<?php

use App\Models\Branch;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\UserSeeder;
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

test('canonical kitchen users see and update only orders from assigned branches', function () {
    $assigned = Branch::create(['name' => 'Main', 'code' => 'MAIN']);
    $other = Branch::create(['name' => 'Roxas', 'code' => 'ROX']);
    $kitchen = User::factory()->create(['role' => User::ROLE_KITCHEN]);
    $kitchen->branches()->attach($assigned->id);
    $assignedOrder = Order::factory()->create(['branch_id' => $assigned->id, 'status' => 'pending']);
    Order::factory()->create(['branch_id' => $other->id, 'status' => 'pending']);

    $this->actingAs($kitchen)->get('/admin/kitchen')->assertInertia(fn ($page) => $page
        ->component('admin/operations/index')
        ->has('orders', 1)
        ->where('orders.0.id', $assignedOrder->id)
    );
    $this->get('/admin/kitchen?branch_id='.$other->id)->assertForbidden();
    $this->get('/admin/dashboard')->assertForbidden();
    $this->patch(route('admin.kitchen.update', $assignedOrder), ['status' => 'confirmed'])->assertRedirect();
});

test('admin branch management edits branch details and only assigns staff and kitchen users', function () {
    $admin = User::factory()->admin()->create();
    $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN']);
    $secondBranch = Branch::create(['name' => 'Roxas', 'code' => 'ROX']);
    $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
    $kitchen = User::factory()->create(['role' => User::ROLE_KITCHEN]);
    $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
    $branch->users()->attach($admin->id);

    $this->actingAs($admin)->get('/admin/branches')->assertInertia(fn ($page) => $page
        ->component('admin/operations/index')
        ->where('module', 'branches')
        ->has('branches', 2)
        ->has('employees', 2)
    );

    $this->post('/admin/branches', ['name' => 'Pinamalayan', 'code' => 'pin', 'address' => 'Town proper'])
        ->assertRedirect();
    $this->assertDatabaseHas('branches', ['name' => 'Pinamalayan', 'code' => 'PIN']);

    $this->put('/admin/branches/'.$branch->id, [
        'name' => 'Bongabong Main Branch',
        'code' => 'MAIN',
        'address' => 'Bongabong',
        'is_active' => true,
        'employee_ids' => [$staff->id, $kitchen->id],
    ])->assertRedirect();
    $this->assertDatabaseHas('branches', ['id' => $branch->id, 'name' => 'Bongabong Main Branch']);
    expect($branch->fresh()->users->modelKeys())->toContain($admin->id, $staff->id, $kitchen->id);

    $this->putJson('/admin/branches/'.$branch->id, [
        'name' => 'Bongabong Main Branch',
        'code' => 'MAIN',
        'address' => 'Bongabong',
        'is_active' => true,
        'employee_ids' => [$customer->id],
    ])->assertUnprocessable();

    $this->actingAs($staff)->get('/admin/branches')->assertForbidden();
    expect($secondBranch->fresh()->is_active)->toBeTrue();
});

test('branch seeding reuses exactly the four canonical locations', function () {
    $this->seed(UserSeeder::class);
    $this->seed(UserSeeder::class);

    $this->assertDatabaseCount('branches', 4);
    $this->assertDatabaseHas('branches', ['code' => 'MAIN', 'name' => 'Bongabong Main Branch – Headquarters']);
    $this->assertDatabaseHas('branches', ['code' => 'ROX', 'name' => 'Roxas Branch']);
    $this->assertDatabaseHas('branches', ['code' => 'PIN', 'name' => 'Pinamalayan Branch']);
    $this->assertDatabaseHas('branches', ['code' => 'PG', 'name' => 'Puerto Galera Branch']);
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

test('canonical staff can create POS orders only for an assigned active branch', function () {
    $assigned = Branch::create(['name' => 'Main', 'code' => 'MAIN']);
    $other = Branch::create(['name' => 'Roxas', 'code' => 'ROX']);
    $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
    $staff->branches()->attach($assigned->id);
    $product = Product::factory()->create();
    $this->actingAs($staff);

    $payload = [
        'customer_name' => 'Walk-in',
        'payment_method' => 'cash',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ];
    $this->postJson('/admin/orders', $payload + ['branch_id' => $other->id])->assertForbidden();
    $this->assertDatabaseCount('orders', 0);

    $this->postJson('/admin/orders', $payload + ['branch_id' => $assigned->id])
        ->assertOk()->assertJson(['success' => true]);
    $this->assertDatabaseHas('orders', ['customer_name' => 'Walk-in', 'branch_id' => $assigned->id]);
});

test('handoff completes only paid ready orders and records the employee', function () {
    $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN']);
    $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
    $staff->branches()->attach($branch->id);
    $order = Order::factory()->create(['branch_id' => $branch->id, 'status' => 'ready']);
    Payment::factory()->create(['order_id' => $order->id, 'status' => 'completed']);

    $this->actingAs($staff)->post(route('admin.handoff.confirm', $order))->assertRedirect();
    expect($order->fresh()->status)->toBe('completed');
    expect($order->fresh()->order_status)->toBe('completed');
    $this->assertDatabaseHas('handoffs', ['order_id' => $order->id, 'user_id' => $staff->id]);
});

test('assigned staff dispatches and confirms a paid delivery order through completion', function () {
    $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN']);
    $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
    $staff->branches()->attach($branch->id);
    $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
    $order = Order::factory()->create([
        'branch_id' => $branch->id,
        'user_id' => $customer->id,
        'order_type' => 'delivery',
        'status' => 'ready',
        'order_status' => 'ready',
    ]);
    Payment::factory()->create(['order_id' => $order->id, 'status' => 'completed']);

    $this->actingAs($customer)->get(route('orders.show', $order->order_number))->assertSee('Ready for Dispatch');

    $this->actingAs($staff)->post(route('admin.handoff.confirm', $order))->assertStatus(422);
    $this->actingAs($staff)->post(route('admin.handoff.dispatch', $order))->assertRedirect();
    expect($order->fresh()->status)->toBe('out_for_delivery')
        ->and($order->fresh()->order_status)->toBe('out_for_delivery');
    $this->assertDatabaseHas('order_status_histories', [
        'order_id' => $order->id,
        'from_status' => 'ready',
        'to_status' => 'out_for_delivery',
        'user_id' => $staff->id,
    ]);
    $this->actingAs($customer)->get(route('orders.show', $order->order_number))->assertSee('Out for Delivery');

    $this->actingAs($staff);
    $this->post(route('admin.handoff.confirm', $order))->assertRedirect();
    expect($order->fresh()->status)->toBe('completed')
        ->and($order->fresh()->order_status)->toBe('completed');
    $this->assertDatabaseHas('handoffs', [
        'order_id' => $order->id,
        'user_id' => $staff->id,
        'notes' => 'Delivery confirmed.',
    ]);
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
