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
        // 1. Branches (Sip n Brew Haven Inc. – 4 branches)
        $mainBranch = Branch::updateOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'Bongabong Main Branch – Headquarters',
                'address' => 'Bongabong Main Café, Oriental Mindoro',
                'is_active' => true,
            ]
        );

        $roxasBranch = Branch::updateOrCreate(
            ['code' => 'ROX'],
            [
                'name' => 'Roxas Branch',
                'address' => 'Roxas Drive Café, Oriental Mindoro',
                'is_active' => true,
            ]
        );

        $pinamalayanBranch = Branch::updateOrCreate(
            ['code' => 'PIN'],
            [
                'name' => 'Pinamalayan Branch',
                'address' => 'Pinamalayan Town Proper, Oriental Mindoro',
                'is_active' => true,
            ]
        );

        $puertoBranch = Branch::updateOrCreate(
            ['code' => 'PG'],
            [
                'name' => 'Puerto Galera Branch',
                'address' => 'Puerto Galera, Oriental Mindoro',
                'is_active' => true,
            ]
        );

        // 2. Administrator Account
        $admin = User::updateOrCreate(
            ['email' => 'admin@brewsync.com'],
            [
                'name' => 'BrewSync Admin',
                'password' => Hash::make('password'),
                'phone' => '0917-000-0001',
                'address' => 'BrewSync HQ, Bongabong, Oriental Mindoro',
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // 3. Staff (Cashier) Account
        $staff = User::updateOrCreate(
            ['email' => 'staff@brewsync.com'],
            [
                'name' => 'BrewSync Staff',
                'password' => Hash::make('password'),
                'phone' => '0917-000-0002',
                'address' => 'Main Branch Counter',
                'role' => User::ROLE_STAFF,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // 4. Kitchen Account
        $kitchen = User::updateOrCreate(
            ['email' => 'kitchen@brewsync.com'],
            [
                'name' => 'BrewSync Kitchen',
                'password' => Hash::make('password'),
                'phone' => '0918-000-0004',
                'address' => 'Main Branch Kitchen',
                'role' => User::ROLE_KITCHEN,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // 5. Customer Account
        $customer = User::updateOrCreate(
            ['email' => 'customer@brewsync.com'],
            [
                'name' => 'Juan Dela Cruz',
                'password' => Hash::make('password'),
                'phone' => '0919-000-0003',
                'address' => 'Unit 402 Sunset Tower, Legazpi Village, Makati City',
                'role' => User::ROLE_CUSTOMER,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Attach employees to branches
        // Admin can access all branches
        $admin->branches()->sync([
            $mainBranch->id,
            $roxasBranch->id,
            $pinamalayanBranch->id,
            $puertoBranch->id,
        ]);

        // Staff and Kitchen default to Main Branch
        $staff->branches()->sync([$mainBranch->id]);
        $kitchen->branches()->sync([$mainBranch->id]);

        // Customer is not assigned to branches via pivot
    }
}