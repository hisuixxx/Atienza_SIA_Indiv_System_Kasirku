<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalSales = Order::where('order_status', Order::STATUS_COMPLETED)->sum('total_amount');
        $totalOrders = Order::count();
        $totalCustomers = User::where('role', User::ROLE_CUSTOMER)->count();
        $totalProducts = Product::count();
        $pendingOrders = Order::where('order_status', Order::STATUS_PENDING)->count();
        $completedOrders = Order::where('order_status', Order::STATUS_COMPLETED)->count();
        $pendingPayments = Payment::where('payment_status', Payment::STATUS_PENDING)->count();

        $recentOrders = Order::with(['user', 'payment'])->latest()->take(5)->get();

        return view('admin.dashboard', compact(
            'totalSales',
            'totalOrders',
            'totalCustomers',
            'totalProducts',
            'pendingOrders',
            'completedOrders',
            'pendingPayments',
            'recentOrders'
        ));
    }
}
