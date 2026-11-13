<?php

use App\Rules\Iso6346ContainerNumber;
use App\Support\Iso6346;

function validateContainerNumber(mixed $value): ?string
{
    $rule = new Iso6346ContainerNumber;
    $errorMessage = null;

    $rule->validate('container_number', $value, function (string $msg) use (&$errorMessage) {
        $errorMessage = $msg;
    });

    return $errorMessage;
}

test('Iso6346 rule passes for valid container numbers across U, J, and Z categories', function (string $containerNumber) {
    $error = validateContainerNumber($containerNumber);
    expect($error)->toBeNull();
})->with([
    'Standard freight container (U)' => 'MSKU1234565',
    'Detachable container equipment (J)' => 'HLXJ1000000',
    'Trailer or chassis (Z)' => 'CMAZ2000008',
    'Formatted with spaces' => 'MSKU 123456 5',
    'Lowercase input' => 'msku1234565',
    'Another carrier code' => 'CSQU3054383',
]);

test('Iso6346 rule fails for invalid check digit', function (string $containerNumber, int $expectedCheck, int $givenCheck) {
    $error = validateContainerNumber($containerNumber);

    expect($error)->not()->toBeNull()
        ->and($error)->toContain('invalid ISO 6346 check digit')
        ->and($error)->toContain((string) $expectedCheck)
        ->and($error)->toContain((string) $givenCheck);
})->with([
    'Check digit 0 instead of 5' => ['MSKU1234560', 5, 0],
    'Check digit 9 instead of 3' => ['CSQU3054389', 3, 9],
    'Check digit 1 instead of 8' => ['CMAZ2000001', 8, 1],
]);

test('Iso6346 rule fails for malformed inputs', function (mixed $input) {
    $error = validateContainerNumber($input);

    expect($error)->not()->toBeNull()
        ->and($error)->toContain('ISO 6346 container number');
})->with([
    'Invalid category letter A' => 'MSKA1234565',
    'Invalid category letter B' => 'MSKB1234565',
    'Too short (10 characters)' => 'MSKU123456',
    'Too long (12 characters)' => 'MSKU12345655',
    'Non-letter in owner code' => '123U1234565',
    'Non-digit in serial number' => 'MSKU12345X5',
    'Special characters' => 'MSKU#123455',
]);

test('Iso6346 format helper outputs standard visual presentation', function () {
    expect(Iso6346::format('MSKU1234565'))->toBe('MSKU 123456 5')
        ->and(Iso6346::format('msku1234565'))->toBe('MSKU 123456 5')
        ->and(Iso6346::format('MSKU 123456 5'))->toBe('MSKU 123456 5')
        ->and(Iso6346::format(null))->toBeNull()
        ->and(Iso6346::format(''))->toBeNull();
});
