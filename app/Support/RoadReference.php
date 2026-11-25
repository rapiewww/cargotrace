<?php

namespace App\Support;

class RoadReference
{
    /**
     * Deterministic Indonesian-style commercial vehicle license plate generator.
     */
    public static function vehiclePlate(string $regionCode, int $serial): string
    {
        $code = strtoupper(trim($regionCode));
        $suffixes = ['XZ', 'TP', 'AC', 'MD', 'KT', 'LM', 'SR', 'BP', 'JN', 'RK', 'DY', 'NS'];
        $suffix = $suffixes[abs($serial) % count($suffixes)];

        $baseNumber = match ($code) {
            'BL' => 8100 + (abs($serial) % 899),
            'BK' => 8400 + (abs($serial) % 899),
            'B' => 9100 + (abs($serial) % 899),
            default => 8000 + (abs($serial) % 1899),
        };

        return match ($code) {
            'NL' => sprintf('NL-%02d-B%s', 10 + (abs($serial) % 89), $suffix),
            'FR' => sprintf('FR-%03d-%s', 100 + (abs($serial) % 899), $suffix),
            'IN' => sprintf('MH 46 T %04d', 8000 + (abs($serial) % 1899)),
            default => sprintf('%s %04d %s', $code !== '' ? $code : 'BK', $baseNumber, $suffix),
        };
    }

    /**
     * Deterministic CMR consignment note number.
     */
    public static function cmrNumber(int $year, int $serial): string
    {
        return sprintf('CMR-%04d-%05d', $year, abs($serial));
    }

    /**
     * Deterministic Ocean Bill of Lading reference number.
     */
    public static function billOfLading(string $carrierCode, int $year, int $serial): string
    {
        $cleanCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $carrierCode));

        return sprintf('B/L-%s-%04d-%05d', $cleanCode, $year, abs($serial));
    }
}
