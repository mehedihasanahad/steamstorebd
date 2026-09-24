<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GiftCardCodeResource\Pages;
use App\Models\GiftCard;
use App\Models\GiftCardCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class GiftCardCodeResource extends Resource
{
    protected static ?string $model = GiftCardCode::class;
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'Catalog';
    protected static ?int $navigationSort = 3;
    protected static ?string $label = 'Gift Card Code';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('gift_card_id')
                ->label('Gift Card')
                ->options(GiftCard::orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->required(),
            Forms\Components\TextInput::make('code')
                ->label('Code')
                ->helperText('Two codes that make up one card go on one line, separated by a spaced plus: AAAA-1111 + BBBB-2222. That is still one card in stock and the buyer receives both. A plus with no spaces around it is treated as part of the code.')
                ->required()
                ->unique(GiftCardCode::class, 'code', ignoreRecord: true),
            Forms\Components\Select::make('status')
                ->options([
                    'available' => 'Available',
                    'reserved'  => 'Reserved',
                    'sold'      => 'Sold',
                    'revoked'   => 'Revoked',
                ])
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    // Masked per part, so a card stocked as two codes still
                    // looks like two codes at a glance.
                    ->formatStateUsing(fn ($state) => collect(GiftCardCode::split($state))
                        ->map(fn (string $part) => substr($part, 0, 4) . '-****-****-****')
                        ->implode(GiftCardCode::PART_SEPARATOR))
                    ->description(fn (GiftCardCode $record) => $record->isSplit()
                        ? $record->partCount() . ' codes make up this card'
                        : null)
                    ->searchable(),
                Tables\Columns\TextColumn::make('giftCard.name')
                    ->label('Gift Card')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'available',
                        'warning' => 'reserved',
                        'danger'  => 'sold',
                        'gray'    => 'revoked',
                    ])
                    ->tooltip(fn ($state) => $state === 'revoked'
                        ? 'Pulled off an order after the customer already had it. Never resold.'
                        : null),
                Tables\Columns\TextColumn::make('addedBy.name')
                    ->label('Added By'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('gift_card_id')
                    ->options(GiftCard::orderBy('name')->pluck('name', 'id'))
                    ->label('Gift Card'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'available' => 'Available',
                        'reserved'  => 'Reserved',
                        'sold'      => 'Sold',
                        'revoked'   => 'Revoked',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('bulk_import')
                    ->label('Bulk Import Codes')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        Forms\Components\Select::make('gift_card_id')
                            ->label('Gift Card')
                            ->options(GiftCard::where('is_active', true)->pluck('name', 'id'))
                            ->required(),
                        Forms\Components\Textarea::make('codes')
                            ->label('Codes (one card per line)')
                            ->helperText('Two codes that make up one card go on one line, separated by a spaced plus: AAAA-1111 + BBBB-2222. That is still one card in stock and the buyer receives both. A plus with no spaces around it is treated as part of the code.')
                            ->rows(10)
                            ->placeholder("STEAM-XXXX-XXXX-XXXX-XXXX\nSTEAM-YYYY-YYYY-YYYY-YYYY + STEAM-ZZZZ-ZZZZ-ZZZZ-ZZZZ")
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $adminId = Auth::id();
                        $created = 0;
                        $skipped = 0;
                        $split   = 0;

                        // Every individual code already stocked against this
                        // card. A line is refused if any one of its codes is in
                        // here, because selling the same code twice is the one
                        // mistake that costs real money, and a bundle hides a
                        // repeat that an exact match on the whole line misses.
                        $known = GiftCardCode::where('gift_card_id', $data['gift_card_id'])
                            ->pluck('code')
                            ->flatMap(fn (string $code) => GiftCardCode::split($code))
                            ->flip();

                        $lines = array_filter(array_map('trim', explode("\n", $data['codes'])));

                        foreach ($lines as $line) {
                            $parts = GiftCardCode::split($line);

                            if ($parts === []) {
                                continue;
                            }

                            $alreadyStocked = collect($parts)->contains(fn (string $part) => $known->has($part));

                            if ($alreadyStocked || GiftCardCode::where('code', GiftCardCode::normalise($line))->exists()) {
                                $skipped++;
                                continue;
                            }

                            GiftCardCode::create([
                                'gift_card_id'      => $data['gift_card_id'],
                                'code'              => $line,
                                'status'            => 'available',
                                'added_by_admin_id' => $adminId,
                            ]);

                            // Kept in step within the loop, so a later duplicate
                            // in the same paste is caught too.
                            foreach ($parts as $part) {
                                $known[$part] = true;
                            }

                            GiftCard::find($data['gift_card_id'])->increment('stock_count');
                            $created++;
                            $split += count($parts) > 1 ? 1 : 0;
                        }

                        Notification::make()
                            ->title("Imported {$created} cards"
                                . ($split ? " ({$split} made up of more than one code)" : '')
                                . ($skipped ? ", skipped {$skipped} already stocked" : ''))
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
                        Forms\Components\Select::make('gift_card_id')
                            ->label('Gift Card')
                            ->options(GiftCard::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('code')
                            ->label('Full Code')
                            ->helperText('Two codes that make up one card go on one line, separated by a spaced plus: AAAA-1111 + BBBB-2222. That is still one card in stock and the buyer receives both. A plus with no spaces around it is treated as part of the code.')
                            ->required()
                            ->unique(GiftCardCode::class, 'code', ignoreRecord: true),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'available' => 'Available',
                                'reserved'  => 'Reserved',
                                'sold'      => 'Sold',
                                'revoked'   => 'Revoked',
                            ])
                            ->required(),
                    ])
                    ->using(function (GiftCardCode $record, array $data): GiftCardCode {
                        $oldStatus     = $record->status;
                        $oldCardId     = $record->gift_card_id;
                        $newStatus     = $data['status'];
                        $newCardId     = $data['gift_card_id'];

                        $record->update($data);

                        // Sync stock_count when status or gift card changes
                        $cardChanged = $oldCardId !== (int) $newCardId;

                        if ($cardChanged) {
                            // Remove from old card stock if was available
                            if ($oldStatus === 'available') {
                                GiftCard::find($oldCardId)?->decrement('stock_count');
                            }
                            // Add to new card stock if now available
                            if ($newStatus === 'available') {
                                GiftCard::find($newCardId)?->increment('stock_count');
                            }
                        } elseif ($oldStatus !== $newStatus) {
                            if ($oldStatus === 'available') {
                                $record->giftCard->decrement('stock_count');
                            } elseif ($newStatus === 'available') {
                                $record->giftCard->increment('stock_count');
                            }
                        }

                        return $record;
                    })
                    ->successNotificationTitle('Code updated'),

                Tables\Actions\DeleteAction::make()
                    ->before(function (GiftCardCode $record) {
                        if ($record->status === 'available') {
                            $record->giftCard->decrement('stock_count');
                        }
                    })
                    ->successNotificationTitle('Code deleted'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Collection $records) {
                            foreach ($records as $record) {
                                if ($record->status === 'available') {
                                    $record->giftCard->decrement('stock_count');
                                }
                            }
                        })
                        ->successNotificationTitle('Selected codes deleted'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGiftCardCodes::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
