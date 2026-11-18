<?php

use App\Models\Category;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductPhotos;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('guests cannot access product management', function () {
    $this->get('/admin/products')->assertRedirect('/login');
});

test('admin can list products with category and photos', function () {
    $this->actingAs(User::factory()->admin()->create());

    $category = Category::factory()->create();
    Product::factory()->count(3)->create(['category_id' => $category->id]);

    $response = $this->get('/admin/products');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/products/index')
        ->has('products', 3)
    );
});

test('admin can update default product availability', function () {
    $this->actingAs(User::factory()->admin()->create());
    $product = Product::factory()->create(['is_available' => true]);

    $this->patch(route('products.availability', $product), ['is_available' => false])
        ->assertRedirect();

    $this->assertDatabaseHas('products', ['id' => $product->id, 'is_available' => false]);
});

test('availability overrides are unique and branch specific', function () {
    $this->actingAs(User::factory()->admin()->create());
    $branchA = Branch::create(['name' => 'Main', 'code' => 'MAIN']);
    $branchB = Branch::create(['name' => 'North', 'code' => 'NORTH']);
    $product = Product::factory()->create(['is_available' => false]);

    $this->patch(route('products.availability', $product), ['branch_id' => $branchA->id, 'is_available' => true])->assertRedirect();
    $this->patch(route('products.availability', $product), ['branch_id' => $branchB->id, 'is_available' => false])->assertRedirect();
    $this->patch(route('products.availability', $product), ['branch_id' => $branchA->id, 'is_available' => true])->assertRedirect();

    expect($product->fresh()->isAvailableAt($branchA->id))->toBeTrue();
    expect($product->fresh()->isAvailableAt($branchB->id))->toBeFalse();
    $this->assertDatabaseCount('branch_product_availability', 2);

    $this->get(route('products.index', ['branch_id' => $branchB->id]))
        ->assertInertia(fn ($page) => $page
            ->where('selectedBranch', $branchB->id)
            ->where('products.0.is_available', false)
        );
});

test('product search filters by name', function () {
    $this->actingAs(User::factory()->admin()->create());

    Product::factory()->create(['name' => 'Nasi Goreng']);
    Product::factory()->create(['name' => 'Es Teh']);

    $response = $this->get('/admin/products?search=Nasi');

    $response->assertInertia(fn ($page) => $page
        ->has('products', 1)
        ->where('products.0.name', 'Nasi Goreng')
    );
});

test('admin can create a product with a photo', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create());
    $category = Category::factory()->create();

    $response = $this->post('/admin/products', [
        'name' => 'Ayam Geprek',
        'category_id' => $category->id,
        'price' => 22000,
        'photos' => [UploadedFile::fake()->create('food.jpg', 100, 'image/jpeg')],
    ]);

    $response->assertRedirect(route('products.index'));
    $this->assertDatabaseHas('products', ['name' => 'Ayam Geprek', 'price' => 22000]);

    $product = Product::where('name', 'Ayam Geprek')->firstOrFail();
    expect($product->photos)->toHaveCount(1);
    expect($product->photos->first()->is_primary)->toBeTrue();
});

test('admin can remove a product photo on update', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create());
    $product = Product::factory()->create();
    $photo = ProductPhotos::factory()->create(['product_id' => $product->id, 'url' => Storage::url('products/existing.jpg')]);
    Storage::disk('public')->put('products/existing.jpg', 'fake-content');

    $response = $this->put("/admin/products/{$product->id}", [
        'name' => $product->name,
        'category_id' => $product->category_id,
        'price' => $product->price,
        'remove_photos' => [$photo->id],
    ]);

    $response->assertRedirect(route('products.index'));
    $this->assertDatabaseMissing('product_photos', ['id' => $photo->id]);
});

test('admin can delete a product', function () {
    $this->actingAs(User::factory()->admin()->create());
    $product = Product::factory()->create();

    $response = $this->delete("/admin/products/{$product->id}");

    $response->assertRedirect(route('products.index'));
    $this->assertSoftDeleted('products', ['id' => $product->id]);
});
