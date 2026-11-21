<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Support\SalesReport;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $report = SalesReport::fromRequest($request->query('period'));

        return view('admin.dashboard', [
            'report' => $report,
            'totals' => $report->totals(),
            'dailyRevenue' => $report->dailyRevenue(),
            'dailyOrders' => $report->dailyOrders(),
            'byCategory' => $report->revenueByCategory(),
            'sellThrough' => $report->sellThroughByCategory(),
            'stockHealth' => $report->stockHealth(),
            'recentOrders' => Order::query()->with('customer')->latest('placed_at')->limit(6)->get(),
            'lowStockProducts' => Product::query()->active()->lowStock()->orderBy('stock')->limit(5)->get(),
        ]);
    }
}
