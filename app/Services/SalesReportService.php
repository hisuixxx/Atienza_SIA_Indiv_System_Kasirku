<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\DailyClosingReport;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesReportService
{
    private const CASH_METHODS = [
        Payment::METHOD_CASH,
        Payment::METHOD_CASH_ON_PICKUP,
        Payment::METHOD_CASH_ON_DELIVERY,
    ];

    private const SETTLED_STATUSES = [
        Payment::STATUS_COMPLETED,
        Payment::STATUS_APPROVED,
    ];

    public function resolveRange(string $period, ?string $date, ?string $startDate, ?string $endDate): array
    {
        $referenceDate = CarbonImmutable::parse($date ?? today()->toDateString());

        if ($period === 'custom') {
            if (! $startDate || ! $endDate) {
                throw ValidationException::withMessages([
                    'start_date' => 'A start and end date are required for a custom range.',
                ]);
            }

            $start = CarbonImmutable::parse($startDate);
            $end = CarbonImmutable::parse($endDate);
        } elseif ($period === 'weekly') {
            $start = $referenceDate->startOfWeek(CarbonImmutable::MONDAY);
            $end = $start->endOfWeek(CarbonImmutable::SUNDAY);
        } elseif ($period === 'monthly') {
            $start = $referenceDate->startOfMonth();
            $end = $referenceDate->endOfMonth();
        } else {
            $start = $referenceDate;
            $end = $referenceDate;
        }

        return [
            'period' => $period,
            'date' => $referenceDate->toDateString(),
            'start' => $start->startOfDay(),
            'end' => $end->endOfDay(),
        ];
    }

    public function report(
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?int $branchId,
        ?string $paymentMethod,
        ?string $orderStatus
    ): array {
        $payments = $this->settledPayments($start, $end, $branchId, $paymentMethod, $orderStatus);
        $refunds = $this->processedRefunds($start, $end, $branchId, $paymentMethod, $orderStatus);

        $grossSales = (float) (clone $payments)->sum('amount');
        $cashSales = (float) (clone $payments)->whereIn('payment_method', self::CASH_METHODS)->sum('amount');
        $gcashSales = (float) (clone $payments)->where('payment_method', Payment::METHOD_GCASH)->sum('amount');
        $refundTotal = (float) (clone $refunds)->sum('refunds.amount');

        $completedPayments = (clone $payments)->whereHas('order', fn (EloquentBuilder $query) => $query->where('status', Order::STATUS_COMPLETED));
        $completedOrders = (clone $completedPayments)->distinct('order_id')->count('order_id');
        $cancelledOrders = $this->ordersInRange($start, $end, $branchId, $paymentMethod, $orderStatus)
            ->where('status', Order::STATUS_CANCELLED)
            ->count();
        $totalOrders = $this->ordersInRange($start, $end, $branchId, $paymentMethod, $orderStatus)->count();
        $completedSales = (float) (clone $completedPayments)->sum('amount');
        $paidOrderCount = (clone $payments)->distinct('order_id')->count('order_id');
        $netSales = $grossSales - $refundTotal;

        $refundsByMethod = (clone $refunds)
            ->selectRaw('payments.payment_method, SUM(refunds.amount) as amount')
            ->groupBy('payments.payment_method')
            ->get()
            ->keyBy('payment_method');

        $breakdown = collect([
            ['method' => 'cash', 'methods' => self::CASH_METHODS],
            ['method' => 'gcash', 'methods' => [Payment::METHOD_GCASH]],
            ['method' => 'other', 'methods' => null],
        ])->map(function (array $bucket) use ($payments, $refundsByMethod): array {
            $query = clone $payments;
            if ($bucket['methods'] === null) {
                $query->whereNotIn('payment_method', array_merge(self::CASH_METHODS, [Payment::METHOD_GCASH]));
            } else {
                $query->whereIn('payment_method', $bucket['methods']);
            }

            $refundAmount = (float) $refundsByMethod
                ->filter(fn ($row, $method) => $bucket['methods'] === null
                    ? ! in_array($method, array_merge(self::CASH_METHODS, [Payment::METHOD_GCASH]), true)
                    : in_array($method, $bucket['methods'], true))
                ->sum('amount');
            $amount = (float) $query->sum('amount');

            return [
                'method' => $bucket['method'],
                'count' => (clone $query)->distinct('order_id')->count('order_id'),
                'amount' => $amount,
                'refunds' => $refundAmount,
                'net' => $amount - $refundAmount,
            ];
        })->all();

        return [
            'summary' => [
                'gross_sales' => $grossSales,
                'cash_sales' => $cashSales,
                'gcash_sales' => $gcashSales,
                'refund_total' => $refundTotal,
                'net_sales' => $netSales,
                'completed_orders' => $completedOrders,
                'cancelled_orders' => $cancelledOrders,
                'total_orders' => $totalOrders,
                'average_order_value' => $completedOrders ? $completedSales / $completedOrders : 0,
                'paid_orders' => $paidOrderCount,
                'gross' => $grossSales,
                'refunds' => $refundTotal,
                'net' => $netSales,
                'orders' => $paidOrderCount,
                'average' => $completedOrders ? $completedSales / $completedOrders : 0,
            ],
            'breakdown' => $breakdown,
            'sales_rows' => (clone $payments)
                ->with(['order.branch'])
                ->orderByRaw('COALESCE(paid_at, created_at) DESC')
                ->limit(500)
                ->get()
                ->map(fn (Payment $payment) => [
                    'id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'order_number' => $payment->order?->order_number ?? '#'.$payment->order_id,
                    'customer' => $payment->order?->customer_name ?: 'Walk-in',
                    'branch' => $payment->order?->branch?->name ?? 'Unassigned',
                    'method' => $payment->payment_method ?? 'other',
                    'amount' => (float) $payment->amount,
                    'payment_status' => $payment->status,
                    'order_status' => $payment->order?->status ?? $payment->order?->order_status,
                    'date' => $payment->paid_at ?? $payment->created_at,
                ])->values(),
            'refund_rows' => (clone $refunds)
                ->leftJoin('branches', 'orders.branch_id', '=', 'branches.id')
                ->select('refunds.id', 'refunds.amount', 'refunds.reason', 'refunds.processed_at', 'orders.order_number', 'orders.customer_name', 'branches.name as branch_name', 'payments.payment_method')
                ->latest('refunds.processed_at')
                ->limit(500)
                ->get()
                ->map(fn ($refund) => [
                    'id' => $refund->id,
                    'order_number' => $refund->order_number ?? 'Order',
                    'customer' => $refund->customer_name ?: 'Walk-in',
                    'branch' => $refund->branch_name ?? 'Unassigned',
                    'method' => $refund->payment_method ?? 'other',
                    'amount' => (float) $refund->amount,
                    'reason' => $refund->reason,
                    'date' => $refund->processed_at,
                ])->values(),
            'sales_trend' => (clone $payments)
                ->selectRaw('DATE(COALESCE(paid_at, created_at)) as sales_date, SUM(amount) as amount')
                ->groupByRaw('DATE(COALESCE(paid_at, created_at))')
                ->orderBy('sales_date')
                ->get()
                ->map(fn ($row) => ['date' => $row->sales_date, 'amount' => (float) $row->amount])
                ->values(),
            'order_statuses' => $this->ordersInRange($start, $end, $branchId, $paymentMethod, $orderStatus)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->orderBy('status')
                ->get()
                ->map(fn ($row) => ['status' => $row->status, 'count' => (int) $row->total])
                ->values(),
            'top_products' => $this->topProducts($start, $end, $branchId, $paymentMethod, $orderStatus),
            'branch_comparison' => $this->branchComparison($start, $end, $paymentMethod, $orderStatus),
        ];
    }

    public function generateClosingReport(Branch $branch, CarbonImmutable $date, ?User $user = null): DailyClosingReport
    {
        $start = $date->startOfDay();
        $end = $date->endOfDay();
        $payments = $this->settledPayments($start, $end, $branch->id, null, null);
        $refunds = $this->processedRefunds($start, $end, $branch->id, null, null);
        $grossSales = (float) (clone $payments)->sum('amount');
        $refundTotal = (float) (clone $refunds)->sum('refunds.amount');
        $orders = Order::query()->where('branch_id', $branch->id)->whereBetween('created_at', [$start, $end]);

        return DailyClosingReport::updateOrCreate(
            ['branch_id' => $branch->id, 'closing_date' => $date->toDateString()],
            [
                'total_orders' => (clone $orders)->count(),
                'completed_orders' => (clone $orders)->where('status', Order::STATUS_COMPLETED)->count(),
                'cancelled_orders' => (clone $orders)->where('status', Order::STATUS_CANCELLED)->count(),
                'cash_sales' => (float) (clone $payments)->whereIn('payment_method', self::CASH_METHODS)->sum('amount'),
                'gcash_sales' => (float) (clone $payments)->where('payment_method', Payment::METHOD_GCASH)->sum('amount'),
                'other_sales' => $grossSales
                    - (float) (clone $payments)->whereIn('payment_method', self::CASH_METHODS)->sum('amount')
                    - (float) (clone $payments)->where('payment_method', Payment::METHOD_GCASH)->sum('amount'),
                'gross_sales' => $grossSales,
                'refund_total' => $refundTotal,
                'net_sales' => $grossSales - $refundTotal,
                'generated_by_user_id' => $user?->id,
                'generated_at' => now(),
            ]
        );
    }

    private function settledPayments(
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?int $branchId,
        ?string $paymentMethod,
        ?string $orderStatus
    ): EloquentBuilder {
        $methods = $this->methodCodes($paymentMethod);

        return Payment::query()
            ->whereIn('id', $this->latestSettledPaymentIds())
            ->whereIn('status', self::SETTLED_STATUSES)
            ->whereBetween(DB::raw('COALESCE(paid_at, created_at)'), [$start, $end])
            ->when($methods, fn (EloquentBuilder $query) => $query->whereIn('payment_method', $methods))
            ->whereHas('order', function (EloquentBuilder $query) use ($branchId, $orderStatus): void {
                $query->when($branchId, fn (EloquentBuilder $orders) => $orders->where('branch_id', $branchId))
                    ->when($orderStatus, fn (EloquentBuilder $orders) => $orders->where('status', $orderStatus));
            });
    }

    private function processedRefunds(
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?int $branchId,
        ?string $paymentMethod,
        ?string $orderStatus
    ): QueryBuilder {
        $methods = $this->methodCodes($paymentMethod);

        return DB::table('refunds')
            ->join('payments', 'refunds.payment_id', '=', 'payments.id')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->where('refunds.status', 'processed_manual')
            ->whereNotNull('refunds.processed_at')
            ->whereExists(function (QueryBuilder $query): void {
                $query->from('payments as settled_payment_check')
                    ->whereColumn('settled_payment_check.order_id', 'orders.id')
                    ->whereIn('settled_payment_check.status', self::SETTLED_STATUSES);
            })
            ->whereBetween('refunds.processed_at', [$start, $end])
            ->when($branchId, fn (QueryBuilder $query) => $query->where('orders.branch_id', $branchId))
            ->when($methods, fn (QueryBuilder $query) => $query->whereIn('payments.payment_method', $methods))
            ->when($orderStatus, fn (QueryBuilder $query) => $query->where('orders.status', $orderStatus));
    }

    private function ordersInRange(
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?int $branchId,
        ?string $paymentMethod,
        ?string $orderStatus
    ): EloquentBuilder {
        $methods = $this->methodCodes($paymentMethod);

        return Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->when($branchId, fn (EloquentBuilder $query) => $query->where('branch_id', $branchId))
            ->when($orderStatus, fn (EloquentBuilder $query) => $query->where('status', $orderStatus))
            ->when($methods, fn (EloquentBuilder $query) => $query->whereHas('payment', fn (EloquentBuilder $payment) => $payment->whereIn('payment_method', $methods)));
    }

    private function topProducts(
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?int $branchId,
        ?string $paymentMethod,
        ?string $orderStatus
    ): Collection {
        $methods = $this->methodCodes($paymentMethod);
        $status = $orderStatus ?? Order::STATUS_COMPLETED;

        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('payments', 'payments.order_id', '=', 'orders.id')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->whereIn('payments.id', $this->latestSettledPaymentIds())
            ->whereBetween(DB::raw('COALESCE(payments.paid_at, payments.created_at)'), [$start, $end])
            ->where('orders.status', $status)
            ->when($methods, fn (QueryBuilder $query) => $query->whereIn('payments.payment_method', $methods))
            ->when($branchId, fn (QueryBuilder $query) => $query->where('orders.branch_id', $branchId))
            ->selectRaw('order_items.product_id, COALESCE(products.name, ?) as product_name, SUM(order_items.quantity) as quantity, SUM(order_items.subtotal) as sales', ['Deleted product'])
            ->groupBy('order_items.product_id', 'products.name')
            ->orderByDesc('quantity')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'product_id' => $row->product_id,
                'product_name' => $row->product_name,
                'quantity' => (int) $row->quantity,
                'sales' => (float) $row->sales,
            ]);
    }

    private function branchComparison(
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?string $paymentMethod,
        ?string $orderStatus
    ): Collection {
        $methods = $this->methodCodes($paymentMethod);
        $paymentRows = DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->whereIn('payments.id', $this->latestSettledPaymentIds())
            ->whereBetween(DB::raw('COALESCE(payments.paid_at, payments.created_at)'), [$start, $end])
            ->when($methods, fn (QueryBuilder $query) => $query->whereIn('payments.payment_method', $methods))
            ->when($orderStatus, fn (QueryBuilder $query) => $query->where('orders.status', $orderStatus))
            ->selectRaw('orders.branch_id, SUM(payments.amount) as gross_sales, COUNT(DISTINCT orders.id) as paid_orders')
            ->groupBy('orders.branch_id')
            ->get()
            ->keyBy('branch_id');
        $refundRows = $this->processedRefunds($start, $end, null, $paymentMethod, $orderStatus)
            ->selectRaw('orders.branch_id, SUM(refunds.amount) as refund_total')
            ->groupBy('orders.branch_id')
            ->pluck('refund_total', 'branch_id');

        return Branch::query()->orderBy('name')->get()->map(function (Branch $branch) use ($paymentRows, $refundRows): array {
            $gross = (float) ($paymentRows->get($branch->id)?->gross_sales ?? 0);
            $refunds = (float) ($refundRows[$branch->id] ?? 0);

            return [
                'branch_id' => $branch->id,
                'branch' => $branch->name,
                'gross_sales' => $gross,
                'refund_total' => $refunds,
                'net_sales' => $gross - $refunds,
                'paid_orders' => (int) ($paymentRows->get($branch->id)?->paid_orders ?? 0),
            ];
        });
    }

    private function latestSettledPaymentIds(): QueryBuilder
    {
        return DB::table('payments as settled_payments')
            ->selectRaw('MAX(settled_payments.id)')
            ->whereIn('settled_payments.status', self::SETTLED_STATUSES)
            ->groupBy('settled_payments.order_id');
    }

    private function methodCodes(?string $paymentMethod): ?array
    {
        return match ($paymentMethod) {
            'cash' => self::CASH_METHODS,
            'gcash' => [Payment::METHOD_GCASH],
            default => $paymentMethod ? [$paymentMethod] : null,
        };
    }
}
