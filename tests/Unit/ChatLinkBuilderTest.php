<?php

use App\Services\ChatLinkBuilder;

function builder(array $overrides = []): ChatLinkBuilder
{
    return new ChatLinkBuilder(
        $overrides['whatsappOn'] ?? true,
        $overrides['whatsappNumber'] ?? '+880 17-11 22 33 44',
        $overrides['whatsappTemplate'] ?? ChatLinkBuilder::DEFAULT_TEMPLATE,
        $overrides['messengerOn'] ?? true,
        $overrides['messengerUsername'] ?? 'SteamStoreBD',
    );
}

it('strips every non-digit from the phone number', function () {
    expect(builder()->whatsappNumber())->toBe('8801711223344');
});

it('disables whatsapp when the number is empty even if the toggle is on', function () {
    expect(builder(['whatsappNumber' => '   '])->whatsappEnabled())->toBeFalse();
});

it('disables whatsapp when the toggle is off even with a valid number', function () {
    expect(builder(['whatsappOn' => false])->whatsappEnabled())->toBeFalse();
});

it('normalises pasted messenger usernames', function (string $input) {
    expect(builder(['messengerUsername' => $input])->messengerUsername())->toBe('SteamStoreBD');
})->with([
    'SteamStoreBD',
    '@SteamStoreBD',
    'm.me/SteamStoreBD',
    'https://m.me/SteamStoreBD',
    'https://www.facebook.com/SteamStoreBD',
    'facebook.com/SteamStoreBD/',
]);

it('disables messenger when the username is empty even if the toggle is on', function () {
    expect(builder(['messengerUsername' => '@'])->messengerEnabled())->toBeFalse();
});

it('reports enabled when either channel is usable', function () {
    expect(builder(['whatsappOn' => false])->enabled())->toBeTrue()
        ->and(builder(['whatsappOn' => false, 'messengerOn' => false])->enabled())->toBeFalse();
});

it('renders every token and preserves the newline', function () {
    $message = builder()->renderMessage([
        '{product}'      => 'Steam Wallet',
        '{denomination}' => '$10',
        '{price}'        => ChatLinkBuilder::formatPrice(1250),
        '{url}'          => 'https://steamstorebd.com/product/steam-wallet',
    ]);

    expect($message)->toBe(
        "Hi! I'm interested in Steam Wallet \$10 ৳1,250.\nhttps://steamstorebd.com/product/steam-wallet"
    );
});

it('collapses cleanly when no denomination is selected', function () {
    $message = builder()->renderMessage([
        '{product}'      => 'Steam Wallet',
        '{denomination}' => '',
        '{price}'        => '',
        '{url}'          => 'https://steamstorebd.com/product/steam-wallet',
    ]);

    expect($message)->toBe(
        "Hi! I'm interested in Steam Wallet.\nhttps://steamstorebd.com/product/steam-wallet"
    );
});

it('drops a line that becomes empty after substitution', function () {
    $message = builder(['whatsappTemplate' => "Hello.\n{url}"])->renderMessage(['{url}' => '']);

    expect($message)->toBe('Hello.');
});

it('falls back to the default template when the admin left it blank', function () {
    expect(builder(['whatsappTemplate' => '   '])->messageTemplate())
        ->toBe(ChatLinkBuilder::DEFAULT_TEMPLATE);
});

it('formats the price with an explicit locale and currency symbol', function () {
    expect(ChatLinkBuilder::formatPrice(1250))->toBe('৳1,250')
        ->and(ChatLinkBuilder::formatPrice('1250.00'))->toBe('৳1,250')
        ->and(ChatLinkBuilder::formatPrice(null))->toBe('')
        ->and(ChatLinkBuilder::formatPrice(''))->toBe('');
});

it('builds a facebook-safe ref token', function () {
    expect(ChatLinkBuilder::refToken('steam-wallet', '$10'))->toBe('product_steam_wallet_10')
        ->and(ChatLinkBuilder::refToken('steam-wallet'))->toBe('product_steam_wallet');
});

it('caps the ref token at 255 characters', function () {
    expect(strlen(ChatLinkBuilder::refToken(str_repeat('a', 400))))->toBe(255);
});

it('raw url encodes the message into the whatsapp url', function () {
    $url = builder()->whatsappUrl("Hi there.\nSecond line");

    expect($url)->toBe('https://wa.me/8801711223344?text=Hi%20there.%0ASecond%20line');
});

it('omits the text parameter when the message is empty', function () {
    expect(builder()->whatsappUrl(''))->toBe('https://wa.me/8801711223344');
});

it('builds messenger urls with and without a ref', function () {
    expect(builder()->messengerUrl('product_steam_wallet'))
        ->toBe('https://m.me/SteamStoreBD?ref=product_steam_wallet')
        ->and(builder()->messengerUrl())->toBe('https://m.me/SteamStoreBD');
});
