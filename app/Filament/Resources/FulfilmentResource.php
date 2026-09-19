<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FulfilmentResource\Pages;
use App\Models\OrderItem;
use App\Services\FulfilmentService;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The manual fulfilment queue: paid lines that an admin has to top up or send
 * credentials for.
 *
 * Scoped to order items rather than orders because an order can be half
 * delivered — its gift cards have already reached the customer while its
 * top-up has not — and the thing an admin acts on is the line, not the order.
 */
class FulfilmentResource extends Resource
{
    protected static ?string $model = OrderItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationGroup = 'Orders';

    protected static ?string $navigationLabel = 'Fulfilment Queue';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'fulfilment item';

    public static function getNavigationBadge(): ?string
    {
        $pending = app(FulfilmentService::class)->pendingCount();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /**
     * Only lines that need a person, on orders that have actually been paid
     * for. A line on an unpaid order is not work; it is a reservation.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('fulfilment_status')
            ->whereHas('order', fn (Builder $q) => $q->whereIn('status', ['paid', 'processing', 'completed']))
            ->with(['order', 'giftCard.category']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->searchable()
                    ->url(fn (OrderItem $record) => OrderResource::getUrl('view', ['record' => $record->order_id])),

                Tables\Columns\TextColumn::make('order.customer_email')
                    ->label('Customer')
                    ->searchable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('giftCard.name')
                    ->label('Item')
                    ->description(fn (OrderItem $record) => $record->giftCard?->category?->name)
                    ->searchable(),

                Tables\Columns\TextColumn::make('quantity')->label('Qty'),

                Tables\Columns\TextColumn::make('buyer_inputs')
                    ->label('Buyer details')
                    ->formatStateUsing(fn ($state) => static::describeInputs($state))
                    ->placeholder('—')
                    ->wrap(),

                Tables\Columns\TextColumn::make('giftCard.delivery_eta_label')
                    ->label('Promised')
                    ->placeholder('—')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('fulfilment_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state) => $state === OrderItem::FULFILMENT_FULFILLED ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ordered')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('fulfilment_status')
                    ->label('Status')
                    ->options([
                        OrderItem::FULFILMENT_AWAITING  => 'Awaiting',
                        OrderItem::FULFILMENT_FULFILLED => 'Fulfilled',
                    ])
                    ->default(OrderItem::FULFILMENT_AWAITING),
            ])
            ->actions([
                Tables\Actions\Action::make('fulfil')
                    ->label('Mark fulfilled')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (OrderItem $record) => $record->needsFulfilment())
                    ->form([
                        Textarea::make('payload')
                            ->label('What to hand the buyer')
                            ->helperText('Account credentials or a top-up confirmation. Stored encrypted and shown only on the buyer\'s own order page. Leave blank for a top-up that needs no reply.')
                            ->rows(4)
                            ->maxLength(2000),
                    ])
                    ->action(function (OrderItem $record, array $data, FulfilmentService $fulfilment) {
                        $fulfilment->fulfil($record, filled($data['payload'] ?? null) ? $data['payload'] : null);

                        Notification::make()->title('Item marked fulfilled')->success()->send();
                    }),

                Tables\Actions\Action::make('reopen')
                    ->label('Reopen')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Put this line back in the queue. The buyer stops seeing what was delivered.')
                    ->visible(fn (OrderItem $record) => $record->isFulfilled())
                    ->action(function (OrderItem $record, FulfilmentService $fulfilment) {
                        $fulfilment->reopen($record);

                        Notification::make()->title('Item reopened')->success()->send();
                    }),

                Tables\Actions\Action::make('release')
                    ->label('Cancel line')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Return this line\'s stock and drop it from the queue. Refunding the money is a separate decision on the order itself.')
                    ->visible(fn (OrderItem $record) => $record->needsFulfilment())
                    ->action(function (OrderItem $record, FulfilmentService $fulfilment) {
                        $fulfilment->release($record);

                        Notification::make()->title('Line released and stock returned')->success()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFulfilmentItems::route('/'),
        ];
    }

    /** "Player ID: 5123456789 · Zone ID: 1234" */
    public static function describeInputs(mixed $state): string
    {
        if (blank($state) || ! is_array($state)) {
            return '—';
        }

        return collect($state)
            ->map(fn ($value, $key) => \Illuminate\Support\Str::headline((string) $key) . ': ' . $value)
            ->implode(' · ');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
