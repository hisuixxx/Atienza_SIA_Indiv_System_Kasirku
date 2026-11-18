<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItems;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
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
        $isAdministrator = in_array($user->role, [User::ROLE_ADMIN, 'administrator'], true);
        $branches = $isAdministrator
            ? Branch::orderBy('name')->get()
            : $user->branches()->where('is_active', true)->orderBy('name')->get();
        $requestedBranch = $request->filled('branch_id') ? $request->integer('branch_id') : null;
        if ($requestedBranch !== null && (! $requestedBranch || ! $branches->contains('id', $requestedBranch))) {
            abort(403);
        }
        $branchId = $requestedBranch;
        $scopeBranchOrders = fn ($query) => $branchId
            ? $query->where('branch_id', $branchId)
            : ($isAdministrator ? $query : $query->whereIn('branch_id', $branches->modelKeys()));
        $scopeOrders = fn ($query) => $scopeBranchOrders($query)->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end);
        $scopeBranchPayments = fn ($query) => $query->whereHas('order', fn ($orders) => $scopeBranchOrders($orders));
        $scopePayments = fn ($query) => $scopeBranchPayments($query)->whereDate('paid_at', '>=', $start)->whereDate('paid_at', '<=', $end);

        $ordersByStatus = $scopeOrders(Order::query())->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');
        $totalOrders = $scopeOrders(Order::query())->count();
        $activeStatuses = ['pending', 'confirmed', 'preparing', 'ready', 'ready_for_pickup', 'out_for_delivery'];
        $activeOrders = collect($activeStatuses)->sum(fn (string $status) => (int) ($ordersByStatus[$status] ?? 0));
        $statusLabels = [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'preparing' => 'Preparing',
            'ready' => 'Ready',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
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

        $recentTransactions = $scopeBranchPayments(Payment::query()->with('order.branch'))
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Payment $payment) => [
                'id' => $payment->id,
                'order_id' => $payment->order_id,
                'order_number' => $payment->order?->order_number ?? '#'.$payment->order_id,
                'branch_name' => $payment->order?->branch?->name,
                'method' => $payment->payment_method ?? 'Other',
                'status' => $payment->status,
                'amount' => (float) $payment->amount,
                'created_at' => $payment->created_at,
            ]);

        $pendingPayments = $scopeBranchPayments(Payment::query())->where('status', 'pending')
            ->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end)
            ->count();

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

        $ordersByStatusChart = collect($statusLabels)->map(fn (string $label, string $status) => [
            'status' => $status,
            'label' => $label,
            'count' => (int) ($ordersByStatus[$status] ?? 0),
        ])->values();

        $performanceBranches = Branch::query()
            ->when(! $isAdministrator, fn ($query) => $query->whereIn('id', $branches->modelKeys()))
            ->orderBy('name')
            ->get();
        $performanceSales = DB::table('payments')->join('orders', 'payments.order_id', '=', 'orders.id')
            ->where('payments.status', 'completed')->whereDate('payments.paid_at', '>=', $start)->whereDate('payments.paid_at', '<=', $end)
            ->when(! $isAdministrator, fn ($query) => $query->whereIn('orders.branch_id', $branches->modelKeys()))
            ->select('orders.branch_id')->selectRaw('SUM(payments.amount) as sales, COUNT(payments.id) as orders')
            ->groupBy('orders.branch_id')->get()->keyBy('branch_id');
        $branchGross = (float) $performanceSales->sum('sales');
        $branchPerformance = $performanceBranches->map(function (Branch $branch) use ($performanceSales, $branchGross): array {
            $sales = $performanceSales->get($branch->id);
            $amount = (float) ($sales->sales ?? 0);

            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'sales' => $amount,
                'orders' => (int) ($sales->orders ?? 0),
                'share' => $branchGross > 0 ? round(($amount / $branchGross) * 100, 1) : 0,
            ];
        })->sortByDesc('sales')->values();

        return Inertia::render('admin/dashboard/index', [
            'stats' => [
                'total_revenue' => (float) $scopePayments(Payment::query())->where('status', 'completed')->sum('amount'),
                'total_orders' => $totalOrders,
                'pending_orders' => (int) ($ordersByStatus['pending'] ?? 0),
                'completed_orders' => (int) ($ordersByStatus['completed'] ?? 0),
                'cancelled_orders' => (int) ($ordersByStatus['cancelled'] ?? 0),
                'active_orders' => $activeOrders,
                'today_orders' => $scopeOrders(Order::query())->count(),
                'today_revenue' => (float) $scopePayments(Payment::query())->where('status', 'completed')
                    ->whereDate('paid_at', today())
                    ->sum('amount'),
                'total_branches' => $isAdministrator ? Branch::count() : $branches->count(),
                'total_products' => Product::count(),
                'total_categories' => Category::count(),
            ],
            'topProducts' => $topProducts,
            'recentOrders' => $recentOrders,
            'recentTransactions' => $recentTransactions,
            'revenueTrend' => $revenueTrend,
            'ordersByStatus' => $ordersByStatusChart,
            'branches' => $branches,
            'selectedBranch' => $branchId,
            'isAdministrator' => $isAdministrator,
            'filters' => ['period' => $period, 'start_date' => $start, 'end_date' => $end],
            'branchPerformance' => $branchPerformance,
            'pendingPayments' => $pendingPayments,
        ]);
    }
}
