<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\OrderStatus;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Figures for the admin dashboard over a period, compared with the previous period of the same length.
 */
class SalesReport
{
    /**
     * Periods offered in the dashboard selector.
     *
     * @var array<string, string>
     */
    public const PERIODS = [
        '7d' => '7 derniers jours',
        '30d' => '30 derniers jours',
        'month' => 'Ce mois-ci',
        '90d' => '90 derniers jours',
    ];

    public readonly CarbonImmutable $from;

    public readonly CarbonImmutable $to;

    public readonly CarbonImmutable $previousFrom;

    public readonly CarbonImmutable $previousTo;

    public function __construct(public readonly string $period = '30d')
    {
        $today = CarbonImmutable::now()->endOfDay();

        $this->to = $today;
        $this->from = match ($period) {
            '7d' => $today->subDays(6)->startOfDay(),
            'month' => $today->startOfMonth(),
            '90d' => $today->subDays(89)->startOfDay(),
            default => $today->subDays(29)->startOfDay(),
        };

        $days = (int) $this->from->diffInDays($this->to) + 1;
        $this->previousTo = $this->from->subSecond();
        $this->previousFrom = $this->from->subDays($days);
    }

    public static function fromRequest(?string $period): self
    {
        return new self(array_key_exists((string) $period, self::PERIODS) ? (string) $period : '30d');
    }

    public function label(): string
    {
        return self::PERIODS[$this->period];
    }

    /**
     * Headline numbers with their evolution against the previous period.
     *
     * @return array{revenue: float, revenue_change: float|null, orders: int, orders_change: float|null, average_basket: float, average_basket_change: float|null, units: int}
     */
    public function totals(): array
    {
        $current = $this->aggregate($this->from, $this->to);
        $previous = $this->aggregate($this->previousFrom, $this->previousTo);

        return [
            'revenue' => $current['revenue'],
            'revenue_change' => $this->change($current['revenue'], $previous['revenue']),
            'orders' => $current['orders'],
            'orders_change' => $this->change($current['orders'], $previous['orders']),
            'average_basket' => $current['average'],
            'average_basket_change' => $this->change($current['average'], $previous['average']),
            'units' => $this->unitsSold($this->from, $this->to),
        ];
    }

    /**
     * Revenue per day over the period (days without sales included as 0).
     *
     * @return Collection<string, float>
     */
    public function dailyRevenue(): Collection
    {
        return $this->daily(fn (Collection $orders): float => (float) $orders->sum('total'));
    }

    /**
     * Number of orders per day over the period.
     *
     * @return Collection<string, float>
     */
    public function dailyOrders(): Collection
    {
        return $this->daily(fn (Collection $orders): float => (float) $orders->count());
    }

    /**
     * Revenue by category, largest first, grouped beyond the top 5 into "Autres".
     *
     * @return Collection<int, array{name: string, value: float, share: float}>
     */
    public function revenueByCategory(): Collection
    {
        $rows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->whereIn('orders.status', $this->billableValues())
            ->whereBetween('orders.placed_at', [$this->from, $this->to])
            ->groupBy('categories.id', 'categories.name')
            ->selectRaw('categories.name as name, SUM(order_items.line_total) as value')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($row): array => ['name' => $row->name, 'value' => (float) $row->value]);

        if ($rows->count() > 6) {
            $rows = $rows->take(5)->push(['name' => 'Autres', 'value' => (float) $rows->slice(5)->sum('value')]);
        }

        $total = max((float) $rows->sum('value'), 0.01);

        return $rows->map(fn (array $row): array => $row + ['share' => round($row['value'] / $total * 100, 1)])->values();
    }

    /**
     * Share of stock sold over the period, per category (sell-through rate).
     *
     * @return Collection<int, array{name: string, sold: int, stock: int, rate: int}>
     */
    public function sellThroughByCategory(int $limit = 5): Collection
    {
        $sold = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereIn('orders.status', $this->billableValues())
            ->whereBetween('orders.placed_at', [$this->from, $this->to])
            ->groupBy('products.category_id')
            ->selectRaw('products.category_id as category_id, SUM(order_items.quantity) as units')
            ->pluck('units', 'category_id');

        return Category::query()
            ->withSum('products as stock', 'stock')
            ->get()
            ->map(function (Category $category) use ($sold): array {
                $units = (int) ($sold[$category->id] ?? 0);
                $stock = (int) $category->stock;

                return [
                    'name' => $category->name,
                    'sold' => $units,
                    'stock' => $stock,
                    'rate' => $units + $stock > 0 ? (int) round($units / ($units + $stock) * 100) : 0,
                ];
            })
            ->sortByDesc('sold')
            ->take($limit)
            ->values();
    }

    /**
     * @return array{active: int, low: int, out: int, healthy_rate: int}
     */
    public function stockHealth(): array
    {
        $active = Product::query()->active()->count();
        $low = Product::query()->active()->lowStock()->where('stock', '>', 0)->count();
        $out = Product::query()->active()->where('stock', 0)->count();

        return [
            'active' => $active,
            'low' => $low,
            'out' => $out,
            'healthy_rate' => $active > 0 ? (int) round(($active - $low - $out) / $active * 100) : 100,
        ];
    }

    /**
     * @return array{revenue: float, orders: int, average: float}
     */
    private function aggregate(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $row = Order::query()->billable()->whereBetween('placed_at', [$from, $to])
            ->selectRaw('COUNT(*) as orders, COALESCE(SUM(total), 0) as revenue')
            ->first();

        $orders = (int) $row->orders;
        $revenue = (float) $row->revenue;

        return ['revenue' => $revenue, 'orders' => $orders, 'average' => $orders > 0 ? $revenue / $orders : 0.0];
    }

    private function unitsSold(CarbonImmutable $from, CarbonImmutable $to): int
    {
        return (int) OrderItem::query()
            ->whereHas('order', fn ($query) => $query->billable()->whereBetween('placed_at', [$from, $to]))
            ->sum('quantity');
    }

    /**
     * @param  callable(Collection<int, Order>): float  $measure
     * @return Collection<string, float>
     */
    private function daily(callable $measure): Collection
    {
        $orders = Order::query()->billable()->whereBetween('placed_at', [$this->from, $this->to])
            ->get(['total', 'placed_at'])
            ->groupBy(fn (Order $order): string => $order->placed_at->toDateString());

        return collect(CarbonPeriod::create($this->from, '1 day', $this->to->startOfDay()))
            ->mapWithKeys(fn ($day): array => [
                $day->toDateString() => $measure($orders->get($day->toDateString(), collect())),
            ]);
    }

    private function change(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return null;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }

    /**
     * @return list<string>
     */
    private function billableValues(): array
    {
        return array_map(fn (OrderStatus $status): string => $status->value, OrderStatus::billable());
    }
}
