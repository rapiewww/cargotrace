<?php

use App\Rules\AwbRule;

function validateAwbNumber(mixed $value): ?string
{
    $rule = new AwbRule;
    $errorMessage = null;

    $rule->validate('awb_number', $value, function (string $msg) use (&$errorMessage) {
        $errorMessage = $msg;
    });

    return $errorMessage;
}

test('AwbRule passes for valid air waybill numbers', function (string $awbNumber) {
    $error = validateAwbNumber($awbNumber);
    expect($error)->toBeNull();
})->with([
    'Garuda Indonesia format 126-12345675 (serial 1234567 % 7 = 5)' => '126-12345675',
    'Plain 11 digits without hyphen' => '12612345675',
    'Formatted with spaces' => '126 1234567 5',
    'Serial ending with 0 mod 7: 0000007 % 7 = 0' => '01400000070',
    'Singapore Airlines 618-87654324 (8765432 % 7 = 4)' => '618-87654324',
]);

test('AwbRule fails for wrong check digit', function (string $awbNumber, int $expected, int $actual) {
    $error = validateAwbNumber($awbNumber);
    expect($error)->not()->toBeNull()
        ->and($error)->toContain('invalid AWB check digit')
        ->and($error)->toContain((string) $expected)
        ->and($error)->toContain((string) $actual);
})->with([
    'Check digit 2 instead of 5' => ['12612345672', 5, 2],
    'Check digit 0 instead of 4' => ['61887654320', 4, 0],
]);

test('AwbRule fails for malformed inputs', function (mixed $input) {
    $error = validateAwbNumber($input);
    expect($error)->not()->toBeNull()
        ->and($error)->toContain('11-digit air waybill number');
})->with([
    'Too short (10 digits)' => '1261234567',
    'Too long (12 digits)' => '126123456755',
    'Contains letters' => '1261234567A',
    'Contains symbols' => '126#12345675',
]);
