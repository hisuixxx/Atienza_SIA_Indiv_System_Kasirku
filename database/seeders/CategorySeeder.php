<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Espresso',
                'description' => 'Pure, intense, and rich coffee extractions with a velvety crema.',
                'image_path' => 'https://images.unsplash.com/photo-1510591509098-f4fdc6d0ff04?auto=format&fit=crop&w=600&q=80',
                'display_order' => 1,
            ],
            [
                'name' => 'Americano',
                'description' => 'Bold espresso shots gently diluted with hot filtered water for a clean body.',
                'image_path' => 'https://images.unsplash.com/photo-1551030173-122aabc4489c?auto=format&fit=crop&w=600&q=80',
                'display_order' => 2,
            ],
            [
                'name' => 'Latte',
                'description' => 'Smooth espresso layered with silky steamed milk and a thin layer of microfoam.',
                'image_path' => 'https://images.unsplash.com/photo-1570968915860-54d5c301fa9f?auto=format&fit=crop&w=600&q=80',
                'display_order' => 3,
            ],
            [
                'name' => 'Cappuccino',
                'description' => 'An Italian classic with equal parts espresso, steamed milk, and airy velvety foam.',
                'image_path' => 'https://images.unsplash.com/photo-1534778101976-62847782c213?auto=format&fit=crop&w=600&q=80',
                'display_order' => 4,
            ],
            [
                'name' => 'Mocha',
                'description' => 'Rich dark Belgian cocoa infused with espresso and velvety steamed milk.',
                'image_path' => 'https://images.unsplash.com/photo-1578314675249-a6910f80cc4e?auto=format&fit=crop&w=600&q=80',
                'display_order' => 5,
            ],
            [
                'name' => 'Macchiato',
                'description' => 'Bold espresso marked with a delicate dollop of textured milk foam.',
                'image_path' => 'https://images.unsplash.com/photo-1485808191679-5f86510681a2?auto=format&fit=crop&w=600&q=80',
                'display_order' => 6,
            ],
            [
                'name' => 'Cold Brew',
                'description' => 'Slow-steeped for 18 hours in cold purified water for low acidity and sweet chocolate notes.',
                'image_path' => 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=600&q=80',
                'display_order' => 7,
            ],
            [
                'name' => 'Iced Coffee',
                'description' => 'Crisp chilled espresso poured over crystal clear ice with your choice of dairy.',
                'image_path' => 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&w=600&q=80',
                'display_order' => 8,
            ],
            [
                'name' => 'Frappes',
                'description' => 'Decadent blended ice drinks topped with freshly whipped cream and gourmet drizzles.',
                'image_path' => 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?auto=format&fit=crop&w=600&q=80',
                'display_order' => 9,
            ],
            [
                'name' => 'Non-Coffee Drinks',
                'description' => 'Artisanal ceremonial matcha, gourmet chocolates, loose-leaf teas, and fruit refreshers.',
                'image_path' => 'https://images.unsplash.com/photo-1536256263959-770b48d82b0a?auto=format&fit=crop&w=600&q=80',
                'display_order' => 10,
            ],
            [
                'name' => 'Pastries',
                'description' => 'Freshly baked flaky croissants, cinnamon rolls, and gourmet muffins made daily.',
                'image_path' => 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=600&q=80',
                'display_order' => 11,
            ],
            [
                'name' => 'Snacks',
                'description' => 'Savory artisanal toasties, waffle bites, and cookies paired perfectly with your brew.',
                'image_path' => 'https://images.unsplash.com/photo-1558961363-fa8fdf82db35?auto=format&fit=crop&w=600&q=80',
                'display_order' => 12,
            ],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['slug' => Str::slug($cat['name'])],
                [
                    'name' => $cat['name'],
                    'description' => $cat['description'],
                    'image_path' => $cat['image_path'],
                    'display_order' => $cat['display_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
