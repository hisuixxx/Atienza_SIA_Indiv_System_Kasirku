<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CustomizationOption;
use App\Models\Product;
use App\Models\ProductCustomization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingAndMenuTest extends TestCase
{
    use RefreshDatabase;

    private function createSampleData(): array
    {
        $category = Category::create([
            'name' => 'Latte',
            'slug' => 'latte',
            'description' => 'Silky espresso and steamed milk',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Vanilla Bean Latte',
            'slug' => 'vanilla-bean-latte',
            'description' => 'Espresso with rich Madagascar vanilla and fresh steamed milk.',
            'base_price' => 165.00,
            'is_available' => true,
            'is_featured' => true,
        ]);

        $customization = ProductCustomization::create([
            'product_id' => $product->id,
            'name' => 'Drink Size',
            'type' => 'single_choice',
            'is_required' => true,
        ]);

        $optSmall = CustomizationOption::create([
            'product_customization_id' => $customization->id,
            'name' => 'Small (8oz)',
            'additional_price' => 0.00,
            'is_default' => true,
        ]);

        $optLarge = CustomizationOption::create([
            'product_customization_id' => $customization->id,
            'name' => 'Large (16oz)',
            'additional_price' => 45.00,
            'is_default' => false,
        ]);

        return compact('category', 'product', 'customization', 'optSmall', 'optLarge');
    }

    public function test_landing_page_can_be_rendered(): void
    {
        $this->createSampleData();

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('customer/landing'));
    }

    public function test_menu_page_can_be_rendered(): void
    {
        $this->createSampleData();

        $response = $this->get('/menu');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('customer/index')
            ->has('products')
            ->has('categories')
        );
    }

    public function test_menu_category_filtering(): void
    {
        $data = $this->createSampleData();

        $otherCat = Category::create([
            'name' => 'Snacks',
            'slug' => 'snacks',
            'is_active' => true,
        ]);

        $otherProd = Product::create([
            'category_id' => $otherCat->id,
            'name' => 'Cheese Toastie',
            'slug' => 'cheese-toastie',
            'description' => 'Four-cheese toastie',
            'base_price' => 175.00,
            'is_available' => true,
        ]);

        $response = $this->get('/menu?category=latte');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('products', 1)
            ->where('products.0.name', 'Vanilla Bean Latte')
        );
    }

    public function test_menu_search(): void
    {
        $this->createSampleData();

        $response = $this->get('/menu?search=Vanilla');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('products', 1)
            ->where('products.0.name', 'Vanilla Bean Latte')
        );

        $emptyResponse = $this->get('/menu?search=NonExistentDrink123');
        $emptyResponse->assertStatus(200);
        $emptyResponse->assertInertia(fn ($page) => $page
            ->has('products', 0)
        );
    }

    public function test_product_customization_details_page_can_be_rendered(): void
    {
        $data = $this->createSampleData();

        $response = $this->get('/menu/' . $data['product']->slug);
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('customer/index')
            ->has('selectedProduct')
            ->where('selectedProduct.name', 'Vanilla Bean Latte')
        );
    }

    public function test_unauthenticated_user_adding_to_checkout_redirects_to_login(): void
    {
        $response = $this->get('/checkout');
        $response->assertRedirect('/login?redirect=%2Fcheckout');
    }

    public function test_authenticated_user_can_access_checkout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/checkout');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('customer/checkout/index'));
    }
}
