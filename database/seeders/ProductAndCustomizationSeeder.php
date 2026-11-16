<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CustomizationOption;
use App\Models\Product;
use App\Models\ProductCustomization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductAndCustomizationSeeder extends Seeder
{
    public function run(): void
    {
        $productsData = [
            // Espresso
            [
                'category' => 'Espresso',
                'name' => 'Single Origin Espresso',
                'description' => 'A concentrated shot extracted under 9 bars of pressure with a thick golden crema, featuring notes of dark chocolate and citrus.',
                'price' => 110.00,
                'featured' => true,
                'image' => 'https://images.unsplash.com/photo-1510591509098-f4fdc6d0ff04?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'category' => 'Espresso',
                'name' => 'Espresso Doppio (Double Shot)',
                'description' => 'Double shot of our signature house blend with enhanced body, deep hazelnut undertones, and persistent finish.',
                'price' => 140.00,
                'featured' => false,
                'image' => 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=600&q=80',
            ],

            // Americano
            [
                'category' => 'Americano',
                'name' => 'Classic Caffe Americano',
                'description' => 'Rich espresso poured over steaming hot purified water for a nuanced and balanced dark brew.',
                'price' => 130.00,
                'featured' => false,
                'image' => 'https://images.unsplash.com/photo-1551030173-122aabc4489c?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'category' => 'Americano',
                'name' => 'Honey Cinnamon Americano',
                'description' => 'Double shot espresso infused with organic wildflower honey and dusted with aromatic Ceylon cinnamon.',
                'price' => 155.00,
                'featured' => true,
                'image' => 'https://images.unsplash.com/photo-1497636577773-f1231844b336?auto=format&fit=crop&w=600&q=80',
            ],

            // Latte
            [
                'category' => 'Latte',
                'name' => 'BrewSync Signature Vanilla Latte',
                'description' => 'Velvety steamed fresh milk poured over double espresso with Madagascar vanilla bean extract.',
                'price' => 165.00,
                'featured' => true,
                'image' => 'https://images.unsplash.com/photo-1570968915860-54d5c301fa9f?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'category' => 'Latte',
                'name' => 'Spanish Latte (Café con Leche)',
                'description' => 'Espresso harmonized with creamy steamed milk and sweetened condensed milk for a silky caramel sweetness.',
                'price' => 175.00,
                'featured' => true,
                'image' => 'https://images.unsplash.com/photo-1561047029-3000c68339ca?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'category' => 'Latte',
                'name' => 'Salted Caramel Latte',
                'description' => 'Artisanal buttery caramel sauce melted into espresso, steamed dairy, and sprinkled with Himalayan sea salt flakes.',
                'price' => 180.00,
                'featured' => false,
                'image' => 'https://images.unsplash.com/photo-1582293041079-7814c2f12063?auto=format&fit=crop&w=600&q=80',
            ],

            // Cappuccino
            [
                'category' => 'Cappuccino',
                'name' => 'Traditional Italian Cappuccino',
                'description' => 'Equal third proportions of intense espresso, silky steamed whole milk, and dense micro-foam dusting.',
                'price' => 150.00,
                'featured' => false,
                'image' => 'https://images.unsplash.com/photo-1534778101976-62847782c213?auto=format&fit=crop&w=600&q=80',
            ],

            // Mocha
            [
                'category' => 'Mocha',
                'name' => 'Belgian Dark Chocolate Mocha',
                'description' => 'Real 70% dark Belgian cocoa melted with espresso and steamed milk, capped with cocoa nibs.',
                'price' => 180.00,
                'featured' => true,
                'image' => 'https://images.unsplash.com/photo-1578314675249-a6910f80cc4e?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'category' => 'Mocha',
                'name' => 'White Chocolate Mocha',
                'description' => 'Velvety white chocolate ganache swirled with freshly pulled espresso and steamed milk.',
                'price' => 185.00,
                'featured' => false,
                'image' => 'https://images.unsplash.com/photo-1517256064527-09c73fc73e38?auto=format&fit=crop&w=600&q=80',
            ],

            // Macchiato
            [
                'category' => 'Macchiato',
                'name' => 'Caramel Cloud Macchiato',
                'description' => 'Steamed milk stained with espresso shots poured on top, finished with crosshatch vanilla-caramel sauce.',
                'price' => 170.00,
                'featured' => true,
                'image' => 'https://images.unsplash.com/photo-1485808191679-5f86510681a2?auto=format&fit=crop&w=600&q=80',
            ],

            // Cold Brew
            [
                'category' => 'Cold Brew',
                'name' => '18-Hour Reserve Cold Brew',
                'description' => 'Steeped for 18 hours in cold purified water. Exceptionally smooth, sweet, and low in bitterness.',
                'price' => 160.00,
                'featured' => true,
                'image' => 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'category' => 'Cold Brew',
                'name' => 'Vanilla Sweet Cream Cold Brew',
                'description' => 'Our slow-steeped reserve cold brew accented with vanilla and topped with a floating house-made sweet cream cascade.',
                'price' => 185.00,
                'featured' => true,
                'image' => 'https://images.unsplash.com/photo-1592663527359-cf6642f54cff?auto=format&fit=crop&w=600&q=80',
            ],

            // Iced Coffee
            [
                'category' => 'Iced Coffee',
                'name' => 'Iced Brown Sugar Shaken Espresso',
                'description' => 'Blonde espresso shots hand-shaken with brown sugar syrup and cinnamon over ice, crowned with oat milk.',
                'price' => 180.00,
                'featured' => true,
                'image' => 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&w=600&q=80',
            ],

            // Frappes
            [
                'category' => 'Frappes',
                'name' => 'Java Chip Blended Frappe',
                'description' => 'Coffee roast blended with rich chocolate chips, whole milk, and ice, finished with chocolate drizzle.',
                'price' => 195.00,
                'featured' => true,
                'image' => 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'category' => 'Frappes',
                'name' => 'Caramel Crunch Frappe',
                'description' => 'Decadent buttery caramel blended with coffee and ice, topped with whipped cream and candied crunchies.',
                'price' => 195.00,
                'featured' => false,
                'image' => 'https://images.unsplash.com/photo-1579888944880-d98341245702?auto=format&fit=crop&w=600&q=80',
            ],

            // Non-Coffee Drinks
            [
                'category' => 'Non-Coffee Drinks',
                'name' => 'Ceremonial Uji Matcha Latte',
                'description' => 'First-harvest organic Japanese stone-ground matcha whisked to order and blended with creamy steamed milk.',
                'price' => 185.00,
                'featured' => true,
                'image' => 'https://images.unsplash.com/photo-1536256263959-770b48d82b0a?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'category' => 'Non-Coffee Drinks',
                'name' => 'Signature Hot Chocolate',
                'description' => 'Melted single-origin dark chocolate blended with whole milk and topped with fluffy toasted marshmallows.',
                'price' => 160.00,
                'featured' => false,
                'image' => 'https://images.unsplash.com/photo-1542990253-0d0f5be5f0ed?auto=format&fit=crop&w=600&q=80',
            ],

            // Pastries
            [
                'category' => 'Pastries',
                'name' => 'French Butter Croissant',
                'description' => 'Golden flaky pastry laminated with pure European butter, baked fresh every morning to crisp perfection.',
                'price' => 110.00,
                'featured' => true,
                'image' => 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'category' => 'Pastries',
                'name' => 'Warm Glazed Cinnamon Roll',
                'description' => 'Tender yeast dough spiraled with fragrant Saigon cinnamon and coated with cream cheese vanilla glaze.',
                'price' => 135.00,
                'featured' => false,
                'image' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'category' => 'Pastries',
                'name' => 'Blueberry Streusel Muffin',
                'description' => 'Bursting with plump fresh wild blueberries and crowned with a sweet golden streusel crumb topping.',
                'price' => 125.00,
                'featured' => false,
                'image' => 'https://images.unsplash.com/photo-1586985289688-ca3cf47d3e6e?auto=format&fit=crop&w=600&q=80',
            ],

            // Snacks
            [
                'category' => 'Snacks',
                'name' => 'Artisanal Grilled Four-Cheese Toastie',
                'description' => 'Sourdough bread grilled with aged cheddar, mozzarella, gouda, and parmesan with garlic herb butter.',
                'price' => 175.00,
                'featured' => true,
                'image' => 'https://images.unsplash.com/photo-1528735602780-2552fd46c7af?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'category' => 'Snacks',
                'name' => 'Chunky Dark Chocolate Sea Salt Cookie',
                'description' => 'Chewy center with crisp edges, packed with generous 70% dark chocolate chunks and Maldon sea salt.',
                'price' => 95.00,
                'featured' => false,
                'image' => 'https://images.unsplash.com/photo-1558961363-fa8fdf82db35?auto=format&fit=crop&w=600&q=80',
            ],
        ];

        // Seed products
        foreach ($productsData as $data) {
            $cat = Category::where('name', $data['category'])->first();
            if (!$cat) {
                continue;
            }

            $product = Product::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'category_id' => $cat->id,
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'price' => $data['price'],
                    'base_price' => $data['price'],
                    'is_available' => true,
                    'is_active' => true,
                    'is_featured' => $data['featured'],
                    'image_path' => $data['image'],
                ]
            );

            // Seed Product Photo
            \App\Models\ProductPhotos::updateOrCreate(
                ['product_id' => $product->id],
                [
                    'url' => $data['image'],
                    'is_primary' => true,
                ]
            );

            // Seed default recipe for stock deductions
            $espressoId = \Illuminate\Support\Facades\DB::table('ingredients')->where('name', 'Signature Espresso Beans')->value('id');
            $milkId = \Illuminate\Support\Facades\DB::table('ingredients')->where('name', 'Fresh Whole Milk')->value('id');
            if ($espressoId && !in_array($data['category'], ['Pastries', 'Snacks'])) {
                \Illuminate\Support\Facades\DB::table('product_recipes')->updateOrInsert(
                    ['product_id' => $product->id, 'ingredient_id' => $espressoId],
                    ['quantity' => 18.000, 'created_at' => now(), 'updated_at' => now()]
                );
            }
            if ($milkId && in_array($data['category'], ['Latte', 'Cappuccino', 'Mocha'])) {
                \Illuminate\Support\Facades\DB::table('product_recipes')->updateOrInsert(
                    ['product_id' => $product->id, 'ingredient_id' => $milkId],
                    ['quantity' => 150.000, 'created_at' => now(), 'updated_at' => now()]
                );
            }

            // Add Drink Customization options to beverage products (excluding pastries/snacks)
            if (!in_array($data['category'], ['Pastries', 'Snacks'])) {
                $this->attachDrinkCustomizations($product);
            }
        }
    }

    private function attachDrinkCustomizations(Product $product): void
    {
        // 1. Drink Size
        $sizeCust = ProductCustomization::updateOrCreate(
            ['product_id' => $product->id, 'name' => 'Drink Size'],
            ['type' => 'single_choice', 'is_required' => true]
        );
        CustomizationOption::updateOrCreate(['product_customization_id' => $sizeCust->id, 'name' => 'Small (8oz)'], ['additional_price' => 0.00, 'is_default' => true]);
        CustomizationOption::updateOrCreate(['product_customization_id' => $sizeCust->id, 'name' => 'Medium (12oz)'], ['additional_price' => 25.00, 'is_default' => false]);
        CustomizationOption::updateOrCreate(['product_customization_id' => $sizeCust->id, 'name' => 'Large (16oz)'], ['additional_price' => 45.00, 'is_default' => false]);

        // 2. Temperature
        $tempCust = ProductCustomization::updateOrCreate(
            ['product_id' => $product->id, 'name' => 'Temperature'],
            ['type' => 'single_choice', 'is_required' => true]
        );
        CustomizationOption::updateOrCreate(['product_customization_id' => $tempCust->id, 'name' => 'Hot'], ['additional_price' => 0.00, 'is_default' => true]);
        CustomizationOption::updateOrCreate(['product_customization_id' => $tempCust->id, 'name' => 'Iced'], ['additional_price' => 10.00, 'is_default' => false]);

        // 3. Milk Selection
        $milkCust = ProductCustomization::updateOrCreate(
            ['product_id' => $product->id, 'name' => 'Milk Selection'],
            ['type' => 'single_choice', 'is_required' => false]
        );
        CustomizationOption::updateOrCreate(['product_customization_id' => $milkCust->id, 'name' => 'Whole Milk (Standard)'], ['additional_price' => 0.00, 'is_default' => true]);
        CustomizationOption::updateOrCreate(['product_customization_id' => $milkCust->id, 'name' => 'Oat Milk'], ['additional_price' => 35.00, 'is_default' => false]);
        CustomizationOption::updateOrCreate(['product_customization_id' => $milkCust->id, 'name' => 'Almond Milk'], ['additional_price' => 35.00, 'is_default' => false]);
        CustomizationOption::updateOrCreate(['product_customization_id' => $milkCust->id, 'name' => 'Soy Milk'], ['additional_price' => 30.00, 'is_default' => false]);
        CustomizationOption::updateOrCreate(['product_customization_id' => $milkCust->id, 'name' => 'Skim Milk'], ['additional_price' => 0.00, 'is_default' => false]);

        // 4. Sweetness Level
        $sweetCust = ProductCustomization::updateOrCreate(
            ['product_id' => $product->id, 'name' => 'Sweetness Level'],
            ['type' => 'single_choice', 'is_required' => false]
        );
        CustomizationOption::updateOrCreate(['product_customization_id' => $sweetCust->id, 'name' => '100% (Standard Sweetness)'], ['additional_price' => 0.00, 'is_default' => true]);
        CustomizationOption::updateOrCreate(['product_customization_id' => $sweetCust->id, 'name' => '75% (Less Sweet)'], ['additional_price' => 0.00, 'is_default' => false]);
        CustomizationOption::updateOrCreate(['product_customization_id' => $sweetCust->id, 'name' => '50% (Half Sweet)'], ['additional_price' => 0.00, 'is_default' => false]);
        CustomizationOption::updateOrCreate(['product_customization_id' => $sweetCust->id, 'name' => '25% (Light Sweet)'], ['additional_price' => 0.00, 'is_default' => false]);
        CustomizationOption::updateOrCreate(['product_customization_id' => $sweetCust->id, 'name' => '0% (Unsweetened / No Sugar)'], ['additional_price' => 0.00, 'is_default' => false]);

        // 5. Espresso Shots
        $shotCust = ProductCustomization::updateOrCreate(
            ['product_id' => $product->id, 'name' => 'Additional Espresso Shot'],
            ['type' => 'single_choice', 'is_required' => false]
        );
        CustomizationOption::updateOrCreate(['product_customization_id' => $shotCust->id, 'name' => 'Standard Recipe'], ['additional_price' => 0.00, 'is_default' => true]);
        CustomizationOption::updateOrCreate(['product_customization_id' => $shotCust->id, 'name' => '+1 Extra Espresso Shot'], ['additional_price' => 30.00, 'is_default' => false]);
        CustomizationOption::updateOrCreate(['product_customization_id' => $shotCust->id, 'name' => '+2 Extra Espresso Shots'], ['additional_price' => 55.00, 'is_default' => false]);

        // 6. Syrup Flavors
        $syrupCust = ProductCustomization::updateOrCreate(
            ['product_id' => $product->id, 'name' => 'Syrup Flavors'],
            ['type' => 'single_choice', 'is_required' => false]
        );
        CustomizationOption::updateOrCreate(['product_customization_id' => $syrupCust->id, 'name' => 'None'], ['additional_price' => 0.00, 'is_default' => true]);
        CustomizationOption::updateOrCreate(['product_customization_id' => $syrupCust->id, 'name' => 'Madagascar Vanilla Syrup'], ['additional_price' => 25.00, 'is_default' => false]);
        CustomizationOption::updateOrCreate(['product_customization_id' => $syrupCust->id, 'name' => 'Golden Caramel Syrup'], ['additional_price' => 25.00, 'is_default' => false]);
        CustomizationOption::updateOrCreate(['product_customization_id' => $syrupCust->id, 'name' => 'Roasted Hazelnut Syrup'], ['additional_price' => 25.00, 'is_default' => false]);
        CustomizationOption::updateOrCreate(['product_customization_id' => $syrupCust->id, 'name' => 'Toffee Nut Syrup'], ['additional_price' => 25.00, 'is_default' => false]);

        // 7. Toppings & Add-ons
        $topCust = ProductCustomization::updateOrCreate(
            ['product_id' => $product->id, 'name' => 'Toppings & Add-ons'],
            ['type' => 'single_choice', 'is_required' => false]
        );
        CustomizationOption::updateOrCreate(['product_customization_id' => $topCust->id, 'name' => 'No Additional Toppings'], ['additional_price' => 0.00, 'is_default' => true]);
        CustomizationOption::updateOrCreate(['product_customization_id' => $topCust->id, 'name' => 'Fresh Whipped Cream'], ['additional_price' => 20.00, 'is_default' => false]);
        CustomizationOption::updateOrCreate(['product_customization_id' => $topCust->id, 'name' => 'Salted Caramel Drizzle'], ['additional_price' => 15.00, 'is_default' => false]);
        CustomizationOption::updateOrCreate(['product_customization_id' => $topCust->id, 'name' => 'Dark Chocolate Drizzle'], ['additional_price' => 15.00, 'is_default' => false]);
        CustomizationOption::updateOrCreate(['product_customization_id' => $topCust->id, 'name' => 'Espresso Jelly Cubes'], ['additional_price' => 25.00, 'is_default' => false]);
    }
}
