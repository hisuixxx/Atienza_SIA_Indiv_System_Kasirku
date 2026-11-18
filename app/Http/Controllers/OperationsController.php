<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\OrderStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class OperationsController extends Controller
{
    private function scopedOrders(Request $request)
    {
        $query = Order::with(['branch', 'payment', 'orderItems.product', 'statusHistories.user', 'statusHistories.branch']);
        [$branches, $branchId] = $this->branchData($request);
        if ($branchId) {
            $query->where('branch_id', $branchId);
        } elseif (! $this->isAdministrator($request->user())) {
            $query->whereIn('branch_id', $branches->modelKeys());
        }

        return $query;
    }

    private function branchData(Request $request): array
    {
        $user = $request->user();
        $branches = $this->isAdministrator($user) ? Branch::orderBy('name')->get() : $user->branches()->where('is_active', true)->orderBy('name')->get();
        $requestedBranch = $request->filled('branch_id') ? $request->integer('branch_id') : null;
        if ($requestedBranch !== null && (! $requestedBranch || ! $branches->contains('id', $requestedBranch))) {
            abort(403, 'You cannot access that branch.');
        }

        return [$branches, $requestedBranch];
    }

    public function kitchen(Request $request)
    {
        [$branches, $selectedBranch] = $this->branchData($request);
        $orders = $this->scopedOrders($request)->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready'])->oldest()->get();

        return Inertia::render('admin/operations/index', [
            'module' => 'kitchen', 'title' => 'Kitchen queue', 'branches' => $branches,
            'selectedBranch' => $selectedBranch, 'orders' => $this->serializeOrders($orders),
        ]);
    }

    public function payments(Request $request)
    {
        [$branches, $selectedBranch] = $this->branchData($request);
        $payments = Payment::with(['order.branch'])
            ->when($selectedBranch, fn ($query) => $query->whereHas('order', fn ($orders) => $orders->where('branch_id', $selectedBranch)))
            ->when(! $selectedBranch && ! $this->isAdministrator($request->user()), fn ($query) => $query->whereHas('order', fn ($orders) => $orders->whereIn('branch_id', $branches->modelKeys())))
            ->latest()->limit(100)->get()->map(fn (Payment $payment) => [
                'id' => $payment->id, 'order_id' => $payment->order_id, 'order_number' => $payment->order?->order_number ?? '#'.$payment->order_id,
                'customer' => $payment->order?->customer_name ?: 'Walk-in', 'branch' => $payment->order?->branch?->name ?? 'Unassigned',
                'method' => $payment->payment_method ?? 'Other', 'amount' => (float) $payment->amount,
                'status' => $payment->status, 'verification_status' => $payment->verification_status,
                'rejection_reason' => $payment->rejection_reason,
                'order_status' => $payment->order?->status,
                'reference' => $payment->gcash_reference_number ?? $payment->transaction_id,
                'receipt_url' => $payment->receipt_url,
                'date' => $payment->paid_at ?? $payment->created_at,
                'verification' => DB::table('payment_verifications')->where('payment_id', $payment->id)->first(),
            ]);

        return Inertia::render('admin/operations/index', ['module' => 'payments', 'title' => 'Payments & ledger', 'branches' => $branches, 'selectedBranch' => $selectedBranch, 'payments' => $payments]);
    }

    public function handoff(Request $request)
    {
        [$branches, $selectedBranch] = $this->branchData($request);
        $orders = $this->scopedOrders($request)->where('status', 'ready')->whereHas('payment', fn ($q) => $q->where('status', 'completed'))->oldest()->get();

        return Inertia::render('admin/operations/index', ['module' => 'handoff', 'title' => 'Order handoff', 'branches' => $branches, 'selectedBranch' => $selectedBranch, 'orders' => $this->serializeOrders($orders)]);
    }

    public function branches(Request $request)
    {
        abort_unless($this->isAdministrator($request->user()), 403);

        return Inertia::render('admin/operations/index', [
            'module' => 'branches', 'title' => 'Branch management', 'branches' => Branch::with('users')->orderBy('name')->get(),
            'employees' => User::whereIn('role', [User::ROLE_STAFF, User::ROLE_KITCHEN])
                ->orderBy('name')->get(['id', 'name', 'email', 'role', 'is_active']),
        ]);
    }

    public function storeBranch(Request $request)
    {
        abort_unless($this->isAdministrator($request->user()), 403);
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        $data = $request->validate(['name' => 'required|string|max:255', 'code' => 'required|string|max:32|unique:branches,code', 'address' => 'nullable|string|max:255']);
        Branch::create($data + ['is_active' => true]);

        return back()->with('success', 'Branch created.');
    }

    public function updateBranch(Request $request, Branch $branch)
    {
        abort_unless($this->isAdministrator($request->user()), 403);
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:32', Rule::unique('branches', 'code')->ignore($branch->id)],
            'address' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
            'employee_ids' => 'present|array',
            'employee_ids.*' => ['integer', Rule::exists('users', 'id')->where(fn ($query) => $query->whereIn('role', [User::ROLE_STAFF, User::ROLE_KITCHEN]))],
        ]);
        DB::transaction(function () use ($branch, $data): void {
            $lockedBranch = Branch::lockForUpdate()->findOrFail($branch->id);
            if ($lockedBranch->is_active && ! $data['is_active']) {
                $activeOrderExists = $lockedBranch->orders()->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready', 'ready_for_pickup', 'out_for_delivery'])->exists();
                abort_unless(! $activeOrderExists, 422, 'Resolve active branch orders before deactivating this branch.');
                abort_unless(Branch::where('is_active', true)->count() > 1, 422, 'At least one branch must remain active.');
            }

            $lockedBranch->update([
                'name' => $data['name'],
                'code' => $data['code'],
                'address' => $data['address'] ?? null,
                'is_active' => $data['is_active'],
            ]);
            $employeeIds = array_map('intval', $data['employee_ids']);
            $currentEmployeeIds = $lockedBranch->users()
                ->whereIn('users.role', [User::ROLE_STAFF, User::ROLE_KITCHEN])
                ->pluck('users.id')->all();
            $lockedBranch->users()->detach(array_diff($currentEmployeeIds, $employeeIds));
            $lockedBranch->users()->syncWithoutDetaching($employeeIds);
        });

        return back()->with('success', 'Branch settings and employee access saved.');
    }

    public function reports(Request $request)
    {
        [$branches, $selectedBranch] = $this->branchData($request);
        $filters = $request->validate([
            'start_date' => 'nullable|date', 'end_date' => 'nullable|date|after_or_equal:start_date',
            'payment_method' => 'nullable|string|max:40',
        ]);
        if (empty($filters['start_date']) && empty($filters['end_date'])) {
            $filters['start_date'] = today()->toDateString();
            $filters['end_date'] = today()->toDateString();
        }
        $ordersQuery = $this->scopedOrders($request)->whereHas('payment', function ($query) use ($filters): void {
            $query->where('status', 'completed');
            if (! empty($filters['payment_method'])) {
                $query->where('payment_method', $filters['payment_method']);
            }
            if (! empty($filters['start_date'])) {
                $query->whereDate('paid_at', '>=', $filters['start_date']);
            }
            if (! empty($filters['end_date'])) {
                $query->whereDate('paid_at', '<=', $filters['end_date']);
            }
            if (empty($filters['start_date']) && empty($filters['end_date'])) {
                $query->whereDate('paid_at', today());
            }
        });
        $orders = $ordersQuery->get();
        $gross = (float) $orders->sum(fn (Order $order) => $order->payment?->amount ?? 0);
        $refundQuery = DB::table('refunds')->join('payments', 'refunds.payment_id', '=', 'payments.id')->join('orders', 'payments.order_id', '=', 'orders.id')->where('refunds.status', 'processed_manual');
        if ($selectedBranch) {
            $refundQuery->where('orders.branch_id', $selectedBranch);
        } elseif (! $this->isAdministrator($request->user())) {
            $refundQuery->whereIn('orders.branch_id', $branches->modelKeys());
        }
        if (! empty($filters['start_date'])) {
            $refundQuery->whereDate('refunds.processed_at', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $refundQuery->whereDate('refunds.processed_at', '<=', $filters['end_date']);
        }
        if (! empty($filters['payment_method'])) {
            $refundQuery->where('payments.payment_method', $filters['payment_method']);
        }
        $refundTotal = (float) $refundQuery->sum('refunds.amount');
        $refundByMethod = (clone $refundQuery)->select('payments.payment_method', DB::raw('SUM(refunds.amount) as amount'))->groupBy('payments.payment_method')->pluck('amount', 'payments.payment_method');
        $groupedOrders = $orders->groupBy(fn (Order $order) => $order->payment?->payment_method ?? 'other');
        $methods = collect($groupedOrders->keys())->merge($refundByMethod->keys())->unique()->values();
        $rows = $methods->map(function ($method) use ($groupedOrders, $refundByMethod) {
            $group = $groupedOrders->get($method, collect());
            $amount = (float) $group->sum(fn ($order) => $order->payment?->amount ?? 0);
            $refunds = (float) ($refundByMethod[$method] ?? 0);

            return ['method' => $method, 'count' => $group->count(), 'amount' => $amount, 'refunds' => $refunds, 'net' => $amount - $refunds];
        });

        return Inertia::render('admin/operations/index', ['module' => 'reports', 'title' => 'Sales reports', 'branches' => $branches, 'selectedBranch' => $selectedBranch, 'filters' => $filters, 'summary' => ['gross' => $gross, 'refunds' => $refundTotal, 'net' => $gross - $refundTotal, 'orders' => $orders->count(), 'average' => $orders->count() ? $gross / $orders->count() : 0], 'breakdown' => $rows]);
    }

    public function exportReports(Request $request)
    {
        $filters = $request->validate(['start_date' => 'nullable|date', 'end_date' => 'nullable|date|after_or_equal:start_date', 'payment_method' => 'nullable|string|max:40']);
        if (empty($filters['start_date']) && empty($filters['end_date'])) {
            $filters['start_date'] = today()->toDateString();
            $filters['end_date'] = today()->toDateString();
        }
        $query = $this->scopedOrders($request)->whereHas('payment', function ($payment) use ($filters): void {
            $payment->where('status', 'completed');
            if (! empty($filters['payment_method'])) {
                $payment->where('payment_method', $filters['payment_method']);
            }
            if (! empty($filters['start_date'])) {
                $payment->whereDate('paid_at', '>=', $filters['start_date']);
            }
            if (! empty($filters['end_date'])) {
                $payment->whereDate('paid_at', '<=', $filters['end_date']);
            }
            if (empty($filters['start_date']) && empty($filters['end_date'])) {
                $payment->whereDate('paid_at', today());
            }
        });
        $orders = $query->get();
        $refundQuery = DB::table('refunds')->join('payments', 'refunds.payment_id', '=', 'payments.id')->join('orders', 'payments.order_id', '=', 'orders.id')->leftJoin('branches', 'orders.branch_id', '=', 'branches.id')->where('refunds.status', 'processed_manual');
        [$branches, $branchId] = $this->branchData($request);
        if ($branchId) {
            $refundQuery->where('orders.branch_id', $branchId);
        } elseif (! $this->isAdministrator($request->user())) {
            $refundQuery->whereIn('orders.branch_id', $branches->modelKeys());
        }
        if (! empty($filters['start_date'])) {
            $refundQuery->whereDate('refunds.processed_at', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $refundQuery->whereDate('refunds.processed_at', '<=', $filters['end_date']);
        }
        if (! empty($filters['payment_method'])) {
            $refundQuery->where('payments.payment_method', $filters['payment_method']);
        }
        $refunds = $refundQuery->get(['refunds.*', 'payments.payment_method', 'orders.order_number', 'orders.customer_name', 'branches.name as branch_name']);

        return response()->streamDownload(function () use ($orders, $refunds): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['Order', 'Date', 'Branch', 'Customer', 'Payment method', 'Amount', 'Status']);
            foreach ($orders as $order) {
                fputcsv($stream, [$order->order_number ?? '#'.$order->id, $order->payment?->paid_at, $order->branch?->name, $order->customer_name, $order->payment?->payment_method, $order->payment?->amount, $order->status]);
            }
            foreach ($refunds as $refund) {
                fputcsv($stream, ['Refund '.$refund->order_number, $refund->processed_at, $refund->branch_name, $refund->customer_name, $refund->payment_method, -$refund->amount, 'processed_manual']);
            }
            fclose($stream);
        }, 'brewsync-sales-report.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function refunds(Request $request)
    {
        [$branches, $selectedBranch] = $this->branchData($request);
        $refundsQuery = DB::table('refunds')->join('payments', 'refunds.payment_id', '=', 'payments.id')->join('orders', 'payments.order_id', '=', 'orders.id')->leftJoin('branches', 'orders.branch_id', '=', 'branches.id')->leftJoin('users as refund_authorizers', 'refunds.authorized_by', '=', 'refund_authorizers.id')->leftJoin('users as refund_processors', 'refunds.processed_by', '=', 'refund_processors.id')->select('refunds.*', 'orders.branch_id', 'orders.order_number', 'orders.customer_name', 'branches.name as branch_name', 'refund_authorizers.name as authorized_employee', 'refund_processors.name as processed_employee');
        if ($selectedBranch) {
            $refundsQuery->where('orders.branch_id', $selectedBranch);
        } elseif (! $this->isAdministrator($request->user())) {
            $refundsQuery->whereIn('orders.branch_id', $branches->modelKeys());
        }
        $refunds = $refundsQuery->latest('refunds.created_at')->limit(100)->get();

        $cancellationsQuery = DB::table('cancellations')->join('orders', 'cancellations.order_id', '=', 'orders.id')->leftJoin('branches', 'orders.branch_id', '=', 'branches.id')->leftJoin('users as cancellation_users', 'cancellations.requested_by', '=', 'cancellation_users.id')->select('cancellations.*', 'orders.branch_id', 'orders.order_number', 'orders.customer_name', 'branches.name as branch_name', 'cancellation_users.name as employee_name');
        if ($selectedBranch) {
            $cancellationsQuery->where('orders.branch_id', $selectedBranch);
        } elseif (! $this->isAdministrator($request->user())) {
            $cancellationsQuery->whereIn('orders.branch_id', $branches->modelKeys());
        }
        $cancellations = $cancellationsQuery->latest('cancellations.created_at')->limit(100)->get();

        return Inertia::render('admin/operations/index', ['module' => 'refunds', 'title' => 'Cancellations & refunds', 'branches' => $branches, 'selectedBranch' => $selectedBranch, 'refunds' => $refunds, 'cancellations' => $cancellations]);
    }

    public function updateKitchen(Request $request, Order $order)
    {
        $this->authorizeRoles($request, ['admin', 'administrator', 'branch_manager', 'kitchen', 'kitchen_staff']);
        $this->authorizeBranch($request, $order);
        abort_unless(in_array($order->status, ['pending', 'confirmed', 'preparing', 'ready'], true), 422, 'This order can no longer be cancelled.');
        $data = $request->validate(['status' => 'required|in:confirmed,preparing,ready']);
        DB::transaction(function () use ($request, $order, $data): void {
            $lockedOrder = Order::lockForUpdate()->findOrFail($order->id);
            app(OrderStatusService::class)->transition($lockedOrder, $data['status'], $request->user());
        });

        return back()->with('success', 'Order moved to '.ucfirst($data['status']).'.');
    }

    public function confirmHandoff(Request $request, Order $order)
    {
        $this->authorizeRoles($request, ['administrator', 'branch_manager', 'cashier']);
        $this->authorizeBranch($request, $order);
        abort_unless($order->status === 'ready' && $order->payment?->status === 'completed', 422, 'Order must be ready and paid before handoff.');

        DB::transaction(function () use ($request, $order): void {
            $lockedOrder = Order::lockForUpdate()->findOrFail($order->id);
            abort_unless($lockedOrder->status === 'ready', 422, 'Only ready orders can be handed off.');
            app(OrderStatusService::class)->transition($lockedOrder, Order::STATUS_COMPLETED, $request->user());
            DB::table('handoffs')->insert([
                'order_id' => $order->id,
                'user_id' => $request->user()->id,
                'notes' => 'Pickup confirmed.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('success', 'Pickup confirmed.');
    }

    public function requestCancellation(Request $request, Order $order)
    {
        $this->authorizeRoles($request, ['administrator', 'branch_manager', 'cashier']);
        $this->authorizeBranch($request, $order);
        $data = $request->validate(['reason' => 'required|string|max:500']);

        DB::transaction(function () use ($request, $order, $data): void {
            $lockedOrder = Order::lockForUpdate()->findOrFail($order->id);
            abort_unless(in_array($lockedOrder->status, ['pending', 'confirmed', 'preparing', 'ready'], true), 422, 'This order can no longer be cancelled.');
            abort_if(DB::table('cancellations')->where('order_id', $lockedOrder->id)->exists(), 422, 'This order already has a cancellation record.');
            DB::table('cancellations')->insert([
                'order_id' => $lockedOrder->id,
                'user_id' => $request->user()->id,
                'requested_by' => $request->user()->id,
                'reason' => $data['reason'],
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            app(OrderStatusService::class)->transition($lockedOrder, Order::STATUS_CANCELLED, $request->user(), $data['reason']);

            if ($lockedOrder->user_id) {
                Notification::create([
                    'user_id' => $lockedOrder->user_id,
                    'title' => 'Order cancelled: '.($lockedOrder->order_number ?? '#'.$lockedOrder->id),
                    'message' => 'Your order was cancelled. Reason: '.$data['reason'],
                    'link' => route('order.status', $lockedOrder->id),
                ]);
            }
        });

        return back()->with('success', 'Order cancelled. The original order and payment history were preserved.');
    }

    public function reviewCancellation(Request $request, int $cancellation)
    {
        $this->authorizeRoles($request, ['administrator', 'branch_manager']);
        $data = $request->validate(['decision' => 'required|in:approve,reject', 'review_note' => 'nullable|string|max:500']);
        DB::transaction(function () use ($request, $cancellation, $data): void {
            $record = DB::table('cancellations')->where('id', $cancellation)->lockForUpdate()->first();
            abort_unless($record && $record->status === 'pending', 404);
            $order = Order::lockForUpdate()->findOrFail($record->order_id);
            $this->authorizeBranch($request, $order);
            $approved = $data['decision'] === 'approve';
            DB::table('cancellations')->where('id', $record->id)->update([
                'status' => $approved ? 'cancelled' : 'rejected', 'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(), 'review_note' => $data['review_note'] ?? null, 'updated_at' => now(),
            ]);
            if ($approved) {
                abort_unless(in_array($order->status, ['pending', 'confirmed', 'preparing', 'ready'], true), 422, 'This order can no longer be cancelled.');
                app(OrderStatusService::class)->transition($order, Order::STATUS_CANCELLED, $request->user(), $record->reason);
                DB::table('cancellations')->where('id', $record->id)->update(['cancelled_at' => now(), 'updated_at' => now()]);
                if ($order->user_id) {
                    Notification::create([
                        'user_id' => $order->user_id,
                        'title' => 'Order cancelled: '.($order->order_number ?? '#'.$order->id),
                        'message' => 'Your order was cancelled. Reason: '.$record->reason,
                        'link' => route('order.status', $order->id),
                    ]);
                }
            }
        });

        return back()->with('success', 'Cancellation request reviewed.');
    }

    public function requestRefund(Request $request, Order $order)
    {
        $this->authorizeBranch($request, $order);
        $this->authorizeRoles($request, ['administrator', 'branch_manager', 'cashier']);
        $order->loadMissing('payment');
        abort_unless($order->payment?->status === 'completed', 422, 'Only paid orders can be refunded.');
        $data = $request->validate(['amount' => 'required|numeric|min:0.01', 'reason' => 'required|string|max:500']);
        DB::transaction(function () use ($request, $order, $data): void {
            $payment = Payment::lockForUpdate()->findOrFail($order->payment->id);
            abort_unless($payment->status === 'completed', 422, 'Only paid orders can be refunded.');
            $alreadyRequested = (float) DB::table('refunds')->where('payment_id', $payment->id)->whereNotIn('status', ['rejected'])->sum('amount');
            abort_if($alreadyRequested + (float) $data['amount'] > (float) $payment->amount, 422, 'Refund total cannot exceed the original payment.');
            DB::table('refunds')->insert([
                'payment_id' => $payment->id,
                'user_id' => $request->user()->id,
                'requested_by' => $request->user()->id,
                'amount' => $data['amount'],
                'reason' => $data['reason'],
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('success', 'Refund request submitted for review.');
    }

    public function reviewRefund(Request $request, int $refund)
    {
        $this->authorizeRoles($request, ['administrator', 'branch_manager']);
        $data = $request->validate(['decision' => 'required|in:approve,reject']);
        DB::transaction(function () use ($request, $refund, $data): void {
            $record = DB::table('refunds')->where('id', $refund)->lockForUpdate()->first();
            abort_unless($record && $record->status === 'pending', 404);
            $payment = Payment::with('order')->lockForUpdate()->findOrFail($record->payment_id);
            $this->authorizeBranch($request, $payment->order);
            DB::table('refunds')->where('id', $record->id)->update([
                'status' => $data['decision'] === 'approve' ? 'approved_manual' : 'rejected',
                'authorized_by' => $request->user()->id,
                'authorized_at' => now(),
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('success', 'Refund request reviewed. Approved refunds must still be manually processed.');
    }

    public function markRefundProcessed(Request $request, int $refund)
    {
        $this->authorizeRoles($request, ['administrator', 'branch_manager']);
        $data = $request->validate([
            'processing_confirmation' => 'required|accepted',
            'processing_reference' => 'required|string|max:120',
        ]);
        DB::transaction(function () use ($request, $refund, $data): void {
            $record = DB::table('refunds')->where('id', $refund)->lockForUpdate()->first();
            abort_unless($record && $record->status === 'approved_manual', 404);
            $payment = Payment::with('order')->lockForUpdate()->findOrFail($record->payment_id);
            $this->authorizeBranch($request, $payment->order);
            DB::table('refunds')->where('id', $record->id)->update([
                'status' => 'processed_manual',
                'processed_by' => $request->user()->id,
                'processing_reference' => $data['processing_reference'],
                'processed_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('success', 'Manual refund processing recorded.');
    }

    public function updateEmployee(Request $request, int $employee)
    {
        abort_unless($request->user()->role === 'administrator', 403);
        $data = $request->validate(['role' => 'required|in:administrator,branch_manager,cashier,kitchen_staff,inventory_staff', 'is_active' => 'required|boolean']);
        DB::table('users')->where('id', $employee)->update(['role' => $data['role'], 'is_active' => $data['is_active'], 'updated_at' => now()]);

        return back()->with('success', 'Employee role and access updated.');
    }

    public function submitPaymentReference(Request $request, Payment $payment)
    {
        $this->authorizeRoles($request, ['administrator', 'branch_manager', 'cashier']);
        $payment->load('order');
        $this->authorizeBranch($request, $payment->order);
        abort_unless($payment->payment_method === 'gcash' && $payment->status === 'pending', 422, 'Only pending GCash payments can be submitted for verification.');
        $data = $request->validate(['reference_number' => 'required|string|max:120']);
        DB::transaction(function () use ($request, $payment, $data): void {
            $lockedPayment = Payment::lockForUpdate()->findOrFail($payment->id);
            abort_unless($lockedPayment->payment_method === 'gcash' && $lockedPayment->status === 'pending', 422, 'Only pending GCash payments can be submitted for verification.');
            abort_if(DB::table('payment_verifications')->where('payment_id', $lockedPayment->id)->exists(), 422, 'This payment already has a verification record.');
            DB::table('payment_verifications')->insert([
                'payment_id' => $lockedPayment->id,
                'reference_number' => $data['reference_number'],
                'status' => Payment::VERIFICATION_PENDING,
                'submitted_by' => $request->user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $lockedPayment->update([
                'gcash_reference_number' => $data['reference_number'],
                'verification_status' => Payment::VERIFICATION_PENDING,
            ]);
        });

        return back()->with('success', 'GCash reference submitted for manual verification.');
    }

    public function reviewPaymentReference(Request $request, int $verification)
    {
        $this->authorizeRoles($request, ['administrator', 'branch_manager', 'cashier']);
        $data = $request->validate([
            'decision' => 'required|in:approve,reject',
            'review_note' => 'required_if:decision,reject|nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request, $verification, $data): void {
            $record = DB::table('payment_verifications')->where('id', $verification)->lockForUpdate()->first();
            abort_unless($record && $record->status === Payment::VERIFICATION_PENDING, 404);
            $payment = Payment::with('order')->lockForUpdate()->findOrFail($record->payment_id);
            $this->authorizeBranch($request, $payment->order);
            abort_unless($payment->payment_method === 'gcash'
                && $payment->status === 'pending'
                && $payment->verification_status === Payment::VERIFICATION_PENDING, 422, 'This payment is no longer awaiting verification.');
            $approved = $data['decision'] === 'approve';
            DB::table('payment_verifications')->where('id', $record->id)->update([
                'status' => $approved ? Payment::VERIFICATION_VERIFIED : Payment::VERIFICATION_REJECTED,
                'reviewed_by' => $request->user()->id,
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
                'reviewed_at' => now(), 'review_note' => $data['review_note'] ?? null, 'updated_at' => now(),
            ]);
            $payment->update([
                'status' => $approved ? Payment::STATUS_COMPLETED : Payment::STATUS_REJECTED,
                'payment_status' => $approved ? Payment::STATUS_COMPLETED : Payment::STATUS_REJECTED,
                'verification_status' => $approved ? Payment::VERIFICATION_VERIFIED : Payment::VERIFICATION_REJECTED,
                'paid_at' => $approved ? now() : null,
                'verified_at' => now(),
                'verified_by_user_id' => $request->user()->id,
                'rejection_reason' => $approved ? null : $data['review_note'],
            ]);
        });

        return back()->with('success', 'Payment verification reviewed.');
    }

    private function authorizeBranch(Request $request, Order $order): void
    {
        abort_unless(
            $this->isAdministrator($request->user())
                || (Branch::whereKey($order->branch_id)->where('is_active', true)->exists()
                    && $request->user()->branches()->whereKey($order->branch_id)->exists()),
            403
        );
    }

    private function authorizeRoles(Request $request, array $roles): void
    {
        $aliases = ['admin' => 'administrator', 'staff' => 'cashier', 'kitchen' => 'kitchen_staff'];
        $role = $aliases[$request->user()->role] ?? $request->user()->role;
        $allowed = array_map(fn (string $item) => $aliases[$item] ?? $item, $roles);
        abort_unless(in_array($role, $allowed, true), 403);
    }

    private function isAdministrator($user): bool
    {
        return in_array($user->role, ['admin', 'administrator'], true);
    }

    private function serializeOrders($orders)
    {
        return $orders->map(fn (Order $order) => [
            'id' => $order->id, 'number' => $order->order_number ?? '#'.$order->id, 'customer' => $order->customer_name ?: 'Walk-in',
            'branch' => $order->branch?->name ?? 'Unassigned', 'status' => $order->status, 'total' => (float) ($order->payment?->amount ?? 0),
            'created_at' => $order->created_at, 'items' => $order->orderItems->map(fn ($item) => ['name' => $item->product?->name ?? 'Removed item', 'quantity' => $item->quantity, 'notes' => $item->notes]),
            'status_history' => $order->statusHistories->map(fn ($history) => [
                'from_status' => $history->from_status,
                'to_status' => $history->to_status,
                'employee' => $history->user?->name ?? 'System',
                'branch' => $history->branch?->name,
                'notes' => $history->notes,
                'created_at' => $history->created_at,
            ]),
        ])->values();
    }
}
