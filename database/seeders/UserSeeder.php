<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed users table with the four core demo accounts (credentials preserved)
     * plus four fictional spice wholesaler and fragrance house buyer accounts.
     */
    public function run(): void
    {
        $verifiedAt = Carbon::now()->subDays(90);
        $password = Hash::make('password');

        $users = [
            [
                'email' => 'admin@cargotrace.test',
                'name' => 'Hendra Wijaya',
                'role' => 'admin',
                'company_name' => 'Nusa Spice Freight HQ',
                'phone' => '+62-61-555-0100',
                'preferred_currency' => 'USD',
            ],
            [
                'email' => 'operator@cargotrace.test',
                'name' => 'Sari Kusuma',
                'role' => 'operator',
                'company_name' => 'Nusa Spice Freight Operations Desk',
                'phone' => '+65-6789-0123',
                'preferred_currency' => 'SGD',
            ],
            [
                'email' => 'customer@cargotrace.test',
                'name' => 'PT Rempah Nusantara Sejahtera',
                'role' => 'customer',
                'company_name' => 'PT Rempah Nusantara Sejahtera',
                'phone' => '+62-21-555-4321',
                'preferred_currency' => 'IDR',
            ],
            [
                'email' => 'customer2@cargotrace.test',
                'name' => 'Van der Berg Spice Trading B.V.',
                'role' => 'customer',
                'company_name' => 'Van der Berg Spice Trading B.V.',
                'phone' => '+31-10-890-1234',
                'preferred_currency' => 'EUR',
            ],
            [
                'email' => 'customer3@cargotrace.test',
                'name' => "Maison L'Essence Grasse S.A.S.",
                'role' => 'customer',
                'company_name' => "Maison L'Essence Grasse S.A.S.",
                'phone' => '+33-4-93-55-0190',
                'preferred_currency' => 'EUR',
            ],
            [
                'email' => 'customer4@cargotrace.test',
                'name' => 'Hudson Valley Botanicals Inc.',
                'role' => 'customer',
                'company_name' => 'Hudson Valley Botanicals Inc.',
                'phone' => '+1-212-555-0184',
                'preferred_currency' => 'USD',
            ],
            [
                'email' => 'customer5@cargotrace.test',
                'name' => 'Deccan Aroma & Spices Pvt. Ltd.',
                'role' => 'customer',
                'company_name' => 'Deccan Aroma & Spices Pvt. Ltd.',
                'phone' => '+91-22-5550-2410',
                'preferred_currency' => 'INR',
            ],
            [
                'email' => 'customer6@cargotrace.test',
                'name' => 'Al-Mina Gulf Spice Merchants LLC',
                'role' => 'customer',
                'company_name' => 'Al-Mina Gulf Spice Merchants LLC',
                'phone' => '+971-4-555-0162',
                'preferred_currency' => 'USD',
            ],
        ];

        foreach ($users as $data) {
            User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $password,
                    'role' => $data['role'],
                    'company_name' => $data['company_name'],
                    'phone' => $data['phone'],
                    'preferred_currency' => $data['preferred_currency'],
                    'is_active' => true,
                    'email_verified_at' => $verifiedAt,
                ]
            );
        }
    }
}
