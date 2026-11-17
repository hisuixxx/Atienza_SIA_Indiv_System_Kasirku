<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OperationsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

// Checkout routes
Route::prefix('checkout')->group(function () {
    Route::get('/', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/', [CheckoutController::class, 'process'])->name('checkout.process');
    Route::get('/finish', [CheckoutController::class, 'paymentFinish'])->name('checkout.finish');
    Route::get('/unfinish', [CheckoutController::class, 'paymentUnfinish'])->name('checkout.unfinish');
    Route::get('/error', [CheckoutController::class, 'paymentError'])->name('checkout.error');
    Route::post('/notification', [CheckoutController::class, 'paymentNotification'])->name('checkout.notification');
});

// Order routes
Route::prefix('order')->group(function () {
    Route::get('/{orderId}/status', [CheckoutController::class, 'orderStatus'])->name('order.status');
    Route::get('/{orderId}/check', [CheckoutController::class, 'checkOrderStatus'])->name('order.check');
});

// Admin & Staff Operations
Route::prefix('admin')->middleware(['auth', 'active', 'role:admin,staff,kitchen'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    Route::post('print', [PrintController::class, 'index'])->name('print.index');

    // Resource routes
    Route::resource('categories', CategoryController::class);
    Route::resource('products', ProductController::class);

    // Orders & POS Cashier routes
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
    Route::put('orders/{order}', [OrderController::class, 'update'])->name('orders.update');
    Route::delete('orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');
    Route::post('orders/{order}/update-status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::get('orders/{order}/print', [OrderController::class, 'printReceipt'])->name('orders.print');

    // Kitchen Queue
    Route::get('kitchen', [OperationsController::class, 'kitchen'])->name('admin.kitchen');
    Route::patch('kitchen/{order}', [OperationsController::class, 'updateKitchen'])->name('admin.kitchen.update');

    // Handoff
    Route::get('handoff', [OperationsController::class, 'handoff'])->name('admin.handoff');
    Route::post('handoff/{order}', [OperationsController::class, 'confirmHandoff'])->name('admin.handoff.confirm');

    // Payments & Digital Ledger
    Route::get('payments', [OperationsController::class, 'payments'])->name('admin.payments');
    Route::post('payments/{payment}/reference', [OperationsController::class, 'submitPaymentReference'])->name('admin.payments.reference');
    Route::post('payments/verifications/{verification}', [OperationsController::class, 'decideVerification'])->name('admin.payments.verification.decide');


    // User Management (Admin only)
    Route::get('users', [App\Http\Controllers\Admin\UserController::class, 'index'])->name('admin.users.index');
    Route::post('users', [App\Http\Controllers\Admin\UserController::class, 'store'])->name('admin.users.store');
    Route::put('users/{user}', [App\Http\Controllers\Admin\UserController::class, 'update'])->name('admin.users.update');
    Route::post('users/{user}/toggle-status', [App\Http\Controllers\Admin\UserController::class, 'toggleStatus'])->name('admin.users.toggle-status');
    Route::post('users/{user}/reset-password', [App\Http\Controllers\Admin\UserController::class, 'resetPassword'])->name('admin.users.reset-password');
    Route::delete('users/{user}', [App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('admin.users.destroy');



    // Reports & CSV Export
    Route::get('reports', [OperationsController::class, 'reports'])->name('admin.reports');
    Route::get('reports/export', [OperationsController::class, 'exportReports'])->name('admin.reports.export');

    // Cancellations & Refunds
    Route::get('refunds', [OperationsController::class, 'refunds'])->name('admin.refunds');
    Route::post('orders/{order}/refund', [OperationsController::class, 'requestRefund'])->name('admin.orders.refund');
    Route::post('orders/{order}/cancellation', [OperationsController::class, 'requestCancellation'])->name('admin.orders.cancellation');
    Route::post('refunds/{refund}/review', [OperationsController::class, 'reviewRefund'])->name('admin.refunds.review');
    Route::post('refunds/{refund}/process', [OperationsController::class, 'processRefund'])->name('admin.refunds.process');
    Route::post('cancellations/{cancellation}/review', [OperationsController::class, 'reviewCancellation'])->name('admin.cancellations.review');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
