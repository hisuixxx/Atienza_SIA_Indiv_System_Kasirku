<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItems;
use App\Models\Payment;
use App\Models\Product;
use App\Services\OrderStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OrderController extends Controller
{
    /**
     * Display a listing of orders.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdministrator = $this->isAdministrator($user);
        $branchId = $request->filled('branch_id') ? $request->integer('branch_id') : null;
        if ($branchId !== null) {
            if ($isAdministrator) {
                abort_unless(Branch::whereKey($branchId)->exists(), 404);
            } else {
                abort_unless($branchId && $user->branches()->whereKey($branchId)->exists(), 403);
            }
        }
        $branches = $isAdministrator
            ? Branch::orderBy('name')->get(['id', 'name', 'code'])
            : $user->branches()->orderBy('name')->get(['branches.id', 'branches.name', 'branches.code']);

        try {
            $query = Order::with(['orderItems.product.photos', 'payment', 'statusHistories.user', 'statusHistories.branch', 'cancellation'])
                ->orderBy('created_at', 'desc');
            if (! $isAdministrator) {
                $query->whereIn('branch_id', $user->branches()->select('branches.id'));
            }
            if ($branchId !== null) {
                $query->where('branch_id', $branchId);
            }

            // Filter by status if provided
            if ($request->has('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }

            // Search by customer name or order ID
            if ($request->has('search') && $request->search !== '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('id', 'like', "%{$search}%");
                });
            }

            $orders = $query->paginate(10)->through(function ($order) {
                return [
                    'id' => $order->id,
                    'customer_name' => $order->customer_name,
                    'customer_phone' => null, // Not stored in database
                    'customer_email' => null, // Not stored in database
                    'total_amount' => $order->payment->amount ?? 0,
                    'status' => $order->status,
                    'status_history' => $order->statusHistories->map(fn ($history) => [
                        'from_status' => $history->from_status,
                        'to_status' => $history->to_status,
                        'employee' => $history->user?->name ?? 'System',
                        'branch' => $history->branch?->name,
                        'notes' => $history->notes,
                        'created_at' => $history->created_at,
                    ]),
                    'cancellation' => $order->cancellation ? [
                        'reason' => $order->cancellation->reason,
                        'cancelled_at' => $order->cancellation->cancelled_at,
                        'employee' => $order->cancellation->responsibleUser?->name,
                    ] : null,
                    'order_type' => 'admin', // Since these are admin-created orders
                    'notes' => null, // Not stored in database
                    'created_at' => $order->created_at,
                    'order_items' => $order->orderItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product' => [
                                'id' => $item->product->id,
                                'name' => $item->product->name,
                                'photos' => $item->product->photos->map(function ($photo) {
                                    return [
                                        'id' => $photo->id,
                                        'url' => $photo->url,
                                    ];
                                }),
                            ],
                            'quantity' => $item->quantity,
                            'price' => (float) $item->price,
                            'subtotal' => (float) $item->subtotal,
                        ];
                    }),
                    'payment' => [
                        'id' => $order->payment->id ?? 0,
                        'payment_method' => $order->payment->payment_method ?? 'cash',
                        'method' => $order->payment->payment_method ?? 'cash',
                        'status' => $order->payment->status ?? 'completed',
                        'amount' => $order->payment->amount ?? 0,
                        'transaction_id' => $order->payment->transaction_id ?? '',
                        'paid_at' => $order->payment->paid_at ?? null,
                    ],
                ];
            });

            $products = Product::with('photos')->get();

            // Return JSON for AJAX requests (infinite scroll). Inertia visits also
            // set X-Requested-With via axios, so exclude them explicitly or every
            // Inertia navigation to this page would get raw JSON instead of a
            // proper Inertia response.
            if (! $request->header('X-Inertia') && ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest')) {
                return response()->json([
                    'orders' => $orders,
                    'products' => $products,
                    'filters' => $request->only(['status', 'search']) + ['branch_id' => $branchId],
                ]);
            }

            return Inertia::render('admin/orders/index', [
                'orders' => $orders,
                'products' => $products,
                'branches' => $branches,
                'selectedBranch' => $branchId,
                'filters' => $request->only(['status', 'search']) + ['branch_id' => $branchId],
            ]);
        } catch (\Exception $e) {
            report($e);
            abort(500, 'Unable to load order history.');
        }
    }

    /**
     * Show the form for creating a new order.
     */
    public function create()
    {
        $user = request()->user();
        abort_unless(in_array($user->role, ['admin', 'administrator', 'branch_manager', 'cashier', 'staff'], true), 403);
        $branches = $this->isAdministrator($user) ? Branch::where('is_active', true)->orderBy('name')->get() : $user->branches()->where('is_active', true)->orderBy('name')->get();
        $requestedBranch = request()->filled('branch_id') ? request()->integer('branch_id') : null;
        abort_unless($requestedBranch === null || ($requestedBranch && $branches->contains('id', $requestedBranch)), 403);
        $selectedBranch = $requestedBranch ?? $branches->first()?->id;
        $products = Product::with(['category:id,name', 'photos:id,product_id,url,is_primary', 'branches:id'])->where('is_active', true)->orderBy('name')->get()->map(fn (Product $product) => [
            'id' => $product->id, 'name' => $product->name, 'description' => $product->description,
            'category' => $product->category?->name ?? 'Other', 'price' => (float) $product->price,
            'available' => $product->isAvailableAt($selectedBranch ? (int) $selectedBranch : null),
            'image' => $product->photos->firstWhere('is_primary', true)?->url ?? $product->photos->first()?->url,
        ]);

        return Inertia::render('admin/orders/create', [
            'products' => $products, 'categories' => Category::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'branches' => $branches, 'selectedBranch' => $selectedBranch, 'taxRate' => (float) config('pos.tax_rate'),
        ]);
    }

    /**
     * Store a newly created order in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'table_number' => 'required_if:service_type,dine_in|nullable|integer|min:1',
            'status' => 'nullable|in:pending,confirmed,preparing,ready,completed,cancelled',
            'branch_id' => 'nullable|exists:branches,id',
            'service_type' => 'nullable|in:dine_in,takeout',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string|max:255',
            'payment_method' => 'required|in:cash,digital,gcash,card,other',
        ]);

        $branchId = $request->input('branch_id');
        $branchId ??= $request->user()->branches()->value('branches.id');
        if (! $this->isAdministrator($request->user())) {
            abort_unless($branchId && $request->user()->branches()->whereKey($branchId)->exists(), 403);
        }
        abort_unless(! $branchId || Branch::whereKey($branchId)->where('is_active', true)->exists(), 422, 'Selected branch is inactive.');
        abort_unless(in_array($request->user()->role, ['admin', 'administrator', 'branch_manager', 'cashier', 'staff'], true), 403);

        DB::beginTransaction();

        try {
            // Calculate total amount
            $subtotalAmount = 0;
            $validatedItems = [];

            foreach ($request->items as $item) {
                $product = Product::whereKey($item['product_id'])->lockForUpdate()->first();
                if (! $product || ! $product->isAvailableAt($branchId ? (int) $branchId : null)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items' => ($product?->name ?? 'A selected product').' is unavailable at the selected branch.',
                    ]);
                }
                $subtotal = $product->price * $item['quantity'];
                $subtotalAmount += $subtotal;

                $validatedItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'subtotal' => $subtotal,
                    'notes' => $item['notes'] ?? null,
                ];
            }

            // Add tax
            $taxAmount = $subtotalAmount * config('pos.tax_rate');
            $totalAmount = $subtotalAmount + $taxAmount;

            // Create order
            $order = Order::create([
                'branch_id' => $branchId,
                'order_number' => $this->generateOrderNumber(),
                'created_by' => $request->user()->id,
                'customer_name' => $request->customer_name,
                'table_number' => $request->table_number ?? 0, // Default to 0 for admin orders
                'status' => 'pending',
                'service_type' => $request->service_type ?? 'takeout',
                'notes' => $request->notes,
            ]);
            app(OrderStatusService::class)->recordInitial($order, $request->user());

            // Create order items
            foreach ($validatedItems as $item) {
                OrderItems::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                    'notes' => $item['notes'],
                ]);
            }

            // Create payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'amount' => $totalAmount,
                'payment_method' => $request->payment_method,
                'status' => $request->payment_method === 'cash' ? 'completed' : 'pending',
                'payment_status' => $request->payment_method === 'cash' ? 'completed' : 'pending',
                'verification_status' => $request->payment_method === Payment::METHOD_GCASH ? Payment::VERIFICATION_PENDING : null,
                'transaction_id' => 'POS-'.strtoupper(bin2hex(random_bytes(6))),
                'paid_at' => $request->payment_method === 'cash' ? now() : null,
            ]);

            DB::commit();

            // Return JSON response for AJAX requests
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => 'Order berhasil dibuat!',
                    'order_id' => $order->id,
                ]);
            }

            // Return Inertia response for regular requests
            return redirect()->route('orders.index')->with('success', 'Order berhasil dibuat!');
        } catch (\Exception $e) {
            DB::rollback();
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                throw $e;
            }

            // Return JSON error for AJAX requests
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create order: '.$e->getMessage(),
                    'errors' => ['general' => $e->getMessage()],
                ], 422);
            }

            return redirect()->back()->withErrors(['error' => 'Failed to create order: '.$e->getMessage()]);
        }
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'B-'.random_int(1000, 9999);
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order)
    {
        $order->load(['orderItems.product.photos', 'payment']);

        return Inertia::render('admin/orders/show', [
            'order' => $order,
            'printReceipt' => session('print_receipt', false),
        ]);
    }

    /**
     * Show the form for editing the specified order.
     */
    public function edit(Order $order)
    {
        $order->load(['orderItems.product', 'payment']);
        $products = Product::with('photos')->get();

        return Inertia::render('admin/orders/edit', [
            'order' => $order,
            'products' => $products,
        ]);
    }

    /**
     * Update the specified order in storage.
     */
    public function update(Request $request, Order $order)
    {
        $this->authorizeOrderBranch($request, $order);
        $request->validate([
            'customer_name' => 'required|string|max:255',
        ]);

        $order->update([
            'customer_name' => $request->customer_name,
        ]);

        // Return JSON response for AJAX requests
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'message' => 'Order berhasil diupdate!',
                'order_id' => $order->id,
            ]);
        }

        return redirect()->route('orders.index')
            ->with('success', 'Order berhasil diupdate!');
    }

    /**
     * Remove the specified order from storage.
     */
    public function destroy(Request $request, Order $order)
    {
        $this->authorizeOrderBranch($request, $order);
        // Orders and their payment history are financial records. Use the cancellation
        // workflow to void an order; never cascade-delete a transaction trail.
        $message = 'Orders cannot be deleted. Submit a cancellation request instead.';

        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        return back()->withErrors(['error' => $message]);
    }

    /**
     * Print receipt for the order.
     */
    public function printReceipt(Request $request, Order $order)
    {
        $this->authorizeOrderBranch($request, $order);
        $order->load(['orderItems.product', 'payment']);

        // Prepare data for print endpoint
        $items = $order->orderItems->map(function ($item) {
            return [
                'name' => $item->product->name,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'subtotal' => $item->subtotal,
            ];
        });

        $paymentMethod = $order->payment?->payment_method ?? $order->payment?->method ?? 'cash';

        $printData = [
            'customer_name' => $order->customer_name,
            'customer_phone' => null,
            'items' => $items,
            'total_amount' => $order->total_amount,
            'payment_method' => $paymentMethod,
            'order_date' => $order->created_at->format('d/m/Y H:i'),
            'order_id' => $order->id,
        ];

        // Redirect to print endpoint with query parameters
        return redirect()->route('print.index', $printData);
    }

    /**
     * Update order status.
     */
    public function updateStatus(Request $request, Order $order)
    {
        $this->authorizeOrderBranch($request, $order);
        $request->validate([
            'status' => 'required|in:confirmed,preparing,ready',
        ]);

        abort_unless(in_array($request->status, ['confirmed', 'preparing', 'ready'], true), 422, 'Invalid order status transition.');
        abort_unless(in_array($request->user()->role, ['admin', 'administrator', 'branch_manager', 'kitchen_staff', 'kitchen'], true), 403);

        DB::transaction(function () use ($request, $order): void {
            $lockedOrder = Order::lockForUpdate()->findOrFail($order->id);
            app(OrderStatusService::class)->transition($lockedOrder, $request->status, $request->user());
        });

        return response()->json([
            'success' => true,
            'message' => 'Status order berhasil diupdate!',
        ]);
    }

    private function authorizeOrderBranch(Request $request, Order $order): void
    {
        abort_unless($this->isAdministrator($request->user()) || $request->user()->branches()->whereKey($order->branch_id)->exists(), 403);
    }

    private function isAdministrator($user): bool
    {
        return in_array($user->role, ['admin', 'administrator'], true);
    }
}
