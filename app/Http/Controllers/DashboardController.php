<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItems;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'period' => 'nullable|in:today,last7,last30,custom',
            'start_date' => 'nullable|required_if:period,custom|date',
            'end_date' => 'nullable|required_if:period,custom|date|after_or_equal:start_date',
        ]);
        $period = $filters['period'] ?? 'today';
        $start = match ($period) {
            'last7' => today()->subDays(6),
            'last30' => today()->subDays(29),
            'custom' => $filters['start_date'] ?? today()->toDateString(),
            default => today()->toDateString(),
        };
        $end = $period === 'custom' ? ($filters['end_date'] ?? today()->toDateString()) : today()->toDateString();
        $user = $request->user();
        $isAdministrator = in_array($user->role, ['admin', 'administrator'], true);
        $branches = $isAdministrator ? Branch::where('is_active', true)->orderBy('name')->get() : $user->branches()->where('is_active', true)->orderBy('name')->get();
        $requestedBranch = $request->integer('branch_id');
        if ($requestedBranch && ! $branches->contains('id', $requestedBranch)) {
            abort(403);
        }
        $branchId = $requestedBranch ?: ($isAdministrator ? null : $branches->first()?->id);
        $scopeBranchOrders = fn ($query) => $branchId ? $query->where('branch_id', $branchId) : ($isAdministrator ? $query : $query->whereRaw('1 = 0'));
        $scopeOrders = fn ($query) => $scopeBranchOrders($query)->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end);
        $scopeBranchPayments = fn ($query) => $query->whereHas('order', fn ($orders) => $scopeBranchOrders($orders));
        $scopePayments = fn ($query) => $scopeBranchPayments($query)->whereDate('paid_at', '>=', $start)->whereDate('paid_at', '<=', $end);

        $ordersByStatus = $scopeOrders(Order::query())->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $statusCounts = [
            'pending' => (int) ($ordersByStatus['pending'] ?? 0) + (int) ($ordersByStatus['preparing'] ?? 0) + (int) ($ordersByStatus['ready'] ?? 0),
            'completed' => (int) ($ordersByStatus['completed'] ?? 0),
            'cancelled' => (int) ($ordersByStatus['cancelled'] ?? 0),
        ];

        $topProducts = OrderItems::whereHas('order', fn ($orders) => $scopeOrders($orders))->selectRaw('product_id, SUM(quantity) as total_quantity, SUM(subtotal) as total_revenue')
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->with('product:id,name')
            ->limit(5)
            ->get()
            ->filter(fn ($item) => $item->product !== null)
            ->map(fn ($item) => [
                'id' => $item->product_id,
                'name' => $item->product->name,
                'quantity_sold' => (int) $item->total_quantity,
                'revenue' => (float) $item->total_revenue,
            ])
            ->values();

        $recentOrders = $scopeOrders(Order::query())->with(['payment', 'branch'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'customer_name' => $order->customer_name,
                'branch_name' => $order->branch?->name,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'total' => (float) ($order->payment->amount ?? 0),
                'created_at' => $order->created_at,
            ]);

        $revenueTrend = collect(range(13, 0))
            ->map(function (int $daysAgo) use ($scopeBranchPayments) {
                $date = today()->subDays($daysAgo);

                return [
                    'date' => $date->toDateString(),
                    'revenue' => (float) $scopeBranchPayments(Payment::query())->where('status', 'completed')
                        ->whereDate('paid_at', $date)
                        ->sum('amount'),
                ];
            })
            ->values();

        $ordersByStatusChart = [
            ['status' => 'pending', 'label' => 'Pending', 'count' => $statusCounts['pending']],
            ['status' => 'completed', 'label' => 'Completed', 'count' => $statusCounts['completed']],
            ['status' => 'cancelled', 'label' => 'Cancelled', 'count' => $statusCounts['cancelled']],
        ];

        $branchPerformance = DB::table('payments')->join('orders', 'payments.order_id', '=', 'orders.id')->join('branches', 'orders.branch_id', '=', 'branches.id')
            ->where('payments.status', 'completed')->whereDate('payments.paid_at', '>=', $start)->whereDate('payments.paid_at', '<=', $end)
            ->when($branchId, fn ($query) => $query->where('orders.branch_id', $branchId))
            ->select('branches.id', 'branches.name')->selectRaw('SUM(payments.amount) as sales, COUNT(payments.id) as orders')
            ->groupBy('branches.id', 'branches.name')->orderByDesc('sales')->get();
        $branchGross = (float) $branchPerformance->sum('sales');
        $branchPerformance = $branchPerformance->map(fn ($branch) => [
            'id' => $branch->id, 'name' => $branch->name, 'sales' => (float) $branch->sales, 'orders' => (int) $branch->orders,
            'share' => $branchGross > 0 ? round(((float) $branch->sales / $branchGross) * 100, 1) : 0,
        ]);
        $needsAttention = DB::table('inventory')->join('ingredients', 'inventory.ingredient_id', '=', 'ingredients.id')->join('branches', 'inventory.branch_id', '=', 'branches.id')
            ->where('ingredients.is_active', true)->whereColumn('inventory.quantity', '<=', 'ingredients.minimum_stock')
            ->when($branchId, fn ($query) => $query->where('inventory.branch_id', $branchId))
            ->orderBy('inventory.quantity')->limit(8)->get(['ingredients.name as ingredient', 'ingredients.unit', 'inventory.quantity', 'ingredients.minimum_stock', 'branches.name as branch'])
            ->map(fn ($item) => ['ingredient' => $item->ingredient, 'unit' => $item->unit, 'quantity' => (float) $item->quantity, 'minimum_stock' => (float) $item->minimum_stock, 'branch' => $item->branch]);

        return Inertia::render('admin/dashboard/index', [
            'stats' => [
                'total_revenue' => (float) $scopePayments(Payment::query())->where('status', 'completed')->sum('amount'),
                'total_orders' => array_sum($statusCounts),
                'pending_orders' => $statusCounts['pending'],
                'completed_orders' => $statusCounts['completed'],
                'cancelled_orders' => $statusCounts['cancelled'],
                'active_orders' => (int) ($ordersByStatus['pending'] ?? 0) + (int) ($ordersByStatus['preparing'] ?? 0) + (int) ($ordersByStatus['ready'] ?? 0),
                'today_orders' => $scopeOrders(Order::query())->count(),
                'today_revenue' => (float) $scopePayments(Payment::query())->where('status', 'completed')
                    ->whereDate('paid_at', today())
                    ->sum('amount'),
                'total_products' => Product::count(),
                'total_categories' => Category::count(),
            ],
            'topProducts' => $topProducts,
            'recentOrders' => $recentOrders,
            'revenueTrend' => $revenueTrend,
            'ordersByStatus' => $ordersByStatusChart,
            'branches' => $branches,
            'selectedBranch' => $branchId,
            'filters' => ['period' => $period, 'start_date' => $start, 'end_date' => $end],
            'branchPerformance' => $branchPerformance,
            'needsAttention' => $needsAttention,
            'pendingPayments' => $scopePayments(Payment::query())->where('status', 'pending')->count(),
        ]);
    }
}
