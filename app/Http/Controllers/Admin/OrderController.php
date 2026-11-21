<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\Product;
use App\OrderChannel;
use App\OrderStatus;
use App\PaymentMethod;
use App\PaymentStatus;
use App\Support\OrderPlacement;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with('customer')
            ->withSum('items as units', 'quantity')
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('payment'), fn ($query, $payment) => $query->where('payment_status', $payment))
            ->when($request->query('channel'), fn ($query, $channel) => $query->where('channel', $channel))
            ->when($request->query('method'), fn ($query, $method) => $query->where('payment_method', $method))
            ->when($this->periodStart($request->query('period')), fn ($query, $from) => $query->where('placed_at', '>=', $from))
            ->when($request->query('q'), function ($query, $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($query) => $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%"));
                });
            })
            ->latest('placed_at')
            ->paginate(20)
            ->withQueryString();

        $statusCounts = Order::query()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');

        return view('admin.orders.index', compact('orders', 'statusCounts'));
    }

    /**
     * Start date of a quick period filter (today, 7d, 30d, month).
     */
    private function periodStart(?string $period): ?CarbonInterface
    {
        return match ($period) {
            'today' => now()->startOfDay(),
            '7d' => now()->subDays(6)->startOfDay(),
            '30d' => now()->subDays(29)->startOfDay(),
            'month' => now()->startOfMonth(),
            default => null,
        };
    }

    public function create(): View
    {
        return view('admin.orders.create', [
            'products' => Product::query()->active()->with('brand')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $lines = collect($data['items'])->mapWithKeys(fn (array $item): array => [$item['product_id'] => (int) $item['quantity']])->all();

        try {
            $order = OrderPlacement::place(
                $data['customer'],
                $lines,
                OrderChannel::from($data['channel']),
                PaymentMethod::from($data['payment_method']),
                $data['notes'] ?? null,
                $request->user(),
                (float) ($data['discount'] ?? 0),
            );
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['items' => $exception->getMessage()]);
        }

        return redirect()->route('admin.orders.show', $order)->with('status', "Commande {$order->number} créée.");
    }

    public function show(Order $order): View
    {
        $order->load(['customer', 'items.product', 'stockMovements']);

        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate(['status' => ['required', Rule::enum(OrderStatus::class)]]);

        try {
            OrderPlacement::changeStatus($order, OrderStatus::from($validated['status']), $request->user());
        } catch (DomainException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        }

        return back()->with('status', 'Statut mis à jour : '.$order->status->label().'.');
    }

    public function updatePayment(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'payment_status' => ['required', Rule::enum(PaymentStatus::class)],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
        ]);

        $order->payment_method = PaymentMethod::from($validated['payment_method']);
        OrderPlacement::changePaymentStatus($order, PaymentStatus::from($validated['payment_status']));

        return back()->with('status', 'Paiement mis à jour.');
    }
}
