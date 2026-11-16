<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItems;
use App\Models\Payment;
use App\Models\Product;
use App\Services\InventoryService;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Midtrans\Notification;

class CheckoutController extends Controller
{
    public function __construct(private MidtransService $midtransService) {}

    public function index()
    {
        if (!auth()->check()) {
            return redirect()->guest(route('login', ['redirect' => '/checkout']));
        }

        return Inertia::render('customer/checkout/index');
    }

    public function processCheckout(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'table_number' => 'required|integer|min:1',
            'cart' => 'required|array|min:1',
            'cart.*.product.id' => 'required|exists:products,id',
            'cart.*.quantity' => 'required|integer|min:1',
            'cart.*.notes' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $branchId = Branch::where('is_active', true)->orderBy('id')->value('id');
            do {
                $orderNumber = 'B-'.random_int(1000, 9999);
            } while (Order::where('order_number', $orderNumber)->exists());

            // Create order
            $order = Order::create([
                'branch_id' => $branchId,
                'order_number' => $orderNumber,
                'created_by' => $request->user()?->id,
                'customer_name' => $request->customer_name,
                'table_number' => $request->table_number,
                'status' => 'pending',
            ]);
            DB::table('order_status_histories')->insert([
                'order_id' => $order->id, 'from_status' => null, 'to_status' => 'pending', 'user_id' => $request->user()?->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $subtotalAmount = 0;

            // Create order items and calculate subtotal
            foreach ($request->cart as $cartItem) {
                $product = Product::whereKey($cartItem['product']['id'])->where('is_active', true)->firstOrFail();
                $itemSubtotal = $product->price * $cartItem['quantity'];

                OrderItems::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $cartItem['quantity'],
                    'notes' => $cartItem['notes'] ?? null,
                    'price' => $product->price,
                    'subtotal' => $itemSubtotal,
                ]);

                $subtotalAmount += $itemSubtotal;
            }

            // app(InventoryService::class)->deductForOrder($order);

            // Calculate tax and total amount, rounded to whole IDR so the amount
            // charged via Midtrans matches what's stored/printed (no truncation).
            $taxAmount = round($subtotalAmount * config('pos.tax_rate'));
            $totalAmount = $subtotalAmount + $taxAmount;

            // Create payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'amount' => $totalAmount,
                'status' => 'pending',
                'payment_method' => 'midtrans',
            ]);

            // Generate unique order ID for Midtrans (format: KASIR-{timestamp}-{payment_id})
            $midtransOrderId = 'KASIR-'.time().'-'.$payment->id;

            // Prepare Midtrans transaction
            $params = [
                'transaction_details' => [
                    'order_id' => $midtransOrderId,
                    'gross_amount' => (int) $totalAmount,
                ],
                'customer_details' => [
                    'first_name' => $request->customer_name,
                    'email' => 'customer@kasirku.com',
                    'phone' => '08123456789',
                ],
                'item_details' => array_merge(
                    collect($request->cart)->map(function ($cartItem) {
                        $product = Product::find($cartItem['product']['id']);

                        return [
                            'id' => $product->id,
                            'price' => (int) $product->price,
                            'quantity' => $cartItem['quantity'],
                            'name' => $product->name,
                        ];
                    })->toArray(),
                    [
                        [
                            'id' => 'TAX',
                            'price' => (int) $taxAmount,
                            'quantity' => 1,
                            'name' => 'Pajak ('.(config('pos.tax_rate') * 100).'%)',
                        ],
                    ]
                ),
                'callbacks' => [
                    'finish' => route('checkout.finish'),
                    'unfinish' => route('checkout.unfinish'),
                    'error' => route('checkout.error'),
                ],
            ];

            $snapToken = $this->midtransService->createSnapToken($params);

            // Update payment with transaction ID from Midtrans
            $payment->update([
                'transaction_id' => $midtransOrderId,
            ]);

            DB::commit();

            return response()->json([
                'snap_token' => $snapToken,
                'order_id' => $order->id,
                'payment_id' => $payment->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof ValidationException) {
                throw $e;
            }

            return response()->json([
                'error' => 'An error occurred while processing the order: '.$e->getMessage(),
            ], 500);
        }
    }

    public function paymentFinish(Request $request)
    {
        $orderId = $request->query('order_id');
        $status = $request->query('transaction_status');

        return Inertia::render('customer/payment/finish', [
            'order_id' => $orderId,
            'status' => $status,
        ]);
    }

    public function paymentUnfinish(Request $request)
    {
        return Inertia::render('customer/payment/unfinish');
    }

    public function paymentError(Request $request)
    {
        return Inertia::render('customer/payment/error');
    }

    public function paymentNotification(Request $request)
    {
        try {
            $notification = new Notification;

            $this->applyTransactionStatus(
                $notification->transaction_status,
                $notification->payment_type,
                $notification->order_id,
                $notification->fraud_status
            );

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Apply a Midtrans transaction status to the matching payment/order.
     * Split out from paymentNotification() so the state-transition logic
     * can be tested without instantiating the real Midtrans SDK.
     */
    public function applyTransactionStatus(string $transactionStatus, ?string $paymentType, string $orderId, ?string $fraudStatus): void
    {
        $payment = Payment::where('transaction_id', $orderId)->firstOrFail();
        $order = $payment->order;

        if ($transactionStatus == 'capture') {
            if ($paymentType == 'credit_card') {
                if ($fraudStatus == 'challenge') {
                    $payment->update(['status' => 'pending']);
                } else {
                    $payment->update([
                        'status' => 'completed',
                        'paid_at' => now(),
                    ]);
                    $order->update(['status' => 'pending']);
                }
            }
        } elseif ($transactionStatus == 'settlement') {
            $payment->update([
                'status' => 'completed',
                'paid_at' => now(),
            ]);
            $order->update(['status' => 'pending']);
        } elseif ($transactionStatus == 'pending') {
            $payment->update(['status' => 'pending']);
        } elseif ($transactionStatus == 'deny') {
            $payment->update(['status' => 'failed']);
            $order->update(['status' => 'cancelled']);
        } elseif ($transactionStatus == 'expire') {
            $payment->update(['status' => 'failed']);
            $order->update(['status' => 'cancelled']);
        } elseif ($transactionStatus == 'cancel') {
            $payment->update(['status' => 'failed']);
            $order->update(['status' => 'cancelled']);
        }
    }

    public function orderStatus($orderId)
    {
        $order = Order::with(['orderItems.product.photos', 'payment'])
            ->findOrFail($orderId);

        return Inertia::render('customer/order/status', [
            'order' => $order,
        ]);
    }

    public function checkOrderStatus($orderId)
    {
        $order = Order::with(['orderItems.product.photos', 'payment'])
            ->findOrFail($orderId);

        return response()->json([
            'order' => $order,
        ]);
    }
}
