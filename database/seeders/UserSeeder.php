<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed users table with the four required demo accounts and supplemental accounts.
     */
    public function run(): void
    {
        // 1. Fleet Director / Admin
        User::firstOrCreate(
            ['email' => 'admin@cargotrace.test'],
            [
                'name' => 'CargoTrace Admin (Fleet Director)',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'company_name' => 'CargoTrace Maritime Global HQ',
                'phone' => '+1-555-010-9900',
                'preferred_currency' => 'USD',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // 2. Port Operations Harbor Master / Operator
        User::firstOrCreate(
            ['email' => 'operator@cargotrace.test'],
            [
                'name' => 'Port Operations Harbor Master',
                'password' => Hash::make('password'),
                'role' => 'operator',
                'company_name' => 'CargoTrace Seaport Operations Center',
                'phone' => '+65-6789-0123',
                'preferred_currency' => 'SGD',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // 3. Customer 1 (Indonesia - PT Nusantara Ocean Cargo)
        User::firstOrCreate(
            ['email' => 'customer@cargotrace.test'],
            [
                'name' => 'PT Nusantara Ocean Cargo',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'company_name' => 'Nusantara Ocean Shipping Lines Ltd.',
                'phone' => '+62-21-555-4321',
                'preferred_currency' => 'IDR',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // 4. Customer 2 (Europe - EuroPacific Maritime B.V.)
        User::firstOrCreate(
            ['email' => 'customer2@cargotrace.test'],
            [
                'name' => 'EuroPacific Maritime B.V.',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'company_name' => 'EuroPacific Container Logistics B.V.',
                'phone' => '+31-10-890-1234',
                'preferred_currency' => 'EUR',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // 5. Customer 3 (Americas - Pacific Rim Logistics LLC)
        User::firstOrCreate(
            ['email' => 'customer3@cargotrace.test'],
            [
                'name' => 'Pacific Rim Logistics LLC',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'company_name' => 'Pacific Rim Transpacific Logistics LLC',
                'phone' => '+1-310-555-7890',
                'preferred_currency' => 'USD',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // 6. Customer 4 (East Asia - Nippon Kaiun Trading Co.)
        User::firstOrCreate(
            ['email' => 'customer4@cargotrace.test'],
            [
                'name' => 'Nippon Kaiun Trading Co.',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'company_name' => 'Nippon Kaiun Container Freight Corp.',
                'phone' => '+81-3-5555-6789',
                'preferred_currency' => 'JPY',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}

