<?php

use App\Models\Category;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItems;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;

test('guests cannot access the dashboard', function () {
    $this->get('/admin/dashboard')->assertRedirect('/login');
});

test('dashboard reports accurate revenue and order counts', function () {
    $this->actingAs(User::factory()->admin()->create());

    $product = Product::factory()->create(['price' => 10000]);

    $completedOrder = Order::factory()->create(['status' => 'completed']);
    OrderItems::factory()->create([
        'order_id' => $completedOrder->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 10000,
        'subtotal' => 20000,
    ]);
    Payment::factory()->completed()->create(['order_id' => $completedOrder->id, 'amount' => 22000]);

    $pendingOrder = Order::factory()->create(['status' => 'pending']);
    Payment::factory()->create(['order_id' => $pendingOrder->id, 'amount' => 11000]);

    $cancelledOrder = Order::factory()->create(['status' => 'cancelled']);
    Payment::factory()->failed()->create(['order_id' => $cancelledOrder->id, 'amount' => 5000]);

    $response = $this->get('/admin/dashboard');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/dashboard/index')
        ->where('stats.total_orders', 3)
        ->where('stats.pending_orders', 1)
        ->where('stats.completed_orders', 1)
        ->where('stats.cancelled_orders', 1)
        // Only the completed payment counts toward revenue.
        ->where('stats.total_revenue', 22000)
        ->where('pendingPayments', 1)
        ->where('stats.total_products', 1)
    );
});

test('dashboard ranks top products by quantity sold', function () {
    $this->actingAs(User::factory()->admin()->create());

    $popular = Product::factory()->create(['name' => 'Popular Item', 'price' => 5000]);
    $niche = Product::factory()->create(['name' => 'Niche Item', 'price' => 50000]);

    $order = Order::factory()->create();
    OrderItems::factory()->create([
        'order_id' => $order->id,
        'product_id' => $popular->id,
        'quantity' => 10,
        'price' => 5000,
        'subtotal' => 50000,
    ]);
    OrderItems::factory()->create([
        'order_id' => $order->id,
        'product_id' => $niche->id,
        'quantity' => 1,
        'price' => 50000,
        'subtotal' => 50000,
    ]);

    $response = $this->get('/admin/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->where('topProducts.0.name', 'Popular Item')
        ->where('topProducts.0.quantity_sold', 10)
        ->where('topProducts.1.name', 'Niche Item')
    );
});

test('dashboard lists recent orders with their payment total', function () {
    $this->actingAs(User::factory()->admin()->create());

    $order = Order::factory()->create(['customer_name' => 'Wati', 'status' => 'completed']);
    Payment::factory()->completed()->create(['order_id' => $order->id, 'amount' => 33000]);

    $response = $this->get('/admin/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->where('recentOrders.0.customer_name', 'Wati')
        ->where('recentOrders.0.total', 33000)
        ->where('recentOrders.0.status', 'completed')
    );
});

test('dashboard handles having no data at all', function () {
    $this->actingAs(User::factory()->admin()->create());

    $response = $this->get('/admin/dashboard');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('stats.total_orders', 0)
        ->where('stats.total_revenue', 0)
        ->where('topProducts', [])
        ->where('recentOrders', [])
    );
});

test('category count is unaffected by unrelated data', function () {
    $this->actingAs(User::factory()->admin()->create());
    Category::factory()->count(3)->create();

    $response = $this->get('/admin/dashboard');

    $response->assertInertia(fn ($page) => $page->where('stats.total_categories', 3));
});

test('dashboard includes a 14-day revenue trend ending today', function () {
    $this->actingAs(User::factory()->admin()->create());

    $order = Order::factory()->create();
    Payment::factory()->completed()->create(['order_id' => $order->id, 'amount' => 17500, 'paid_at' => today()]);

    $response = $this->get('/admin/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->has('revenueTrend', 14)
        ->where('revenueTrend.13.date', today()->toDateString())
        ->where('revenueTrend.13.revenue', 17500)
        ->where('revenueTrend.0.date', today()->subDays(13)->toDateString())
    );
});

test('dashboard breaks orders down by status for the chart', function () {
    $this->actingAs(User::factory()->admin()->create());

    Order::factory()->create(['status' => 'pending']);
    Order::factory()->count(2)->create(['status' => 'completed']);

    $response = $this->get('/admin/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->where('ordersByStatus.0', ['status' => 'pending', 'label' => 'Pending', 'count' => 1])
        ->where('ordersByStatus.1', ['status' => 'confirmed', 'label' => 'Confirmed', 'count' => 0])
        ->where('ordersByStatus.4', ['status' => 'completed', 'label' => 'Completed', 'count' => 2])
        ->where('ordersByStatus.5', ['status' => 'cancelled', 'label' => 'Cancelled', 'count' => 0])
    );
});

test('admin dashboards compare all branches and can filter a single branch', function () {
    $this->actingAs(User::factory()->admin()->create());
    $main = Branch::create(['name' => 'Bongabong Main', 'code' => 'MAIN']);
    $roxas = Branch::create(['name' => 'Roxas', 'code' => 'ROX']);

    $mainOrder = Order::factory()->create(['branch_id' => $main->id, 'status' => 'completed']);
    Payment::factory()->completed()->create(['order_id' => $mainOrder->id, 'amount' => 1200]);
    $roxasOrder = Order::factory()->create(['branch_id' => $roxas->id, 'status' => 'pending']);
    Payment::factory()->create(['order_id' => $roxasOrder->id, 'amount' => 800]);

    $this->get('/admin/dashboard')->assertInertia(fn ($page) => $page
        ->where('stats.total_branches', 2)
        ->where('stats.total_orders', 2)
        ->has('branchPerformance', 2)
        ->where('branchPerformance.0.name', 'Bongabong Main')
        ->where('branchPerformance.0.sales', 1200)
    );

    $this->get('/admin/dashboard?branch_id='.$main->id)->assertInertia(fn ($page) => $page
        ->where('selectedBranch', $main->id)
        ->where('stats.total_orders', 1)
        ->where('recentOrders.0.id', $mainOrder->id)
        ->has('branchPerformance', 2)
    );
});

test('staff dashboard and order filters remain within assigned branches', function () {
    $assigned = Branch::create(['name' => 'Assigned', 'code' => 'ASSIGNED']);
    $assignedAlso = Branch::create(['name' => 'Assigned Also', 'code' => 'ASSIGNED2']);
    $other = Branch::create(['name' => 'Other', 'code' => 'OTHER']);
    $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
    $staff->branches()->attach([$assigned->id, $assignedAlso->id]);
    Order::factory()->create(['branch_id' => $assigned->id, 'status' => 'pending']);
    Order::factory()->create(['branch_id' => $assignedAlso->id, 'status' => 'confirmed']);
    Order::factory()->create(['branch_id' => $other->id, 'status' => 'completed']);

    $this->actingAs($staff)->get('/staff/dashboard')->assertInertia(fn ($page) => $page
        ->component('admin/dashboard/index')
        ->where('stats.total_branches', 2)
        ->where('stats.total_orders', 2)
        ->where('stats.active_orders', 2)
        ->has('recentOrders', 2)
        ->has('branchPerformance', 2)
    );

    $this->get('/admin/dashboard?branch_id='.$other->id)->assertForbidden();
    $this->get('/admin/orders?branch_id='.$other->id)->assertForbidden();
    $this->get('/admin/orders?branch_id='.$assigned->id)->assertInertia(fn ($page) => $page
        ->where('selectedBranch', $assigned->id)
        ->where('orders.total', 1)
    );
});
