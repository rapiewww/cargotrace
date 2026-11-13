<?php

namespace Database\Seeders;

use App\Models\Carrier;
use Illuminate\Database\Seeder;

class CarrierSeeder extends Seeder
{
    /**
     * Seed carriers table with 10 real-world ocean container shipping lines.
     */
    public function run(): void
    {
        $carriers = [
            [
                'code' => 'MAEU',
                'name' => 'A.P. Moller – Maersk',
                'mode' => 'SEA',
                'headquarters_country' => 'Denmark',
                'contact_email' => 'maritime.support@maersk.com',
                'website' => 'https://www.maersk.com',
                'is_active' => true,
            ],
            [
                'code' => 'MSCU',
                'name' => 'Mediterranean Shipping Company (MSC)',
                'mode' => 'SEA',
                'headquarters_country' => 'Switzerland',
                'contact_email' => 'vessel.ops@msc.com',
                'website' => 'https://www.msc.com',
                'is_active' => true,
            ],
            [
                'code' => 'CMDU',
                'name' => 'CMA CGM Group',
                'mode' => 'SEA',
                'headquarters_country' => 'France',
                'contact_email' => 'ocean.booking@cma-cgm.com',
                'website' => 'https://www.cma-cgm.com',
                'is_active' => true,
            ],
            [
                'code' => 'COSU',
                'name' => 'COSCO Shipping Lines',
                'mode' => 'SEA',
                'headquarters_country' => 'China',
                'contact_email' => 'container.dispatch@coscon.com',
                'website' => 'https://lines.coscoshipping.com',
                'is_active' => true,
            ],
            [
                'code' => 'HLCU',
                'name' => 'Hapag-Lloyd Container Lines',
                'mode' => 'SEA',
                'headquarters_country' => 'Germany',
                'contact_email' => 'vessel.traffic@hlag.com',
                'website' => 'https://www.hapag-lloyd.com',
                'is_active' => true,
            ],
            [
                'code' => 'ONEY',
                'name' => 'Ocean Network Express (ONE)',
                'mode' => 'SEA',
                'headquarters_country' => 'Singapore',
                'contact_email' => 'ocean.tracking@one-line.com',
                'website' => 'https://www.one-line.com',
                'is_active' => true,
            ],
            [
                'code' => 'EGLV',
                'name' => 'Evergreen Marine Corporation',
                'mode' => 'SEA',
                'headquarters_country' => 'Taiwan',
                'contact_email' => 'booking@evergreen-marine.com',
                'website' => 'https://www.evergreen-marine.com',
                'is_active' => true,
            ],
            [
                'code' => 'YMLU',
                'name' => 'Yang Ming Marine Transport Corp.',
                'mode' => 'SEA',
                'headquarters_country' => 'Taiwan',
                'contact_email' => 'service@yangming.com',
                'website' => 'https://www.yangming.com',
                'is_active' => true,
            ],
            [
                'code' => 'HMMU',
                'name' => 'HMM Co., Ltd.',
                'mode' => 'SEA',
                'headquarters_country' => 'South Korea',
                'contact_email' => 'ocean.logistics@hmm21.com',
                'website' => 'https://www.hmm21.com',
                'is_active' => true,
            ],
            [
                'code' => 'ZIMU',
                'name' => 'ZIM Integrated Shipping Services',
                'mode' => 'SEA',
                'headquarters_country' => 'Israel',
                'contact_email' => 'customer.care@zim.com',
                'website' => 'https://www.zim.com',
                'is_active' => true,
            ],
        ];

        foreach ($carriers as $carrier) {
            Carrier::firstOrCreate(['code' => $carrier['code']], $carrier);
        }
    }
}
