<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\CateringRequest;
use App\Models\DeliveryRequest;
use App\Models\Order;
use App\Models\Product;
use Illuminate\View\View;

class StaffDashboardController extends Controller
{
    public function index(): View
    {
        $activeOrders = Order::with(['items', 'payment'])
            ->whereIn('order_status', [
                Order::STATUS_PENDING,
                Order::STATUS_CONFIRMED,
                Order::STATUS_PREPARING,
                Order::STATUS_READY_FOR_PICKUP,
                Order::STATUS_OUT_FOR_DELIVERY
            ])
            ->latest()
            ->get();

        $recentCatering = CateringRequest::latest()->take(5)->get();
        $recentDeliveries = DeliveryRequest::latest()->take(5)->get();
        $products = Product::with('category')->latest()->take(10)->get();

        return view('staff.dashboard', compact('activeOrders', 'recentCatering', 'recentDeliveries', 'products'));
    }
}
