<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Exceptions\OrderEditException;
use App\Filament\Resources\OrderResource;
use App\Jobs\SendOrderCodesEmail;
use App\Models\GiftCard;
use App\Models\OrderEdit;
use App\Services\OrderEditService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Admin-facing screen for changing what a customer actually ordered.
 *
 * The page only collects intent — every inventory and money change is done by
 * OrderEditService inside one transaction.
 */
class EditOrderItems extends Page
{
    use InteractsWithFormActions;
    use InteractsWithRecord {
        configureAction as configureActionRecord;
    }

    protected static string $resource = OrderResource::class;

    protected static string $view = 'filament.resources.order-resource.pages.edit-order-items';

    protected static ?string $title = 'Edit Order Items';

    protected static ?string $breadcrumb = 'Edit Items';

    public ?array $data = [];

    /** @var Collection<int, GiftCard>|null */
    protected ?Collection $giftCardCache = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->record->load('items.giftCard');

        abort_unless(static::getResource()::canView($this->record), 403);

        if (! app(OrderEditService::class)->canEdit($this->record)) {
            Notification::make()
                ->title('This order can no longer be edited')
                ->body('Orders that are ' . $this->record->status . ' are closed to item changes.')
                ->danger()
                ->send();

            $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));

            return;
        }

        $this->form->fill([
            'items' => $this->record->items->map(fn ($item) => [
                'gift_card_id'   => $item->gift_card_id,
                'quantity'       => $item->quantity,
                'unit_price_bdt' => (float) $item->unit_price_bdt,
            ])->all(),
            'disposition'  => OrderEditService::DISPOSITION_RESTOCK,
            'notify'       => $this->deliversCodes(),
            'reason'       => null,
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Edit Order #' . $this->record->order_number;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->record->customer_name . ' — ' . $this->record->customer_email;
    }

    public function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::FiveExtraLarge;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Delivered codes')
                    ->description('This order has already been paid and its codes emailed to the customer. Anything you remove here is a code they are still holding.')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->visible(fn () => $this->deliversCodes())
                    ->schema([
                        Forms\Components\Radio::make('disposition')
                            ->label('When a delivered code is removed from this order')
                            ->options([
                                OrderEditService::DISPOSITION_RESTOCK => 'Return it to stock — only if you are certain the customer never used it',
                                OrderEditService::DISPOSITION_REVOKE  => 'Revoke it — the code is burned and never resold',
                            ])
                            ->default(OrderEditService::DISPOSITION_RESTOCK)
                            ->required(),
                    ]),

                Forms\Components\Section::make('Line items')
                    ->description('Change a quantity, drop a product, or add a new one. Existing lines keep the price the customer agreed to.')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('')
                            ->schema([
                                Forms\Components\Select::make('gift_card_id')
                                    ->label('Product')
                                    ->options(fn () => $this->giftCardOptions())
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                                        $card = $this->giftCards()->get((int) $state);

                                        if ($card) {
                                            $set('unit_price_bdt', (float) $card->price_bdt);
                                        }
                                    })
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->minValue(1)
                                    ->step(1)
                                    ->required()
                                    ->default(1)
                                    ->live(onBlur: true),

                                Forms\Components\TextInput::make('unit_price_bdt')
                                    ->label('Unit price (৳)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->helperText('Override only if you agreed a different price.'),
                            ])
                            ->columns(5)
                            ->addActionLabel('Add product')
                            ->reorderable(false)
                            ->live()
                            ->minItems(1)
                            ->itemLabel(fn (array $state) => $this->giftCards()->get((int) ($state['gift_card_id'] ?? 0))?->name)
                            ->required(),
                    ]),

                Forms\Components\Section::make('Settlement')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Forms\Components\Placeholder::make('settlement_preview')
                            ->label('')
                            ->content(fn (Get $get) => view(
                                'filament.resources.order-resource.pages.partials.settlement-preview',
                                ['settlement' => $this->settlement($get('items') ?? [])],
                            )),
                    ]),

                Forms\Components\Section::make('Apply the change')
                    ->schema([
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason for this edit')
                            ->placeholder('e.g. Customer asked to swap the $20 card for two $10 cards over WhatsApp.')
                            ->required()
                            ->minLength(5)
                            ->maxLength(1000)
                            ->rows(3)
                            ->helperText('Stored on the order history so anyone can see why the total changed.'),

                        Forms\Components\Toggle::make('notify')
                            ->label('Email the updated codes to the customer')
                            ->helperText('Sends the full current code list for this order.')
                            ->visible(fn () => $this->deliversCodes()),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        try {
            $edit = app(OrderEditService::class)->apply(
                order: $this->record,
                lines: $data['items'],
                admin: auth()->user(),
                reason: $data['reason'],
                disposition: $data['disposition'] ?? OrderEditService::DISPOSITION_RESTOCK,
            );
        } catch (OrderEditException $e) {
            Notification::make()
                ->title('Order not changed')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        if (($data['notify'] ?? false) && $this->deliversCodes()) {
            dispatch(new SendOrderCodesEmail($this->record->fresh()));
        }

        Notification::make()
            ->title('Order updated')
            ->body($this->settlementMessage($edit))
            ->success()
            ->persistent()
            ->send();

        $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Apply changes')
                ->submit('save')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Apply these order changes?')
                ->modalDescription(fn () => $this->deliversCodes()
                    ? 'This order is already paid. New products are delivered immediately and removed codes are handled by the disposition you chose.'
                    : 'Reserved codes will be adjusted to match the new quantities.')
                ->modalSubmitActionLabel('Yes, apply'),

            Actions\Action::make('cancel')
                ->label('Cancel')
                ->color('gray')
                ->url(fn () => static::getResource()::getUrl('view', ['record' => $this->record])),
        ];
    }

    /**
     * Money impact of the currently entered lines, recomputed on every keystroke
     * so the admin sees the settlement before committing.
     *
     * Mirrors OrderEditService::reconcileDiscounts — the service stays the
     * authority, this is only the preview.
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<string, float|bool>
     */
    public function settlement(array $lines): array
    {
        $subtotal = 0.0;

        foreach ($lines as $line) {
            $subtotal += (float) ($line['unit_price_bdt'] ?? 0) * (int) ($line['quantity'] ?? 0);
        }

        $subtotal = round($subtotal, 2);

        $referral = round(min((float) $this->record->referral_discount_bdt, $subtotal), 2);
        $wallet   = round(min((float) $this->record->wallet_discount_bdt, max(0, $subtotal - $referral)), 2);
        $total    = round(max(0, $subtotal - $referral - $wallet), 2);

        return [
            'subtotal'       => $subtotal,
            'referral'       => $referral,
            'wallet'         => $wallet,
            'wallet_refund'  => round((float) $this->record->wallet_discount_bdt - $wallet, 2),
            'total'          => $total,
            'total_before'   => (float) $this->record->total_bdt,
            'delta'          => round($total - (float) $this->record->total_bdt, 2),
            'already_paid'   => $this->deliversCodes(),
        ];
    }

    protected function settlementMessage(OrderEdit $edit): string
    {
        $delta = (float) $edit->balance_delta_bdt;

        $message = 'New total ' . format_bdt($edit->total_after_bdt) . '.';

        if (! $this->deliversCodes()) {
            return $message . ' The customer pays the new total.';
        }

        if ($delta > 0) {
            $message .= ' Collect ' . format_bdt($delta) . ' more from the customer.';
        } elseif ($delta < 0) {
            $message .= ' Refund ' . format_bdt(abs($delta)) . ' to the customer.';
        } else {
            $message .= ' No payment adjustment needed.';
        }

        if ((float) $edit->wallet_refunded_bdt > 0) {
            $message .= ' ' . format_bdt($edit->wallet_refunded_bdt) . ' of unusable wallet credit was returned.';
        }

        $revoked = count($edit->codeIds('revoked'));

        if ($revoked > 0) {
            $message .= " {$revoked} delivered code(s) were revoked.";
        }

        return $message;
    }

    protected function deliversCodes(): bool
    {
        return app(OrderEditService::class)->deliversCodes($this->record);
    }

    /**
     * @return Collection<int, GiftCard>
     */
    protected function giftCards(): Collection
    {
        return $this->giftCardCache ??= GiftCard::query()
            ->withCount(['codes as available_codes_count' => fn ($q) => $q->where('status', 'available')])
            ->orderBy('name')
            ->get()
            ->keyBy('id');
    }

    /**
     * @return array<int, string>
     */
    protected function giftCardOptions(): array
    {
        return $this->giftCards()
            ->mapWithKeys(fn (GiftCard $card) => [
                $card->id => sprintf(
                    '%s — %s (%d in stock)',
                    $card->name,
                    format_bdt($card->price_bdt),
                    $card->available_codes_count,
                ),
            ])
            ->all();
    }
}
