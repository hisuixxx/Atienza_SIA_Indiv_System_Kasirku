<?php

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItems;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    Carbon::setTestNow('2025-05-07 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

test('sales reports filter branch, period, payment method and order status without multiplying refunds', function () {
    $main = Branch::create(['name' => 'Main', 'code' => 'MAIN']);
    $roxas = Branch::create(['name' => 'Roxas', 'code' => 'ROXAS']);
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create(['name' => 'House Latte']);

    $mainOrder = Order::factory()->create([
        'branch_id' => $main->id,
        'status' => 'completed',
        'created_at' => '2025-05-06 10:00:00',
        'updated_at' => '2025-05-06 10:00:00',
    ]);
    OrderItems::factory()->create([
        'order_id' => $mainOrder->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 150,
        'subtotal' => 300,
    ]);
    $mainPayment = Payment::factory()->create([
        'order_id' => $mainOrder->id,
        'amount' => 300,
        'payment_method' => 'cash',
        'status' => 'completed',
        'paid_at' => '2025-05-06 11:00:00',
    ]);
    Payment::factory()->create([
        'order_id' => $mainOrder->id,
        'amount' => 300,
        'payment_method' => 'cash',
        'status' => 'completed',
        'paid_at' => '2025-05-06 11:05:00',
    ]);
    foreach ([40, 35] as $amount) {
        \Illuminate\Support\Facades\DB::table('refunds')->insert([
            'payment_id' => $mainPayment->id,
            'amount' => $amount,
            'reason' => 'Partial adjustment',
            'status' => 'processed_manual',
            'processed_at' => '2025-05-06 12:00:00',
            'created_at' => '2025-05-06 12:00:00',
            'updated_at' => '2025-05-06 12:00:00',
        ]);
    }
    \Illuminate\Support\Facades\DB::table('refunds')->insert([
        'payment_id' => $mainPayment->id,
        'amount' => 50,
        'reason' => 'Awaiting processing',
        'status' => 'approved_manual',
        'processed_at' => null,
        'created_at' => '2025-05-06 12:00:00',
        'updated_at' => '2025-05-06 12:00:00',
    ]);

    $otherOrder = Order::factory()->create([
        'branch_id' => $roxas->id,
        'status' => 'completed',
        'created_at' => '2025-05-06 10:00:00',
        'updated_at' => '2025-05-06 10:00:00',
    ]);
    Payment::factory()->create([
        'order_id' => $otherOrder->id,
        'amount' => 500,
        'payment_method' => 'gcash',
        'status' => 'completed',
        'paid_at' => '2025-05-06 11:00:00',
    ]);

    $pendingOrder = Order::factory()->create([
        'branch_id' => $main->id,
        'status' => 'completed',
        'created_at' => '2025-05-06 10:00:00',
        'updated_at' => '2025-05-06 10:00:00',
    ]);
    Payment::factory()->create([
        'order_id' => $pendingOrder->id,
        'amount' => 900,
        'payment_method' => 'gcash',
        'status' => 'pending',
        'paid_at' => null,
    ]);
    $rejectedOrder = Order::factory()->create([
        'branch_id' => $main->id,
        'status' => 'completed',
        'created_at' => '2025-05-06 10:00:00',
        'updated_at' => '2025-05-06 10:00:00',
    ]);
    Payment::factory()->create([
        'order_id' => $rejectedOrder->id,
        'amount' => 700,
        'payment_method' => 'cash',
        'status' => 'rejected',
        'paid_at' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.reports', [
            'period' => 'weekly',
            'date' => '2025-05-07',
            'payment_method' => 'cash',
            'order_status' => 'completed',
            'branch_id' => $main->id,
        ]))
        ->assertInertia(fn ($page) => $page
            ->where('summary.gross_sales', 300)
            ->where('summary.cash_sales', 300)
            ->where('summary.gcash_sales', 0)
            ->where('summary.refund_total', 75)
            ->where('summary.net_sales', 225)
            ->where('summary.completed_orders', 1)
            ->where('summary.cancelled_orders', 0)
            ->where('summary.average_order_value', 300)
            ->where('topProducts.0.product_name', 'House Latte')
            ->where('topProducts.0.quantity', 2)
            ->where('branchComparison.1.gross_sales', 0)
        );

    $this->get(route('admin.reports', [
        'period' => 'weekly',
        'date' => '2025-05-07',
        'order_status' => 'completed',
    ]))->assertInertia(fn ($page) => $page
        ->where('branchComparison.0.gross_sales', 300)
        ->where('branchComparison.1.gross_sales', 500)
    );
});

test('monthly and custom sales reports use the requested date boundaries', function () {
    $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN']);
    $admin = User::factory()->admin()->create();

    foreach ([
        ['2025-05-31 18:00:00', 100],
        ['2025-06-01 09:00:00', 200],
        ['2025-06-02 09:00:00', 300],
    ] as [$date, $amount]) {
        $order = Order::factory()->create([
            'branch_id' => $branch->id,
            'status' => 'completed',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => $amount,
            'payment_method' => 'cash',
            'status' => 'approved',
            'paid_at' => $date,
        ]);
    }

    $this->actingAs($admin)
        ->get(route('admin.reports', ['period' => 'monthly', 'date' => '2025-05-15']))
        ->assertInertia(fn ($page) => $page
            ->where('summary.gross_sales', 100)
            ->where('range.start_date', '2025-05-01')
            ->where('range.end_date', '2025-05-31')
        );

    $this->get(route('admin.reports', [
        'period' => 'custom',
        'start_date' => '2025-06-01',
        'end_date' => '2025-06-02',
    ]))->assertInertia(fn ($page) => $page
        ->where('summary.gross_sales', 500)
        ->where('summary.completed_orders', 2)
    );
});
