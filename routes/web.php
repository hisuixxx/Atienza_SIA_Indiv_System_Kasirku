<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\Customer\CheckoutController as CustomerCheckoutController;
use App\Http\Controllers\Customer\CartController as CustomerCartController;
use App\Http\Controllers\Customer\CustomerOrderController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OperationsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Staff\StaffDashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\UserController;

/*
|--------------------------------------------------------------------------
| Web Routes - BrewSync Coffee Shop Management & Ordering
|--------------------------------------------------------------------------
*/

// Public Storefront & Customer Menu
Route::get('/', function () {
    return Inertia::render('customer/landing');
})->name('home');

Route::get('/menu', [CustomerController::class, 'index'])->name('menu');
Route::get('/menu/{product:slug}', [CustomerController::class, 'show'])->name('menu.show');

Route::middleware('auth')->prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CustomerCartController::class, 'index'])->name('index');
    Route::post('/{product}', [CustomerCartController::class, 'add'])->name('add');
    Route::patch('/{cartItem}', [CustomerCartController::class, 'update'])->name('update');
    Route::delete('/{cartItem}', [CustomerCartController::class, 'destroy'])->name('destroy');
});

// Checkout routes
Route::prefix('checkout')->group(function () {
    Route::get('/', [CustomerCheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/', [CustomerCheckoutController::class, 'process'])->name('checkout.process');
    Route::post('/process', [CheckoutController::class, 'processCheckout'])->name('checkout.pos.process');
    Route::get('/finish', [CheckoutController::class, 'paymentFinish'])->name('checkout.finish');
    Route::get('/unfinish', [CheckoutController::class, 'paymentUnfinish'])->name('checkout.unfinish');
    Route::get('/error', [CheckoutController::class, 'paymentError'])->name('checkout.error');
    Route::post('/notification', [CheckoutController::class, 'paymentNotification'])->name('checkout.notification');
});

Route::middleware('auth')->group(function () {
    Route::get('/orders', [CustomerOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{orderNumber}', [CustomerOrderController::class, 'show'])->name('orders.show');
});

Route::get('/staff/dashboard', [StaffDashboardController::class, 'index'])
    ->middleware(['auth', 'active', 'role:staff'])
    ->name('staff.dashboard');

// Order routes
Route::prefix('order')->group(function () {
    Route::get('/{orderId}/status', [CheckoutController::class, 'orderStatus'])->name('order.status');
    Route::get('/{orderId}/check', [CheckoutController::class, 'checkOrderStatus'])->name('order.check');
});

// =====================================================
// ADMIN ROUTES (Admin only)
// =====================================================
Route::prefix('admin')
    ->middleware(['auth', 'active', 'role:admin'])
    ->group(function () {
        // User Management
       Route::get('users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::post('users', [AdminUserController::class, 'store'])->name('admin.users.store');
        Route::put('users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
        Route::post('users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('admin.users.toggle-status');
        Route::post('users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('admin.users.reset-password');
        Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');

        // Products & Categories
        Route::resource('categories', CategoryController::class);
        Route::resource('products', ProductController::class);
        Route::patch('products/{product}/availability', [ProductController::class, 'updateAvailability'])->name('products.availability');

        // Reports
        Route::get('reports', [OperationsController::class, 'reports'])->name('admin.reports');
        Route::get('reports/export', [OperationsController::class, 'exportReports'])->name('admin.reports.export');
    });

// =====================================================
// STAFF + ADMIN ROUTES (Cashier operations)
// =====================================================
Route::prefix('admin')
    ->middleware(['auth', 'active', 'role:admin,staff,administrator,branch_manager,cashier'])
    ->group(function () {
        // Orders & POS
        Route::get('orders', [OrderController::class, 'index'])->name('admin.orders.index');
        Route::get('orders/create', [OrderController::class, 'create'])->name('admin.orders.create');
        Route::post('orders', [OrderController::class, 'store'])->name('admin.orders.store');
        Route::put('orders/{order}', [OrderController::class, 'update'])->name('admin.orders.update');
        Route::delete('orders/{order}', [OrderController::class, 'destroy'])->name('admin.orders.destroy');
        Route::post('orders/{order}/update-status', [OrderController::class, 'updateStatus'])->name('admin.orders.update-status');
        Route::get('orders/{order}/print', [OrderController::class, 'printReceipt'])->name('admin.orders.print');

        // Payments
        Route::get('payments', [OperationsController::class, 'payments'])->name('admin.payments');
        Route::post('payments/{payment}/reference', [OperationsController::class, 'submitPaymentReference'])->name('admin.payments.reference');
        Route::post('payments/verifications/{verification}', [OperationsController::class, 'reviewPaymentReference'])->name('admin.payments.verification.decide');

        // Handoff / Pickup confirmation
        Route::get('handoff', [OperationsController::class, 'handoff'])->name('admin.handoff');
        Route::post('handoff/{order}', [OperationsController::class, 'confirmHandoff'])->name('admin.handoff.confirm');

        // Cancellations & Refunds
        Route::get('refunds', [OperationsController::class, 'refunds'])->name('admin.refunds');
        Route::post('orders/{order}/refund', [OperationsController::class, 'requestRefund'])->name('admin.orders.refund');
        Route::post('orders/{order}/cancellation', [OperationsController::class, 'requestCancellation'])->name('admin.orders.cancellation');
        Route::post('refunds/{refund}/review', [OperationsController::class, 'reviewRefund'])->name('admin.refunds.review');
        Route::post('refunds/{refund}/process', [OperationsController::class, 'markRefundProcessed'])->name('admin.refunds.process');
        Route::post('cancellations/{cancellation}/review', [OperationsController::class, 'reviewCancellation'])->name('admin.cancellations.review');
    });

// =====================================================
// KITCHEN + ADMIN ROUTES
// =====================================================
Route::prefix('admin')
    ->middleware(['auth', 'active', 'role:admin,kitchen,administrator,branch_manager,kitchen_staff'])
    ->group(function () {
        Route::get('kitchen', [OperationsController::class, 'kitchen'])->name('admin.kitchen');
        Route::patch('kitchen/{order}', [OperationsController::class, 'updateKitchen'])->name('admin.kitchen.update');
    });

// =====================================================
// SHARED (Admin + Staff + Kitchen)
// =====================================================
Route::prefix('admin')
    ->middleware(['auth', 'active', 'role:admin,staff,kitchen,administrator,branch_manager,cashier,kitchen_staff'])
    ->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
        Route::post('print', [PrintController::class, 'index'])->name('print.index');
    });

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
