<?php

namespace Database\Seeders;

use App\Models\StorefrontSetting;
use Illuminate\Database\Seeder;

class StorefrontSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'store_name', 'value' => 'BrewSync', 'type' => 'text'],
            ['key' => 'store_tagline', 'value' => 'Crafted Coffee & Artisan Moments', 'type' => 'text'],
            ['key' => 'hero_title', 'value' => 'Artisan Coffee, Crafted For Every Moment', 'type' => 'text'],
            ['key' => 'hero_subtitle', 'value' => 'Discover single-origin roasted beans, handcrafted espresso creations, and artisanal pastries tailored to your palate.', 'type' => 'textarea'],
            ['key' => 'hero_cta_text', 'value' => 'Explore Our Coffee Menu', 'type' => 'text'],
            ['key' => 'about_title', 'value' => 'Our Passion For Exceptional Coffee', 'type' => 'text'],
            ['key' => 'about_story', 'value' => 'BrewSync is born out of an unwavering obsession with the bean, the roast, and the pour. We source ethically from sustainable coffee farms and roast in small batches to preserve delicate flavor profiles.', 'type' => 'textarea'],
            ['key' => 'contact_email', 'value' => 'hello@brewsync.com', 'type' => 'text'],
            ['key' => 'contact_phone', 'value' => '+63 (02) 8888-2739 / +63 917-123-4567', 'type' => 'text'],
            ['key' => 'contact_address', 'value' => '742 Evergreen Avenue, Ground Floor, Makati City, Metro Manila', 'type' => 'textarea'],
            ['key' => 'operating_hours', 'value' => 'Monday - Sunday: 7:00 AM - 10:00 PM', 'type' => 'text'],
            ['key' => 'gcash_account_name', 'value' => 'BrewSync Coffee Shop', 'type' => 'text'],
            ['key' => 'gcash_account_number', 'value' => '0917-123-4567', 'type' => 'text'],
            ['key' => 'delivery_fee', 'value' => '50.00', 'type' => 'text'],
            ['key' => 'delivery_enabled', 'value' => '1', 'type' => 'boolean'],
            ['key' => 'catering_enabled', 'value' => '1', 'type' => 'boolean'],
        ];

        foreach ($settings as $setting) {
            StorefrontSetting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'type' => $setting['type']]
            );
        }
    }
}
