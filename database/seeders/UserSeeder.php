<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Branches
        $mainBranch = Branch::updateOrCreate(
            ['code' => 'MAIN'],
            ['name' => 'Main Branch', 'address' => 'Bongabong Main Café, Mindoro', 'is_active' => true]
        );

        $roxasBranch = Branch::updateOrCreate(
            ['code' => 'ROX'],
            ['name' => 'Roxas Branch', 'address' => 'Roxas Drive Café, Mindoro', 'is_active' => true]
        );

        // 2. Administrator Account
        $admin = User::updateOrCreate(
            ['email' => 'admin@brewsync.com'],
            [
                'name' => 'BrewSync Admin',
                'password' => Hash::make('password'),
                'phone' => '0917-000-0001',
                'address' => 'BrewSync HQ, 742 Evergreen Ave, Makati City',
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );



        // 4. Cashier Account
        // Removed as Staff (Cashier) role is covered by the Staff account.

        // 5. Kitchen Staff Account
        $kitchen = User::updateOrCreate(
            ['email' => 'kitchen@brewsync.com'],
            [
                'name' => 'BrewSync Kitchen',
                'password' => Hash::make('password'),
                'phone' => '0918-000-0004',
                'address' => 'Main Branch Kitchen',
                'role' => 'kitchen',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // 6. Inventory Staff Account
        // Removed obsolete inventory staff.


        // 7. Customer Account
        $customer = User::updateOrCreate(
            ['email' => 'customer@brewsync.com'],
            [
                'name' => 'Juan Dela Cruz',
                'password' => Hash::make('password'),
                'phone' => '0919-000-0003',
                'address' => 'Unit 402 Sunset Tower, Legazpi Village, Makati City',
                'role' => 'customer',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Attach employees to branches
        $admin->branches()->syncWithoutDetaching([$mainBranch->id, $roxasBranch->id]);
        $staff->branches()->syncWithoutDetaching([$mainBranch->id]);
        $cashier->branches()->syncWithoutDetaching([$mainBranch->id]);
        $kitchen->branches()->syncWithoutDetaching([$mainBranch->id]);
        $inventoryStaff->branches()->syncWithoutDetaching([$mainBranch->id, $roxasBranch->id]);

        // 8. Suppliers
        $sup1 = DB::table('suppliers')->updateOrInsert(
            ['name' => 'Mount Apo Coffee Estate'],
            ['phone' => '0920-111-2222', 'email' => 'apo@coffee.ph', 'address' => 'Davao del Sur', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]
        );
        $sup1Id = DB::table('suppliers')->where('name', 'Mount Apo Coffee Estate')->value('id');

        $sup2 = DB::table('suppliers')->updateOrInsert(
            ['name' => 'Bukidnon Fresh Dairy Co.'],
            ['phone' => '0920-333-4444', 'email' => 'dairy@bukidnon.ph', 'address' => 'Bukidnon, Mindanao', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]
        );
        $sup2Id = DB::table('suppliers')->where('name', 'Bukidnon Fresh Dairy Co.')->value('id');

        // 9. Ingredients
        $ingredients = [
            ['name' => 'Signature Espresso Beans', 'unit' => 'g', 'minimum_stock' => 500],
            ['name' => 'Fresh Whole Milk', 'unit' => 'ml', 'minimum_stock' => 2000],
            ['name' => 'Oat Milk Barista Edition', 'unit' => 'ml', 'minimum_stock' => 1000],
            ['name' => 'Madagascar Vanilla Syrup', 'unit' => 'ml', 'minimum_stock' => 500],
            ['name' => 'Belgian Dark Chocolate Sauce', 'unit' => 'ml', 'minimum_stock' => 400],
            ['name' => 'Purified Brewing Water', 'unit' => 'ml', 'minimum_stock' => 5000],
        ];

        foreach ($ingredients as $ing) {
            $ingId = DB::table('ingredients')->updateOrInsert(
                ['name' => $ing['name']],
                ['unit' => $ing['unit'], 'minimum_stock' => $ing['minimum_stock'], 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]
            );
            $id = DB::table('ingredients')->where('name', $ing['name'])->value('id');

            // Seed inventory balances for both branches
            DB::table('inventory')->updateOrInsert(
                ['branch_id' => $mainBranch->id, 'ingredient_id' => $id],
                ['quantity' => $ing['minimum_stock'] * 4, 'created_at' => now(), 'updated_at' => now()]
            );

            DB::table('inventory')->updateOrInsert(
                ['branch_id' => $roxasBranch->id, 'ingredient_id' => $id],
                ['quantity' => $ing['minimum_stock'] * 3, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
