<?php

use App\Models\Branch;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('kitchen transitions are recorded and cannot skip preparation', function () {
    $branch = Branch::create(['name' => 'Roxas', 'code' => 'ROX']);
    $staff = User::factory()->create(['role' => 'kitchen_staff']);
    $staff->branches()->attach($branch->id);
    $order = Order::factory()->create(['branch_id' => $branch->id, 'status' => 'pending']);
    $this->actingAs($staff);

    $this->patch(route('admin.kitchen.update', $order), ['status' => 'ready'])->assertStatus(422);
    $this->patch(route('admin.kitchen.update', $order), ['status' => 'preparing'])->assertRedirect();
    $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'from_status' => 'pending', 'to_status' => 'preparing', 'user_id' => $staff->id]);
});

test('employees cannot update an order outside their assigned branches', function () {
    $assigned = Branch::create(['name' => 'Roxas', 'code' => 'ROX']);
    $other = Branch::create(['name' => 'Main', 'code' => 'MAIN']);
    $staff = User::factory()->create(['role' => 'kitchen_staff']);
    $staff->branches()->attach($assigned->id);
    $order = Order::factory()->create(['branch_id' => $other->id, 'status' => 'pending']);

    $this->actingAs($staff)->patch(route('admin.kitchen.update', $order), ['status' => 'preparing'])->assertForbidden();
});

test('cashier can place a branch order with a public order number', function () {
    $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN']);
    $cashier = User::factory()->create(['role' => 'cashier']);
    $cashier->branches()->attach($branch->id);
    $product = Product::factory()->create(['price' => 125]);

    $response = $this->actingAs($cashier)->post('/admin/orders', [
        'customer_name' => 'Walk-in', 'branch_id' => $branch->id, 'service_type' => 'takeout', 'payment_method' => 'cash',
        'items' => [['product_id' => $product->id, 'quantity' => 1, 'notes' => 'No sugar']],
    ]);

    $response->assertRedirect(route('orders.index'));
    $order = Order::where('customer_name', 'Walk-in')->firstOrFail();
    expect($order->order_number)->toMatch('/^B-\d{4}$/');
    expect($order->branch_id)->toBe($branch->id);
    expect($order->orderItems->first()->notes)->toBe('No sugar');
    expect($order->payment->status)->toBe('completed');
});

test('handoff completes only paid ready orders and records the employee', function () {
    $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN']);
    $cashier = User::factory()->create(['role' => 'cashier']);
    $cashier->branches()->attach($branch->id);
    $order = Order::factory()->create(['branch_id' => $branch->id, 'status' => 'ready']);
    $payment = Payment::factory()->create(['order_id' => $order->id, 'status' => 'completed']);

    $this->actingAs($cashier)->post(route('admin.handoff.confirm', $order))->assertRedirect();
    expect($order->fresh()->status)->toBe('completed');
    $this->assertDatabaseHas('handoffs', ['order_id' => $order->id, 'user_id' => $cashier->id]);
});

test('orders do not depend on recipe or inventory records and inventory management is disabled', function () {
    $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN']);
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create();
    $this->actingAs($admin)->postJson('/admin/orders', [
        'customer_name' => 'Walk-in',
        'branch_id' => $branch->id,
        'payment_method' => 'cash',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertOk()->assertJson(['success' => true]);

    $order = Order::where('customer_name', 'Walk-in')->firstOrFail();
    $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'product_id' => $product->id]);
    $this->assertDatabaseCount('inventory_transactions', 0);
    $this->get('/admin/orders')->assertInertia(fn ($page) => $page
        ->where('orders.data.0.id', $order->id)
        ->where('orders.data.0.order_items.0.product.id', $product->id)
    );
    $this->get('/admin/inventory')->assertNotFound();
    $this->post('/admin/inventory/restock')->assertNotFound();
});

test('sales report subtracts processed refunds from collections', function () {
    $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN']);
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create(['branch_id' => $branch->id, 'order_number' => 'B-7621']);
    $payment = Payment::factory()->create(['order_id' => $order->id, 'amount' => 1000, 'payment_method' => 'cash', 'status' => 'completed', 'paid_at' => now()]);
    DB::table('refunds')->insert(['payment_id' => $payment->id, 'amount' => 250, 'reason' => 'Partial refund', 'status' => 'processed_manual', 'processed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

    $this->actingAs($admin)->get('/admin/reports')->assertInertia(fn ($page) => $page
        ->where('summary.gross', 1000)
        ->where('summary.refunds', 250)
        ->where('summary.net', 750)
        ->where('breakdown.0.net', 750)
    );
});
