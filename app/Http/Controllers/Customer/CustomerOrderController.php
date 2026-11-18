<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerOrderController extends Controller
{
    /**
     * Display a list of the customer's orders.
     */
    public function index(): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please log in to view your order history.');
        }

        $orders = Order::where('user_id', Auth::id())
            ->with(['items', 'payment'])
            ->latest()
            ->paginate(10);

        return view('customer.orders.index', compact('orders'));
    }

    /**
     * Display the order details and real-time tracking timeline.
     */
    public function show(string $orderNumber): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please log in to track your order.');
        }

        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', Auth::id())
            ->with(['items.product', 'payment', 'statusHistories.user'])
            ->firstOrFail();

        // Calculate progress steps for the visual timeline
        $isDelivery = $order->order_type === 'delivery';

        $steps = [
            'pending' => ['label' => 'Order Received', 'icon' => 'fa-receipt', 'desc' => 'Waiting for kitchen confirmation'],
            'confirmed' => ['label' => 'Confirmed', 'icon' => 'fa-check', 'desc' => 'Order accepted by barista'],
            'preparing' => ['label' => 'Brewing & Preparing', 'icon' => 'fa-mug-hot', 'desc' => 'Barista is crafting your beverage'],
            'ready' => [
                'label' => $isDelivery ? 'Ready for Dispatch' : 'Ready for Pickup',
                'icon' => $isDelivery ? 'fa-box' : 'fa-bell-concierge',
                'desc' => $isDelivery ? 'Your order is ready for delivery' : 'Your order is ready at the counter',
            ],
        ];
        if ($isDelivery) {
            $steps['out_for_delivery'] = ['label' => 'Out for Delivery', 'icon' => 'fa-motorcycle', 'desc' => 'Rider is on the way'];
        }
        $steps['completed'] = ['label' => 'Completed', 'icon' => 'fa-circle-check', 'desc' => 'Enjoy your BrewSync coffee!'];

        return view('customer.orders.show', compact('order', 'steps'));
    }
}
