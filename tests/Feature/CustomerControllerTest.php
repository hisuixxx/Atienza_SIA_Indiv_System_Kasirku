<?php

use App\Models\Category;
use App\Models\Product;

test('site root renders the BrewSync landing page', function () {
    $this->get('/')->assertInertia(fn ($page) => $page->component('customer/landing'));
});

test('customer menu renders products and categories', function () {
    $category = Category::factory()->create();
    Product::factory()->count(3)->create(['category_id' => $category->id]);

    $response = $this->get('/menu');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('customer/index')
        ->has('products', 3)
        ->has('categories', 1)
    );
});

test('customer menu search filters by product name', function () {
    Product::factory()->create(['name' => 'Nasi Goreng']);
    Product::factory()->create(['name' => 'Es Teh']);

    $response = $this->get('/menu?search=Nasi');

    $response->assertInertia(fn ($page) => $page
        ->has('products', 1)
        ->where('products.0.name', 'Nasi Goreng')
    );
});

test('infinite scroll requests beyond page 1 return json', function () {
    Product::factory()->count(15)->create();

    $response = $this->get('/menu?page=2', ['X-Requested-With' => 'XMLHttpRequest']);

    $response->assertOk();
    $response->assertJsonStructure(['products', 'pagination']);
});

test('unauthenticated user is redirected to login when accessing checkout', function () {
    $response = $this->get('/checkout');

    $response->assertRedirect('/login?redirect=%2Fcheckout');
});

