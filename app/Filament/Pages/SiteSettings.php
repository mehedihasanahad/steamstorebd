<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

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
            'whatsapp_chat_enabled', 'whatsapp_chat_number', 'whatsapp_chat_message',
            'messenger_chat_enabled', 'messenger_page_username', 'messenger_page_id', 'messenger_use_plugin',
        ];

        $defaults = [
            'payment_bkash_online_enabled'      => true,
            'payment_bkash_send_money_enabled'  => false,
            'payment_nagad_send_money_enabled'  => false,
            'whatsapp_chat_enabled'             => false,
            'messenger_chat_enabled'            => false,
            'messenger_use_plugin'              => false,
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
                Forms\Components\Section::make('General')->schema([
                    Forms\Components\TextInput::make('site_name')->label('Site Name'),
                    Forms\Components\TextInput::make('contact_email')->label('Contact Email')->email(),
                    Forms\Components\TextInput::make('contact_whatsapp')->label('WhatsApp Number'),
                ])->columns(2),

                Forms\Components\Section::make('Hero Section')->schema([
                    Forms\Components\TextInput::make('hero_title')->label('Hero Title'),
                    Forms\Components\TextInput::make('hero_subtitle')->label('Hero Subtitle'),
                ])->columns(2),

                Forms\Components\Section::make('Announcement Bar')->schema([
                    Forms\Components\Textarea::make('announcement_bar_text')->label('Announcement Text')->rows(2),
                    Forms\Components\Toggle::make('announcement_bar_active')->label('Active'),
                ]),

                Forms\Components\Section::make('Chat & Messaging')
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
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

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
            'payment_nagad_send_money_enabled' => 'payment',
            'whatsapp_chat_enabled'            => 'chat',
            'whatsapp_chat_number'             => 'chat',
            'whatsapp_chat_message'            => 'chat',
            'messenger_chat_enabled'           => 'chat',
            'messenger_page_username'          => 'chat',
            'messenger_page_id'                => 'chat',
            'messenger_use_plugin'             => 'chat',
        ];

        foreach ($data as $key => $value) {
            SiteSetting::set($key, $value, $groups[$key] ?? 'general');
        }

        Notification::make()->title('Settings saved successfully')->success()->send();
    }
}
