<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Services\ChatLinkBuilder;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;

class SiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $view = 'filament.pages.site-settings';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $title = 'Site Settings';

    public ?array $data = [];

    public function mount(): void
    {
        $keys = [
            'site_name', 'contact_email', 'contact_whatsapp',
            'hero_title', 'hero_subtitle',
            'announcement_bar_text', 'announcement_bar_active',
            'payment_bkash_online_enabled',
            'payment_bkash_send_money_enabled',
            'payment_nagad_send_money_enabled',
            'payment_rocket_send_money_enabled',
            'whatsapp_chat_enabled', 'whatsapp_chat_number', 'whatsapp_chat_message',
            'messenger_chat_enabled', 'messenger_page_username', 'messenger_page_id', 'messenger_use_plugin',
            'referral_enabled',
            'referral_discount_type',
            'referral_discount_value',
            'referral_max_discount_cap',
            'referral_min_order_amount',
            'referral_owner_reward_amount',
            'referral_min_withdrawal_amount',
            'product_chat_whatsapp_enabled',
            'product_chat_whatsapp_number',
            'product_chat_whatsapp_template',
            'product_chat_messenger_enabled',
            'product_chat_messenger_username',
        ];

        $defaults = [
            'payment_bkash_online_enabled'        => true,
            'payment_bkash_send_money_enabled'    => false,
            'payment_nagad_send_money_enabled'    => false,
            'payment_rocket_send_money_enabled'   => false,
            'whatsapp_chat_enabled'               => false,
            'messenger_chat_enabled'              => false,
            'messenger_use_plugin'                => false,
            'referral_enabled'                    => false,
            'referral_discount_type'              => 'flat',
            'referral_discount_value'             => 0,
            'referral_max_discount_cap'           => 0,
            'referral_min_order_amount'           => 0,
            'referral_owner_reward_amount'        => 0,
            'referral_min_withdrawal_amount'      => 50,
            'product_chat_whatsapp_enabled'       => false,
            'product_chat_whatsapp_template'      => ChatLinkBuilder::DEFAULT_TEMPLATE,
            'product_chat_messenger_enabled'      => false,
        ];

        $this->form->fill(
            collect($keys)->mapWithKeys(function ($key) use ($defaults) {
                $raw = SiteSetting::get($key, $defaults[$key] ?? '');
                return [$key => is_string($raw) && in_array($raw, ['1', '0', '']) ? (bool) $raw : $raw];
            })->toArray()
        );
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Settings')
                    ->persistTabInQueryString()
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('General')
                            ->icon('heroicon-o-building-storefront')
                            ->schema([
                                Forms\Components\TextInput::make('site_name')->label('Site Name'),
                                Forms\Components\TextInput::make('contact_email')->label('Contact Email')->email(),
                                Forms\Components\TextInput::make('contact_whatsapp')->label('WhatsApp Number'),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Homepage')
                            ->icon('heroicon-o-home')
                            ->schema([
                                Forms\Components\Section::make('Hero Section')->schema([
                                    Forms\Components\TextInput::make('hero_title')->label('Hero Title'),
                                    Forms\Components\TextInput::make('hero_subtitle')->label('Hero Subtitle'),
                                ])->columns(2),

                                Forms\Components\Section::make('Announcement Bar')->schema([
                                    Forms\Components\Textarea::make('announcement_bar_text')->label('Announcement Text')->rows(2),
                                    Forms\Components\Toggle::make('announcement_bar_active')->label('Active'),
                                ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Chat & Buttons')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Forms\Components\Section::make('Floating Chat Buttons')
                                    ->description('Floating chat buttons shown to visitors on every page.')
                                    ->schema([
                                        Forms\Components\Toggle::make('whatsapp_chat_enabled')
                                            ->label('Enable WhatsApp Chat Button')
                                            ->helperText('Shows a floating WhatsApp button on every page.'),
                                        Forms\Components\TextInput::make('whatsapp_chat_number')
                                            ->label('WhatsApp Number')
                                            ->placeholder('8801XXXXXXXXX')
                                            ->helperText('International format without + or spaces (e.g. 8801711223344).'),
                                        Forms\Components\TextInput::make('whatsapp_chat_message')
                                            ->label('Default WhatsApp Message')
                                            ->placeholder('Hello! I want to buy a Steam gift card.')
                                            ->helperText('Pre-filled message when user opens the WhatsApp link.'),

                                        Forms\Components\Toggle::make('messenger_chat_enabled')
                                            ->label('Enable Messenger Chat Button'),
                                        Forms\Components\TextInput::make('messenger_page_username')
                                            ->label('Facebook Page Username')
                                            ->placeholder('YourPageName')
                                            ->helperText('Used for the m.me/YourPageName link button.'),
                                        Forms\Components\TextInput::make('messenger_page_id')
                                            ->label('Facebook Page ID')
                                            ->placeholder('123456789012345')
                                            ->helperText('Optional — for reference only.'),
                                    ])->columns(1),

                                Forms\Components\Section::make('Product Page Chat Buttons')
                                    ->description('Buttons shown under "Add to Cart" and "Buy Now" on the product details page. Configured separately from the floating buttons above, so product enquiries can go to a different number.')
                                    ->schema([
                                        Forms\Components\Toggle::make('product_chat_whatsapp_enabled')
                                            ->label('Enable WhatsApp Button')
                                            ->live(),
                                        Forms\Components\TextInput::make('product_chat_whatsapp_number')
                                            ->label('WhatsApp Number')
                                            ->placeholder('8801XXXXXXXXX')
                                            ->helperText('International format. Spaces, dashes and + are stripped automatically.')
                                            ->required(fn (Get $get): bool => (bool) $get('product_chat_whatsapp_enabled')),
                                        Forms\Components\Textarea::make('product_chat_whatsapp_template')
                                            ->label('WhatsApp Message Template')
                                            ->rows(3)
                                            ->helperText('Placeholders: {product} {denomination} {price} {url}. Empty placeholders are removed cleanly, so the message still reads correctly before the customer picks an amount.'),

                                        Forms\Components\Toggle::make('product_chat_messenger_enabled')
                                            ->label('Enable Messenger Button')
                                            ->live(),
                                        Forms\Components\TextInput::make('product_chat_messenger_username')
                                            ->label('Facebook Page Username')
                                            ->placeholder('YourPageName')
                                            ->helperText('Messenger cannot pre-fill a message — Facebook removed that. The button opens a chat with product details attached as an invisible ref tag, which only a Messenger bot can read. There is no message template for Messenger because it would do nothing.')
                                            ->required(fn (Get $get): bool => (bool) $get('product_chat_messenger_enabled')),
                                    ])->columns(1),
                            ]),

                        Forms\Components\Tabs\Tab::make('Referral')
                            ->icon('heroicon-o-gift')
                            ->schema([
                                Forms\Components\Section::make('Referral Program')
                                    ->description('Configure the referral system. Referral codes are auto-generated for every user on registration.')
                                    ->schema([
                                        Forms\Components\Toggle::make('referral_enabled')
                                            ->label('Enable Referral Program')
                                            ->helperText('When enabled, users get a unique referral code they can share. Off = no discounts applied and code input hidden at checkout.'),
                                        Forms\Components\Select::make('referral_discount_type')
                                            ->label('Discount Type for Buyer')
                                            ->options(['flat' => 'Flat Amount (৳)', 'percentage' => 'Percentage (%)'])
                                            ->helperText('How the discount is calculated for the person who uses the referral code.'),
                                        Forms\Components\TextInput::make('referral_discount_value')
                                            ->label('Discount Value')
                                            ->numeric()
                                            ->minValue(0)
                                            ->helperText('৳ amount (if flat) or % number (if percentage). E.g. 50 = ৳50 off or 5 = 5% off.'),
                                        Forms\Components\TextInput::make('referral_max_discount_cap')
                                            ->label('Max Discount Cap (৳)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->helperText('For percentage type only — maximum BDT discount allowed. Set 0 for no cap.'),
                                        Forms\Components\TextInput::make('referral_min_order_amount')
                                            ->label('Minimum Order Amount (৳)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->helperText('Referral code only applies if the cart total is at or above this amount. Set 0 for no minimum.'),
                                        Forms\Components\TextInput::make('referral_owner_reward_amount')
                                            ->label('Referrer Wallet Reward (৳)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->helperText('Flat BDT amount credited to the referral code owner\'s wallet after each successful referred order.'),
                                        Forms\Components\TextInput::make('referral_min_withdrawal_amount')
                                            ->label('Minimum Withdrawal Amount (৳)')
                                            ->numeric()
                                            ->minValue(1)
                                            ->helperText('Minimum BDT a user must have to place a withdrawal request. Default: 50.'),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Payments')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Forms\Components\Section::make('Payment Methods')
                                    ->description('Enable or disable payment options shown at checkout. Credentials are configured in .env.')
                                    ->schema([
                                        Forms\Components\Toggle::make('payment_bkash_online_enabled')
                                            ->label('bKash Tokenized Checkout (Online)')
                                            ->helperText('Redirects customer to bKash payment gateway. Requires BKASH_APP_KEY etc. in .env.'),
                                        Forms\Components\Toggle::make('payment_bkash_send_money_enabled')
                                            ->label('bKash Send Money')
                                            ->helperText('Customer sends money manually, then submits TRX ID. Requires BKASH_SEND_MONEY_NUMBER in .env.'),
                                        Forms\Components\Toggle::make('payment_nagad_send_money_enabled')
                                            ->label('Nagad Send Money')
                                            ->helperText('Customer sends money via Nagad manually, then submits TRX ID. Requires NAGAD_SEND_MONEY_NUMBER in .env.'),
                                        Forms\Components\Toggle::make('payment_rocket_send_money_enabled')
                                            ->label('Rocket Send Money')
                                            ->helperText('Customer sends money via Rocket (Dutch-Bangla) manually, then submits TRX ID. Requires ROCKET_SEND_MONEY_NUMBER in .env.'),
                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        // Filament 3.3 does not switch to the tab holding an invalid field, so a
        // required number on a tab the admin is not looking at would otherwise
        // fail with no visible cause. Name the tab in the notification instead.
        try {
            $data = $this->form->getState();
        } catch (ValidationException $e) {
            Notification::make()
                ->title('Could not save')
                ->body('Check the Chat & Buttons tab — an enabled button is missing its number or page username.')
                ->danger()
                ->send();

            throw $e;
        }

        $groups = [
            'site_name'                        => 'general',
            'contact_email'                    => 'general',
            'contact_whatsapp'                 => 'general',
            'hero_title'                       => 'hero',
            'hero_subtitle'                    => 'hero',
            'announcement_bar_text'            => 'announcement',
            'announcement_bar_active'          => 'announcement',
            'payment_bkash_online_enabled'     => 'payment',
            'payment_bkash_send_money_enabled' => 'payment',
            'payment_nagad_send_money_enabled'   => 'payment',
            'payment_rocket_send_money_enabled'  => 'payment',
            'whatsapp_chat_enabled'            => 'chat',
            'whatsapp_chat_number'             => 'chat',
            'whatsapp_chat_message'            => 'chat',
            'messenger_chat_enabled'           => 'chat',
            'messenger_page_username'          => 'chat',
            'messenger_page_id'                => 'chat',
            'messenger_use_plugin'             => 'chat',
            'referral_enabled'                 => 'referral',
            'referral_discount_type'           => 'referral',
            'referral_discount_value'          => 'referral',
            'referral_max_discount_cap'        => 'referral',
            'referral_min_order_amount'        => 'referral',
            'referral_owner_reward_amount'     => 'referral',
            'referral_min_withdrawal_amount'   => 'referral',
            'product_chat_whatsapp_enabled'    => 'product_chat',
            'product_chat_whatsapp_number'     => 'product_chat',
            'product_chat_whatsapp_template'   => 'product_chat',
            'product_chat_messenger_enabled'   => 'product_chat',
            'product_chat_messenger_username'  => 'product_chat',
        ];

        foreach ($data as $key => $value) {
            SiteSetting::set($key, $value, $groups[$key] ?? 'general');
        }

        Notification::make()->title('Settings saved successfully')->success()->send();
    }
}
