<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CheckoutAndOrdersTest extends TestCase
{
    use RefreshDatabase;

    private function setupCartWithProduct(User $user): Product
    {
        $category = Category::create([
            'name' => 'Cold Brew',
            'slug' => 'cold-brew',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Reserve Cold Brew',
            'slug' => 'reserve-cold-brew',
            'base_price' => 160.00,
            'is_available' => true,
        ]);

        $cart = Cart::create(['user_id' => $user->id]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 160.00,
            'customizations' => ['Size' => ['option' => 'Medium', 'price' => 20.00]],
            'item_subtotal' => 320.00,
        ]);

        return $product;
    }

    public function test_guest_cannot_access_checkout(): void
    {
        $response = $this->get(route('checkout.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_with_empty_cart_redirects_to_menu(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('checkout.index'));
        $response->assertRedirect(route('menu'));
    }

    public function test_user_with_cart_can_view_checkout(): void
    {
        $user = User::factory()->create();
        $this->setupCartWithProduct($user);

        $response = $this->actingAs($user)->get(route('checkout.index'));
        $response->assertStatus(200);
        $response->assertSee('Complete Your Order');
        $response->assertSee('Reserve Cold Brew');
        $response->assertSee('GCash');
    }

    public function test_customer_can_place_pickup_order_with_gcash(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'name' => 'Coffee Buyer',
            'phone' => '0917-111-2222',
        ]);
        $product = $this->setupCartWithProduct($user);

        $receiptFile = UploadedFile::fake()->image('gcash_receipt.png', 600, 800);

        $response = $this->actingAs($user)->post(route('checkout.process'), [
            'customer_name' => 'Coffee Buyer',
            'customer_phone' => '0917-111-2222',
            'order_type' => 'pickup',
            'payment_method' => 'gcash',
            'gcash_reference_number' => '1002938475821',
            'receipt_image' => $receiptFile,
            'order_notes' => 'Pickup at 10 AM please',
        ]);

        $order = Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals(Order::STATUS_PENDING, $order->order_status);
        $this->assertEquals(320.00, (float) $order->subtotal);
        $this->assertEquals(0.00, (float) $order->delivery_fee);
        $this->assertEquals(320.00, (float) $order->total_amount);

        // Check payment
        $payment = $order->payment;
        $this->assertNotNull($payment);
        $this->assertEquals(Payment::METHOD_GCASH, $payment->payment_method);
        $this->assertEquals(Payment::STATUS_PENDING, $payment->payment_status); // NEVER auto-approved!
        $this->assertEquals('1002938475821', $payment->gcash_reference_number);
        $this->assertNotNull($payment->receipt_image_path);
        Storage::disk('public')->assertExists($payment->receipt_image_path);

        // Cart should be empty now
        $this->assertEquals(0, $user->cart->items()->count());

        // Redirects to order tracker
        $response->assertRedirect(route('orders.show', $order->order_number));
    }

    public function test_customer_can_place_delivery_order_with_cash_on_delivery(): void
    {
        $user = User::factory()->create();
        $this->setupCartWithProduct($user);

        $response = $this->actingAs($user)->post(route('checkout.process'), [
            'customer_name' => 'Delivery Customer',
            'customer_phone' => '0918-999-8888',
            'order_type' => 'delivery',
            'delivery_address' => 'Unit 10B, Greenbelt Residences, Makati City',
            'payment_method' => 'cash_on_delivery',
        ]);

        $order = Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals('delivery', $order->order_type);
        $this->assertEquals('Unit 10B, Greenbelt Residences, Makati City', $order->delivery_address);
        $this->assertEquals(50.00, (float) $order->delivery_fee);
        $this->assertEquals(370.00, (float) $order->total_amount); // 320 + 50

        $response->assertRedirect(route('orders.show', $order->order_number));
    }

    public function test_customer_can_view_order_history_and_tracker(): void
    {
        $user = User::factory()->create();
        $product = $this->setupCartWithProduct($user);

        $order = Order::create([
            'order_number' => 'BS-TEST-0001',
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => '0917-000-0000',
            'order_type' => 'pickup',
            'subtotal' => 160.00,
            'delivery_fee' => 0.00,
            'total_amount' => 160.00,
            'order_status' => Order::STATUS_CONFIRMED,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'payment_method' => 'gcash',
            'amount' => 160.00,
            'payment_status' => Payment::STATUS_PENDING,
            'gcash_reference_number' => 'REF12345',
        ]);

        // History page
        $historyResponse = $this->actingAs($user)->get(route('orders.index'));
        $historyResponse->assertStatus(200);
        $historyResponse->assertSee('BS-TEST-0001');

        // Tracker page
        $trackerResponse = $this->actingAs($user)->get(route('orders.show', 'BS-TEST-0001'));
        $trackerResponse->assertStatus(200);
        $trackerResponse->assertSee('BS-TEST-0001');
        $trackerResponse->assertSee('Confirmed');
        $trackerResponse->assertSee('Pending Verification');
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $orderA = Order::create([
            'order_number' => 'BS-USERA-0001',
            'user_id' => $userA->id,
            'customer_name' => $userA->name,
            'customer_phone' => '0917-000-0000',
            'order_type' => 'pickup',
            'subtotal' => 100.00,
            'delivery_fee' => 0.00,
            'total_amount' => 100.00,
            'order_status' => Order::STATUS_PENDING,
        ]);

        $response = $this->actingAs($userB)->get(route('orders.show', 'BS-USERA-0001'));
        $response->assertStatus(404);
    }
}
