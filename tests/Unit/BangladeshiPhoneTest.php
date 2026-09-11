<?php

use App\Rules\BangladeshiPhone;

it('normalises every common way a BD mobile number is written', function (string $input) {
    expect(BangladeshiPhone::normalise($input))->toBe('+8801712345678');
})->with([
    'local' => '01712345678',
    'country code' => '8801712345678',
    'plus country' => '+8801712345678',
    'spaced' => '017 1234 5678',
    'dashed' => '017-1234-5678',
    'plus and spaced' => '+880 1712 345678',
    'bracketed' => '(+880) 1712-345678',
    'padded' => '  01712345678  ',
]);

it('accepts every live operator prefix', function (string $prefix) {
    expect(BangladeshiPhone::normalise("0{$prefix}12345678"))->toBe("+880{$prefix}12345678");
})->with(['13', '14', '15', '16', '17', '18', '19']);

it('rejects numbers that are not valid BD mobiles', function (string $input) {
    expect(BangladeshiPhone::normalise($input))->toBeNull();
})->with([
    'too short' => '0171234567',
    'too long' => '017123456789',
    'dead prefix 011' => '01112345678',
    'dead prefix 012' => '01212345678',
    'landline' => '0212345678',
    'wrong country code' => '+919712345678',
    'letters' => '01712abc678',
    'empty' => '',
    'only symbols' => '+-()',
]);
