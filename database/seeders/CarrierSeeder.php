<?php

namespace Database\Seeders;

use App\Models\Carrier;
use Illuminate\Database\Seeder;

class CarrierSeeder extends Seeder
{
    /**
     * Seed 15 carriers across SEA (5 real liner labels), AIR (5 real cargo airline labels),
     * and ROAD (5 fictional regional trucking companies).
     */
    public function run(): void
    {
        $carriers = [
            // SEA (5 real ocean container lines used as synthetic dataset labels)
            [
                'code' => 'MAEU',
                'name' => 'Maersk',
                'mode' => 'SEA',
                'headquarters_country' => 'Denmark',
                'contact_email' => 'ops.label@maersk-demo.test',
                'website' => 'https://www.maersk.com',
                'is_active' => true,
            ],
            [
                'code' => 'MSCU',
                'name' => 'MSC',
                'mode' => 'SEA',
                'headquarters_country' => 'Switzerland',
                'contact_email' => 'ops.label@msc-demo.test',
                'website' => 'https://www.msc.com',
                'is_active' => true,
            ],
            [
                'code' => 'CMDU',
                'name' => 'CMA CGM',
                'mode' => 'SEA',
                'headquarters_country' => 'France',
                'contact_email' => 'ops.label@cmacgm-demo.test',
                'website' => 'https://www.cma-cgm.com',
                'is_active' => true,
            ],
            [
                'code' => 'HLCU',
                'name' => 'Hapag-Lloyd',
                'mode' => 'SEA',
                'headquarters_country' => 'Germany',
                'contact_email' => 'ops.label@hlag-demo.test',
                'website' => 'https://www.hapag-lloyd.com',
                'is_active' => true,
            ],
            [
                'code' => 'ONEY',
                'name' => 'ONE',
                'mode' => 'SEA',
                'headquarters_country' => 'Singapore',
                'contact_email' => 'ops.label@one-demo.test',
                'website' => 'https://www.one-line.com',
                'is_active' => true,
            ],

            // AIR (5 real air cargo carriers used as synthetic dataset labels)
            [
                'code' => 'GIA',
                'name' => 'Garuda Indonesia Cargo',
                'mode' => 'AIR',
                'headquarters_country' => 'Indonesia',
                'contact_email' => 'airfreight.label@garuda-demo.test',
                'website' => 'https://cargo.garuda-indonesia.com',
                'is_active' => true,
            ],
            [
                'code' => 'SQC',
                'name' => 'Singapore Airlines Cargo',
                'mode' => 'AIR',
                'headquarters_country' => 'Singapore',
                'contact_email' => 'airfreight.label@siacargo-demo.test',
                'website' => 'https://www.siacargo.com',
                'is_active' => true,
            ],
            [
                'code' => 'EKC',
                'name' => 'Emirates SkyCargo',
                'mode' => 'AIR',
                'headquarters_country' => 'United Arab Emirates',
                'contact_email' => 'airfreight.label@skycargo-demo.test',
                'website' => 'https://www.skycargo.com',
                'is_active' => true,
            ],
            [
                'code' => 'QRC',
                'name' => 'Qatar Airways Cargo',
                'mode' => 'AIR',
                'headquarters_country' => 'Qatar',
                'contact_email' => 'airfreight.label@qrcargo-demo.test',
                'website' => 'https://www.qrcargo.com',
                'is_active' => true,
            ],
            [
                'code' => 'LHC',
                'name' => 'Lufthansa Cargo',
                'mode' => 'AIR',
                'headquarters_country' => 'Germany',
                'contact_email' => 'airfreight.label@lhcargo-demo.test',
                'website' => 'https://www.lufthansa-cargo.com',
                'is_active' => true,
            ],

            // ROAD (5 strictly fictional trucking companies)
            [
                'code' => 'SMTR',
                'name' => 'PT Sumatra Rempah Express',
                'mode' => 'ROAD',
                'headquarters_country' => 'Indonesia',
                'contact_email' => 'dispatch@sumatrarempah.test',
                'website' => 'https://sumatrarempah.test',
                'is_active' => true,
            ],
            [
                'code' => 'BKTJ',
                'name' => 'CV Bukit Barisan Haulage',
                'mode' => 'ROAD',
                'headquarters_country' => 'Indonesia',
                'contact_email' => 'fleet@bukitbarisan.test',
                'website' => 'https://bukitbarisan.test',
                'is_active' => true,
            ],
            [
                'code' => 'PRVF',
                'name' => 'Provence Aromatiques Transport S.A.R.L.',
                'mode' => 'ROAD',
                'headquarters_country' => 'France',
                'contact_email' => 'ops@provence-aromatiques.test',
                'website' => 'https://provence-aromatiques.test',
                'is_active' => true,
            ],
            [
                'code' => 'RHNL',
                'name' => 'Rijnmond Spice Logistics B.V.',
                'mode' => 'ROAD',
                'headquarters_country' => 'Netherlands',
                'contact_email' => 'planning@rijnmondspice.test',
                'website' => 'https://rijnmondspice.test',
                'is_active' => true,
            ],
            [
                'code' => 'KHND',
                'name' => 'Konkan Freight Carriers Pvt. Ltd.',
                'mode' => 'ROAD',
                'headquarters_country' => 'India',
                'contact_email' => 'desk@konkanfreight.test',
                'website' => 'https://konkanfreight.test',
                'is_active' => true,
            ],
        ];

        foreach ($carriers as $carrier) {
            Carrier::firstOrCreate(['code' => $carrier['code']], $carrier);
        }
    }
}
