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
                ->required()
                ->unique(GiftCardCode::class, 'code', ignoreRecord: true),
            Forms\Components\Select::make('status')
                ->options([
                    'available' => 'Available',
                    'reserved'  => 'Reserved',
                    'sold'      => 'Sold',
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
                    ->formatStateUsing(fn ($state) => substr($state, 0, 4) . '-****-****-****')
                    ->searchable(),
                Tables\Columns\TextColumn::make('giftCard.name')
                    ->label('Gift Card')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'available',
                        'warning' => 'reserved',
                        'danger'  => 'sold',
                    ]),
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
                            ->label('Codes (one per line)')
                            ->rows(10)
                            ->placeholder("STEAM-XXXX-XXXX-XXXX-XXXX\nSTEAM-YYYY-YYYY-YYYY-YYYY")
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $lines   = array_filter(array_map('trim', explode("\n", $data['codes'])));
                        $adminId = Auth::id();
                        $created = 0;
                        $skipped = 0;

                        foreach ($lines as $code) {
                            if (GiftCardCode::where('code', $code)->exists()) {
                                $skipped++;
                                continue;
                            }
                            GiftCardCode::create([
                                'gift_card_id'      => $data['gift_card_id'],
                                'code'              => $code,
                                'status'            => 'available',
                                'added_by_admin_id' => $adminId,
                            ]);
                            GiftCard::find($data['gift_card_id'])->increment('stock_count');
                            $created++;
                        }

                        Notification::make()
                            ->title("Imported {$created} codes" . ($skipped ? ", skipped {$skipped} duplicates" : ''))
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
                            ->required()
                            ->unique(GiftCardCode::class, 'code', ignoreRecord: true),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'available' => 'Available',
                                'reserved'  => 'Reserved',
                                'sold'      => 'Sold',
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
