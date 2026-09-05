<?php

use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use App\Models\SiteSetting;

beforeEach(function () {
    $this->category = GiftCardCategory::create([
        'name' => 'Steam Wallet',
        'slug' => 'steam-wallet',
    ]);

    GiftCard::create([
        'category_id'           => $this->category->id,
        'name'                  => 'Steam Wallet $10',
        'slug'                  => 'steam-wallet-10',
        'denomination'          => 10,
        'denomination_currency' => 'USD',
        'denomination_bdt'      => 1200,
        'price_bdt'             => 1250,
        'stock_count'           => 5,
    ]);
});

function enableWhatsapp(): void
{
    SiteSetting::set('product_chat_whatsapp_enabled', true, 'product_chat');
    SiteSetting::set('product_chat_whatsapp_number', '+880 1711-223344', 'product_chat');
}

function enableMessenger(): void
{
    SiteSetting::set('product_chat_messenger_enabled', true, 'product_chat');
    SiteSetting::set('product_chat_messenger_username', 'SteamStoreBD', 'product_chat');
}

it('shows no chat buttons when both channels are off', function () {
    $this->get('/product/steam-wallet')
        ->assertOk()
        ->assertDontSee('wa.me')
        ->assertDontSee('m.me')
        ->assertDontSee('Need help? Order via chat');
});

it('shows only the whatsapp button when only whatsapp is configured', function () {
    enableWhatsapp();

    $this->get('/product/steam-wallet')
        ->assertOk()
        ->assertSee('https://wa.me/8801711223344', escape: false)
        ->assertSee('Need help? Order via chat')
        // Assert on the anchor, not on the bare domain: the shared Alpine block
        // always mentions both hosts inside its href builders even when only one
        // channel is enabled.
        ->assertDontSee('href="https://m.me/', escape: false)
        ->assertDontSee('Chat about Steam Wallet on Messenger');
});

it('shows only the messenger button when only messenger is configured', function () {
    enableMessenger();

    $this->get('/product/steam-wallet')
        ->assertOk()
        ->assertSee('https://m.me/SteamStoreBD', escape: false)
        ->assertDontSee('href="https://wa.me/', escape: false)
        ->assertDontSee('Order Steam Wallet on WhatsApp');
});

it('shows both buttons when both are configured', function () {
    enableWhatsapp();
    enableMessenger();

    $this->get('/product/steam-wallet')
        ->assertOk()
        ->assertSee('https://wa.me/8801711223344', escape: false)
        ->assertSee('https://m.me/SteamStoreBD', escape: false);
});

it('hides the button when the toggle is on but the number is blank', function () {
    SiteSetting::set('product_chat_whatsapp_enabled', true, 'product_chat');
    SiteSetting::set('product_chat_whatsapp_number', '', 'product_chat');

    $this->get('/product/steam-wallet')
        ->assertOk()
        ->assertDontSee('wa.me');
});

// The component pushes its Alpine registration to the 'scripts' stack. If the
// host layout ever loses @stack('scripts') the links silently stop updating
// when a denomination is picked, falling back to the server-rendered href.
it('pushes the alpine registration into the page', function () {
    enableWhatsapp();

    $this->get('/product/steam-wallet')
        ->assertOk()
        ->assertSee("Alpine.data('productChatButtons'", escape: false)
        ->assertSee("toLocaleString('en-US'", escape: false);
});

it('server renders a working fallback message with no denomination selected', function () {
    enableWhatsapp();

    $expected = rawurlencode("Hi! I'm interested in Steam Wallet.\n" . url('/product/steam-wallet'));

    $this->get('/product/steam-wallet')
        ->assertOk()
        ->assertSee($expected, escape: false);
});
