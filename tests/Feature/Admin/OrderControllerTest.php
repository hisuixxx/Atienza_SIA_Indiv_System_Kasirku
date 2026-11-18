<?php

use App\Models\Order;
use App\Models\OrderItems;
use App\Models\Branch;
use App\Models\Product;
use App\Models\User;

test('guests cannot access order management', function () {
    $this->get('/admin/orders')->assertRedirect('/login');
});

test('an Inertia navigation to the orders page gets a real Inertia response, not raw JSON', function () {
    $this->actingAs(User::factory()->admin()->create());

    // Mirrors the headers axios/Inertia's client actually sends on a visit,
    // including a matching asset version so the request isn't intercepted
    // as a stale-version conflict before reaching the controller.
    $version = file_exists($manifest = public_path('build/manifest.json'))
        ? hash_file('xxh128', $manifest)
        : null;

    $response = $this->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => $version,
        'X-Requested-With' => 'XMLHttpRequest',
    ])->get('/admin/orders');

    // assertInertia() only understands the full-page-load (view-based) shape,
    // not the XHR/partial-reload response this deliberately exercises — so
    // assert directly on the envelope Inertia's client actually expects.
    $response->assertOk();
    $response->assertHeader('X-Inertia', 'true');
    $response->assertJsonStructure(['component', 'props', 'url', 'version']);
    $response->assertJson(['component' => 'admin/orders/index']);
});

test('order list shows the frozen item price, not the current product price', function () {
    $this->actingAs(User::factory()->admin()->create());
    $product = Product::factory()->create(['price' => 10000]);
    $order = Order::factory()->create();
    OrderItems::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 10000,
        'subtotal' => 20000,
    ]);

    $product->update(['price' => 99999]);

    $response = $this->get('/admin/orders');

    $response->assertInertia(fn ($page) => $page
        ->where('orders.data.0.order_items.0.price', 10000)
        ->where('orders.data.0.order_items.0.subtotal', 20000)
    );
});

test('admin can create a cash order with correct tax calculation', function () {
    $this->actingAs(User::factory()->admin()->create());
    $product = Product::factory()->create(['price' => 15000]);

    $response = $this->postJson('/admin/orders', [
        'customer_name' => 'Siti',
        'table_number' => 3,
        'status' => 'completed',
        'payment_method' => 'cash',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 3],
        ],
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $order = Order::where('customer_name', 'Siti')->firstOrFail();
    $subtotal = 15000 * 3;
    $expectedTotal = $subtotal + $subtotal * config('pos.tax_rate');

    expect((float) $order->payment->amount)->toBe((float) $expectedTotal);
    expect($order->payment->status)->toBe('completed');
    expect($order->payment->payment_method)->toBe('cash');
    expect($order->orderItems)->toHaveCount(1);
    expect((float) $order->orderItems->first()->subtotal)->toBe((float) $subtotal);
});

test('assigned staff can create dine-in and take-out cash orders with multiple items and receipt data', function () {
    $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN']);
    $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
    $staff->branches()->attach($branch->id);
    $latte = Product::factory()->create(['name' => 'House Latte', 'price' => 120]);
    $pastry = Product::factory()->create(['name' => 'Croissant', 'price' => 80]);

    $this->actingAs($staff)->postJson('/admin/orders', [
        'customer_name' => 'Dine-in Guest',
        'branch_id' => $branch->id,
        'service_type' => 'dine_in',
        'table_number' => 4,
        'payment_method' => 'cash',
        'items' => [
            ['product_id' => $latte->id, 'quantity' => 2],
            ['product_id' => $pastry->id, 'quantity' => 1],
        ],
    ])->assertOk()->assertJson(['success' => true]);

    $dineInOrder = Order::where('customer_name', 'Dine-in Guest')->firstOrFail();
    expect($dineInOrder->service_type)->toBe('dine_in')
        ->and($dineInOrder->table_number)->toBe(4)
        ->and($dineInOrder->orderItems)->toHaveCount(2)
        ->and($dineInOrder->payment->status)->toBe('completed');

    $receiptResponse = $this->get(route('admin.orders.print', $dineInOrder));
    $receiptResponse->assertRedirect();
    expect($receiptResponse->headers->get('Location'))->toContain('order_id='.$dineInOrder->id);

    $this->postJson('/admin/orders', [
        'customer_name' => 'Take-out Guest',
        'branch_id' => $branch->id,
        'service_type' => 'takeout',
        'payment_method' => 'cash',
        'items' => [
            ['product_id' => $latte->id, 'quantity' => 1],
            ['product_id' => $pastry->id, 'quantity' => 2],
        ],
    ])->assertOk()->assertJson(['success' => true]);

    $takeOutOrder = Order::where('customer_name', 'Take-out Guest')->firstOrFail();
    expect($takeOutOrder->service_type)->toBe('takeout')
        ->and($takeOutOrder->orderItems)->toHaveCount(2)
        ->and($takeOutOrder->payment->payment_method)->toBe('cash');
});

test('POS rejects a product unavailable at the submitted branch', function () {
    $this->actingAs(User::factory()->admin()->create());
    $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN']);
    $product = Product::factory()->create();
    $product->branches()->attach($branch->id, ['is_available' => false]);

    $this->postJson('/admin/orders', [
        'customer_name' => 'Walk-in',
        'branch_id' => $branch->id,
        'payment_method' => 'cash',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertUnprocessable()->assertJsonValidationErrors('items');

    $this->assertDatabaseCount('orders', 0);
});

test('POS accepts a product explicitly available at a branch', function () {
    $this->actingAs(User::factory()->admin()->create());
    $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN']);
    $product = Product::factory()->create(['is_available' => false]);
    $product->branches()->attach($branch->id, ['is_available' => true]);

    $this->postJson('/admin/orders', [
        'customer_name' => 'Walk-in',
        'branch_id' => $branch->id,
        'payment_method' => 'cash',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertOk()->assertJson(['success' => true]);

    $this->assertDatabaseHas('orders', ['customer_name' => 'Walk-in', 'branch_id' => $branch->id]);
});

test('admin can update order status', function () {
    $this->actingAs(User::factory()->admin()->create());
    $order = Order::factory()->create(['status' => 'pending']);

    $response = $this->postJson("/admin/orders/{$order->id}/update-status", ['status' => 'preparing']);

    $response->assertOk();
    expect($order->fresh()->status)->toBe('preparing');
});

test('only cancelled orders can be deleted', function () {
    $this->actingAs(User::factory()->admin()->create());
    $pendingOrder = Order::factory()->create(['status' => 'pending']);

    $response = $this->from('/admin/orders')->delete("/admin/orders/{$pendingOrder->id}");

    $response->assertSessionHasErrors('error');
    $this->assertDatabaseHas('orders', ['id' => $pendingOrder->id]);
});

test('cancelled orders remain available as financial records', function () {
    $this->actingAs(User::factory()->admin()->create());
    $order = Order::factory()->create(['status' => 'cancelled']);

    $response = $this->delete("/admin/orders/{$order->id}");

    $response->assertSessionHasErrors('error');
    $this->assertDatabaseHas('orders', ['id' => $order->id]);
});
