<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OperationsController extends Controller
{
    private function scopedOrders(Request $request)
    {
        $query = Order::with(['branch', 'payment', 'orderItems.product']);
        [, $branchId] = $this->branchData($request);
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query;
    }

    private function branchData(Request $request): array
    {
        $user = $request->user();
        $branches = $user->role === 'administrator' ? Branch::where('is_active', true)->get() : $user->branches()->where('is_active', true)->get();
        $requestedBranch = $request->integer('branch_id');
        if ($requestedBranch && ! $branches->contains('id', $requestedBranch)) {
            abort(403, 'You cannot access that branch.');
        }

        return [$branches, $requestedBranch ?: ($user->role === 'administrator' ? null : $branches->first()?->id)];
    }

    public function kitchen(Request $request)
    {
        [$branches, $selectedBranch] = $this->branchData($request);
        $orders = $this->scopedOrders($request)->whereIn('status', ['pending', 'preparing', 'ready'])->oldest()->get();

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
            ->latest()->limit(100)->get()->map(fn (Payment $payment) => [
                'id' => $payment->id, 'order_id' => $payment->order_id, 'order_number' => $payment->order?->order_number ?? '#'.$payment->order_id,
                'customer' => $payment->order?->customer_name ?: 'Walk-in', 'branch' => $payment->order?->branch?->name ?? 'Unassigned',
                'method' => $payment->payment_method ?? 'Other', 'amount' => (float) $payment->amount,
                'status' => $payment->status, 'reference' => $payment->transaction_id, 'date' => $payment->paid_at ?? $payment->created_at,
                'verification' => DB::table('payment_verifications')->where('payment_id', $payment->id)->latest()->first(),
            ]);

        return Inertia::render('admin/operations/index', ['module' => 'payments', 'title' => 'Payments & ledger', 'branches' => $branches, 'selectedBranch' => $selectedBranch, 'payments' => $payments]);
    }

    public function handoff(Request $request)
    {
        [$branches, $selectedBranch] = $this->branchData($request);
        $orders = $this->scopedOrders($request)->where('status', 'ready')->whereHas('payment', fn ($q) => $q->where('status', 'completed'))->oldest()->get();

        return Inertia::render('admin/operations/index', ['module' => 'handoff', 'title' => 'Order handoff', 'branches' => $branches, 'selectedBranch' => $selectedBranch, 'orders' => $this->serializeOrders($orders)]);
    }

    public function inventory(Request $request)
    {
        [$branches, $selectedBranch] = $this->branchData($request);
        $branchId = $selectedBranch ?: $branches->first()?->id;
        $ingredients = DB::table('ingredients')->leftJoin('inventory', function ($join) use ($branchId) {
            $join->on('ingredients.id', '=', 'inventory.ingredient_id')->where('inventory.branch_id', '=', $branchId ?? 0);
        })->select('ingredients.*', DB::raw('COALESCE(inventory.quantity, 0) as stock'))->where('ingredients.is_active', true)->orderBy('ingredients.name')->get();

        $products = DB::table('products')->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $recipes = DB::table('product_recipes')->join('ingredients', 'product_recipes.ingredient_id', '=', 'ingredients.id')->get(['product_recipes.product_id', 'product_recipes.ingredient_id', 'product_recipes.quantity', 'ingredients.name as ingredient_name', 'ingredients.unit']);
        $suppliers = DB::table('suppliers')->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $history = DB::table('inventory_transactions')->join('ingredients', 'inventory_transactions.ingredient_id', '=', 'ingredients.id')->leftJoin('users', 'inventory_transactions.user_id', '=', 'users.id')->where('inventory_transactions.branch_id', $branchId ?? 0)->latest('inventory_transactions.created_at')->limit(30)->get(['inventory_transactions.*', 'ingredients.name as ingredient_name', 'users.name as employee_name']);

        return Inertia::render('admin/operations/index', ['module' => 'inventory', 'title' => 'Inventory', 'branches' => $branches, 'selectedBranch' => $branchId, 'ingredients' => $ingredients, 'products' => $products, 'recipes' => $recipes, 'suppliers' => $suppliers, 'history' => $history]);
    }

    public function branches(Request $request)
    {
        abort_unless($request->user()->role === 'administrator', 403);

        return Inertia::render('admin/operations/index', [
            'module' => 'branches', 'title' => 'Branch management', 'branches' => Branch::with('users')->orderBy('name')->get(),
            'employees' => DB::table('users')->orderBy('name')->get(['id', 'name', 'email', 'role', 'is_active']),
        ]);
    }

    public function storeBranch(Request $request)
    {
        abort_unless($request->user()->role === 'administrator', 403);
        $data = $request->validate(['name' => 'required|string|max:255', 'code' => 'required|string|max:32|unique:branches,code', 'address' => 'nullable|string|max:255']);
        Branch::create($data + ['is_active' => true]);

        return back()->with('success', 'Branch created.');
    }

    public function updateBranch(Request $request, Branch $branch)
    {
        abort_unless($request->user()->role === 'administrator', 403);
        $data = $request->validate(['name' => 'required|string|max:255', 'address' => 'nullable|string|max:255', 'is_active' => 'required|boolean', 'employee_ids' => 'present|array', 'employee_ids.*' => 'exists:users,id']);
        $branch->update(['name' => $data['name'], 'address' => $data['address'] ?? null, 'is_active' => $data['is_active']]);
        $branch->users()->sync($data['employee_ids']);

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
        [, $branchId] = $this->branchData($request);
        if ($branchId) {
            $refundQuery->where('orders.branch_id', $branchId);
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
        $refundsQuery = DB::table('refunds')->join('payments', 'refunds.payment_id', '=', 'payments.id')->join('orders', 'payments.order_id', '=', 'orders.id')->leftJoin('branches', 'orders.branch_id', '=', 'branches.id')->select('refunds.*', 'orders.branch_id', 'orders.order_number', 'orders.customer_name', 'branches.name as branch_name');
        if ($selectedBranch) {
            $refundsQuery->where('orders.branch_id', $selectedBranch);
        }
        $refunds = $refundsQuery->latest('refunds.created_at')->limit(100)->get();

        $cancellationsQuery = DB::table('cancellations')->join('orders', 'cancellations.order_id', '=', 'orders.id')->leftJoin('branches', 'orders.branch_id', '=', 'branches.id')->select('cancellations.*', 'orders.branch_id', 'orders.order_number', 'orders.customer_name', 'branches.name as branch_name');
        if ($selectedBranch) {
            $cancellationsQuery->where('orders.branch_id', $selectedBranch);
        }
        $cancellations = $cancellationsQuery->latest('cancellations.created_at')->limit(100)->get();

        return Inertia::render('admin/operations/index', ['module' => 'refunds', 'title' => 'Cancellations & refunds', 'branches' => $branches, 'selectedBranch' => $selectedBranch, 'refunds' => $refunds, 'cancellations' => $cancellations]);
    }

    public function updateKitchen(Request $request, Order $order)
    {
        $this->authorizeRoles($request, ['administrator', 'branch_manager', 'kitchen_staff']);
        $this->authorizeBranch($request, $order);
        $data = $request->validate(['status' => 'required|in:preparing,ready']);
        $allowed = ['pending' => 'preparing', 'preparing' => 'ready'];
        abort_unless(($allowed[$order->status] ?? null) === $data['status'], 422, 'Invalid kitchen status transition.');

        DB::transaction(function () use ($request, $order, $data): void {
            $from = $order->status;
            $order->update(['status' => $data['status']]);
            DB::table('order_status_histories')->insert(['order_id' => $order->id, 'from_status' => $from, 'to_status' => $data['status'], 'user_id' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
        });

        return back()->with('success', 'Order moved to '.ucfirst($data['status']).'.');
    }

    public function confirmHandoff(Request $request, Order $order)
    {
        $this->authorizeRoles($request, ['administrator', 'branch_manager', 'cashier']);
        $this->authorizeBranch($request, $order);
        abort_unless($order->status === 'ready' && $order->payment?->status === 'completed', 422, 'Order must be ready and paid before handoff.');

        DB::transaction(function () use ($request, $order): void {
            $order->update(['status' => 'completed']);
            DB::table('handoffs')->insert(['order_id' => $order->id, 'user_id' => $request->user()->id, 'confirmed_at' => now(), 'reference' => 'HO-'.strtoupper(bin2hex(random_bytes(5))), 'created_at' => now(), 'updated_at' => now()]);
            DB::table('order_status_histories')->insert(['order_id' => $order->id, 'from_status' => 'ready', 'to_status' => 'completed', 'user_id' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
        });

        return back()->with('success', 'Pickup confirmed.');
    }

    public function requestCancellation(Request $request, Order $order)
    {
        $this->authorizeRoles($request, ['administrator', 'branch_manager', 'cashier']);
        $this->authorizeBranch($request, $order);
        abort_unless(in_array($order->status, ['pending', 'preparing', 'ready']), 422);
        abort_if(DB::table('cancellations')->where('order_id', $order->id)->where('status', 'pending')->exists(), 422, 'A cancellation request is already awaiting review.');
        $data = $request->validate(['reason' => 'required|string|max:500']);
        DB::table('cancellations')->insert(['order_id' => $order->id, 'requested_by' => $request->user()->id, 'reason' => $data['reason'], 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Cancellation submitted for manager review.');
    }

    public function reviewCancellation(Request $request, int $cancellation)
    {
        abort_unless(in_array($request->user()->role, ['administrator', 'branch_manager']), 403);
        $data = $request->validate(['decision' => 'required|in:approve,reject', 'review_note' => 'nullable|string|max:500']);
        DB::transaction(function () use ($request, $cancellation, $data): void {
            $record = DB::table('cancellations')->where('id', $cancellation)->lockForUpdate()->first();
            abort_unless($record && $record->status === 'pending', 404);
            $order = Order::lockForUpdate()->findOrFail($record->order_id);
            $this->authorizeBranch($request, $order);
            $approved = $data['decision'] === 'approve';
            DB::table('cancellations')->where('id', $record->id)->update([
                'status' => $approved ? 'approved' : 'rejected', 'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(), 'review_note' => $data['review_note'] ?? null, 'updated_at' => now(),
            ]);
            if ($approved) {
                $from = $order->status;
                $order->update(['status' => 'cancelled']);
                DB::table('order_status_histories')->insert(['order_id' => $order->id, 'from_status' => $from, 'to_status' => 'cancelled', 'user_id' => $request->user()->id, 'notes' => $record->reason, 'created_at' => now(), 'updated_at' => now()]);
            }
        });

        return back()->with('success', 'Cancellation request reviewed.');
    }

    public function requestRefund(Request $request, Order $order)
    {
        $this->authorizeBranch($request, $order);
        abort_unless($request->user()->role === 'administrator' || $request->user()->role === 'branch_manager', 403);
        abort_unless($order->payment?->status === 'completed', 422, 'Only paid orders can be refunded.');
        $data = $request->validate(['amount' => 'required|numeric|min:0.01', 'reason' => 'required|string|max:500']);
        $alreadyRequested = (float) DB::table('refunds')->where('payment_id', $order->payment->id)->whereNotIn('status', ['rejected'])->sum('amount');
        abort_if($alreadyRequested + (float) $data['amount'] > (float) $order->payment->amount, 422, 'Refund total cannot exceed the original payment.');
        DB::table('refunds')->insert([
            'payment_id' => $order->payment->id, 'requested_by' => $request->user()->id,
            'amount' => $data['amount'], 'reason' => $data['reason'], 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', 'Refund request submitted for review.');
    }

    public function reviewRefund(Request $request, int $refund)
    {
        abort_unless(in_array($request->user()->role, ['administrator', 'branch_manager']), 403);
        $data = $request->validate(['decision' => 'required|in:approve,reject']);
        DB::transaction(function () use ($request, $refund, $data): void {
            $record = DB::table('refunds')->where('id', $refund)->lockForUpdate()->first();
            abort_unless($record && $record->status === 'pending', 404);
            $payment = Payment::with('order')->lockForUpdate()->findOrFail($record->payment_id);
            $this->authorizeBranch($request, $payment->order);
            DB::table('refunds')->where('id', $record->id)->update([
                'status' => $data['decision'] === 'approve' ? 'approved_manual' : 'rejected',
                'processed_by' => $request->user()->id,
                'updated_at' => now(),
            ]);
        });

        return back()->with('success', 'Refund request reviewed. Approved refunds must still be manually processed.');
    }

    public function markRefundProcessed(Request $request, int $refund)
    {
        abort_unless(in_array($request->user()->role, ['administrator', 'branch_manager']), 403);
        DB::transaction(function () use ($request, $refund): void {
            $record = DB::table('refunds')->where('id', $refund)->lockForUpdate()->first();
            abort_unless($record && $record->status === 'approved_manual', 404);
            $payment = Payment::with('order')->findOrFail($record->payment_id);
            $this->authorizeBranch($request, $payment->order);
            DB::table('refunds')->where('id', $record->id)->update(['status' => 'processed_manual', 'processed_by' => $request->user()->id, 'processed_at' => now(), 'updated_at' => now()]);
        });

        return back()->with('success', 'Manual refund processing recorded.');
    }

    public function restock(Request $request)
    {
        $this->authorizeRoles($request, ['administrator', 'branch_manager', 'inventory_staff']);
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'ingredient_id' => 'required|exists:ingredients,id',
            'quantity' => 'required|numeric|min:0.001',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'unit_cost' => 'nullable|numeric|min:0',
            'request_key' => 'required|uuid',
            'remarks' => 'nullable|string|max:500',
        ]);
        abort_unless($request->user()->role === 'administrator' || $request->user()->branches()->whereKey($data['branch_id'])->exists(), 403);
        abort_unless(Branch::whereKey($data['branch_id'])->where('is_active', true)->exists(), 422, 'Cannot restock an inactive branch.');
        if (DB::table('restocks')->where('request_key', $data['request_key'])->exists()) {
            return back()->with('success', 'This restock was already recorded.');
        }

        DB::transaction(function () use ($request, $data): void {
            $restockId = DB::table('restocks')->insertGetId([
                'branch_id' => $data['branch_id'], 'supplier_id' => $data['supplier_id'] ?? null, 'user_id' => $request->user()->id,
                'reference' => 'RS-'.strtoupper(bin2hex(random_bytes(5))), 'request_key' => $data['request_key'], 'remarks' => $data['remarks'] ?? null, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $stock = DB::table('inventory')->where('branch_id', $data['branch_id'])->where('ingredient_id', $data['ingredient_id'])->lockForUpdate()->first();
            if ($stock) {
                DB::table('inventory')->where('id', $stock->id)->increment('quantity', $data['quantity']);
            } else {
                DB::table('inventory')->insert(['branch_id' => $data['branch_id'], 'ingredient_id' => $data['ingredient_id'], 'quantity' => $data['quantity'], 'created_at' => now(), 'updated_at' => now()]);
            }
            DB::table('restock_items')->insert(['restock_id' => $restockId, 'ingredient_id' => $data['ingredient_id'], 'quantity' => $data['quantity'], 'unit_cost' => $data['unit_cost'] ?? 0, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('inventory_transactions')->insert([
                'branch_id' => $data['branch_id'], 'ingredient_id' => $data['ingredient_id'], 'user_id' => $request->user()->id,
                'type' => 'restock', 'quantity' => $data['quantity'], 'unit_cost' => $data['unit_cost'] ?? 0, 'remarks' => $data['remarks'] ?? null,
                'reference' => 'RS-'.$restockId, 'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        return back()->with('success', 'Stock updated and movement recorded.');
    }

    public function createIngredient(Request $request)
    {
        abort_unless($request->user()->role === 'administrator', 403);
        $data = $request->validate(['name' => 'required|string|max:255|unique:ingredients,name', 'unit' => 'required|string|max:32', 'minimum_stock' => 'required|numeric|min:0']);
        DB::table('ingredients')->insert($data + ['created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Ingredient added to the catalog.');
    }

    public function saveRecipe(Request $request, string $product)
    {
        abort_unless($request->user()->role === 'administrator', 403);
        abort_unless(DB::table('products')->where('id', $product)->exists(), 404);
        $data = $request->validate(['ingredients' => 'present|array', 'ingredients.*.ingredient_id' => 'required|exists:ingredients,id', 'ingredients.*.quantity' => 'required|numeric|gt:0']);
        DB::transaction(function () use ($product, $data): void {
            DB::table('product_recipes')->where('product_id', $product)->delete();
            foreach ($data['ingredients'] as $row) {
                DB::table('product_recipes')->insert(['product_id' => $product, 'ingredient_id' => $row['ingredient_id'], 'quantity' => $row['quantity'], 'created_at' => now(), 'updated_at' => now()]);
            }
        });

        return back()->with('success', 'Recipe saved.');
    }

    public function storeSupplier(Request $request)
    {
        abort_unless($request->user()->role === 'administrator' || $request->user()->role === 'inventory_staff', 403);
        $data = $request->validate(['name' => 'required|string|max:255', 'phone' => 'nullable|string|max:32', 'email' => 'nullable|email|max:255', 'address' => 'nullable|string|max:255']);
        DB::table('suppliers')->insert($data + ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Supplier added.');
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
        DB::table('payment_verifications')->insert([
            'payment_id' => $payment->id, 'reference_number' => $data['reference_number'], 'status' => 'pending',
            'submitted_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', 'GCash reference submitted for manual verification.');
    }

    public function reviewPaymentReference(Request $request, int $verification)
    {
        abort_unless(in_array($request->user()->role, ['administrator', 'branch_manager']), 403);
        $data = $request->validate(['decision' => 'required|in:approve,reject', 'review_note' => 'nullable|string|max:500']);

        DB::transaction(function () use ($request, $verification, $data): void {
            $record = DB::table('payment_verifications')->where('id', $verification)->lockForUpdate()->first();
            abort_unless($record && $record->status === 'pending', 404);
            $payment = Payment::with('order')->lockForUpdate()->findOrFail($record->payment_id);
            $this->authorizeBranch($request, $payment->order);
            $approved = $data['decision'] === 'approve';
            DB::table('payment_verifications')->where('id', $record->id)->update([
                'status' => $approved ? 'approved' : 'rejected', 'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(), 'review_note' => $data['review_note'] ?? null, 'updated_at' => now(),
            ]);
            if ($approved) {
                $payment->update(['status' => 'completed', 'paid_at' => now(), 'verified_at' => now(), 'verified_by' => $request->user()->id]);
            } else {
                $payment->update(['status' => 'failed']);
            }
        });

        return back()->with('success', 'Payment verification reviewed.');
    }

    private function authorizeBranch(Request $request, Order $order): void
    {
        abort_unless($request->user()->role === 'administrator' || $request->user()->branches()->whereKey($order->branch_id)->exists(), 403);
    }

    private function authorizeRoles(Request $request, array $roles): void
    {
        abort_unless(in_array($request->user()->role, $roles, true), 403);
    }

    private function serializeOrders($orders)
    {
        return $orders->map(fn (Order $order) => [
            'id' => $order->id, 'number' => $order->order_number ?? '#'.$order->id, 'customer' => $order->customer_name ?: 'Walk-in',
            'branch' => $order->branch?->name ?? 'Unassigned', 'status' => $order->status, 'total' => (float) ($order->payment?->amount ?? 0),
            'created_at' => $order->created_at, 'items' => $order->orderItems->map(fn ($item) => ['name' => $item->product?->name ?? 'Removed item', 'quantity' => $item->quantity, 'notes' => $item->notes]),
        ])->values();
    }
}
