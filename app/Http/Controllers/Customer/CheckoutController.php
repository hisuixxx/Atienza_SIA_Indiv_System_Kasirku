<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Cart;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\StorefrontSetting;
use App\Services\OrderStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    /**
     * Display the checkout page.
     */
    public function index(): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->guest(route('login', ['redirect' => '/checkout']))
                ->with('error', 'Please log in to proceed to checkout.');
        }

        $cart = Cart::with(['items.product.category'])
            ->where('user_id', Auth::id())
            ->first();

        if (!$cart || $cart->items->count() === 0) {
            return redirect()->route('menu')->with('error', 'Your shopping cart is empty. Please select coffee products first.');
        }

        $settings = StorefrontSetting::all()->pluck('value', 'key');
        $user = Auth::user();

        return view('customer.checkout.index', compact('cart', 'settings', 'user'));
    }

    /**
     * Process checkout and create Order and Payment.
     */
    public function process(Request $request): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please sign in to complete your order.');
        }

        $cart = Cart::with(['items.product'])
            ->where('user_id', Auth::id())
            ->first();

        if (!$cart || $cart->items->count() === 0) {
            return redirect()->route('menu')->with('error', 'Your shopping cart is empty.');
        }

        // Validate checkout input
        $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'order_type' => ['required', 'in:pickup,delivery'],
            'delivery_address' => ['required_if:order_type,delivery', 'nullable', 'string', 'max:500'],
            'order_notes' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', 'in:gcash,cash_on_pickup,cash_on_delivery'],
            'gcash_reference_number' => ['required_if:payment_method,gcash', 'nullable', 'string', 'max:100'],
            'receipt_image' => ['required_if:payment_method,gcash', 'nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        return DB::transaction(function () use ($request, $cart) {
            // Calculate totals
            $subtotal = (float) $cart->items->sum('item_subtotal');
            $deliveryFee = ($request->order_type === 'delivery') ? 50.00 : 0.00;
            $totalAmount = $subtotal + $deliveryFee;

            // Generate unique human-readable order number (e.g. BS-20261115-A1B2)
            do {
                $orderNumber = 'BS-' . date('Ymd') . '-' . strtoupper(Str::random(4));
            } while (Order::where('order_number', $orderNumber)->exists());

            // 1. Create Order
            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => Auth::id(),
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'order_type' => $request->order_type,
                'delivery_address' => $request->order_type === 'delivery' ? $request->delivery_address : null,
                'order_notes' => $request->order_notes,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $totalAmount,
                'status' => Order::STATUS_PENDING,
                'order_status' => Order::STATUS_PENDING,
            ]);

            // 2. Create Order Items Snapshot
            foreach ($cart->items as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'product_name' => $cartItem->product->name,
                    'base_price' => $cartItem->product->base_price,
                    'unit_price' => $cartItem->unit_price,
                    'quantity' => $cartItem->quantity,
                    'customizations_summary' => $cartItem->customizations,
                    'special_instructions' => $cartItem->special_instructions,
                    'subtotal' => $cartItem->item_subtotal,
                ]);
            }

            // 3. Handle Payment and Receipt Upload
            $receiptPath = null;
            if ($request->hasFile('receipt_image')) {
                $receiptPath = $request->file('receipt_image')->store('receipts', 'public');
            }

            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_method' => $request->payment_method,
                'amount' => $totalAmount,
                'status' => Payment::STATUS_PENDING,
                'payment_status' => Payment::STATUS_PENDING, // Never automatically approve payments!
                'gcash_reference_number' => $request->gcash_reference_number,
                'receipt_image_path' => $receiptPath,
            ]);

            if ($request->payment_method === Payment::METHOD_GCASH) {
                DB::table('payment_verifications')->insert([
                    'payment_id' => $payment->id,
                    'reference_number' => $request->gcash_reference_number,
                    'status' => Payment::VERIFICATION_PENDING,
                    'submitted_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            app(OrderStatusService::class)->recordInitial(
                $order,
                Auth::user(),
                'Order submitted by customer with payment method: ' . strtoupper(str_replace('_', ' ', $request->payment_method))
            );

            // 5. Create Customer Notification
            Notification::create([
                'user_id' => Auth::id(),
                'title' => 'Order Placed: #' . $order->order_number,
                'message' => 'Thank you! Your order for ₱' . number_format($totalAmount, 2) . ' has been received and is queued for verification.',
                'link' => route('orders.show', $order->order_number),
            ]);

            // 6. Record Audit Log
            AuditLog::record(
                'order.created',
                'orders',
                $order->id,
                null,
                ['order_number' => $order->order_number, 'total_amount' => $totalAmount, 'method' => $request->payment_method]
            );

            // 7. Clear Shopping Cart
            $cart->items()->delete();

            return redirect()->route('orders.show', $order->order_number)
                ->with('success', 'Your order #' . $order->order_number . ' has been placed successfully! GCash payments are pending review.');
        });
    }
}
