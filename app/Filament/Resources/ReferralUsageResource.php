<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralUsageResource\Pages;
use App\Models\Order;
use App\Models\ReferralUsage;
use App\Models\WalletTransaction;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class ReferralUsageResource extends Resource
{
    protected static ?string $model = ReferralUsage::class;
    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Referral Usages';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('referrer.name')
                    ->label('Referrer')
                    ->searchable()
                    ->description(fn(ReferralUsage $r) => $r->referrer?->referral_code ?? ''),
                Tables\Columns\TextColumn::make('referee.name')
                    ->label('Buyer')
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('discount_given')
                    ->label('Buyer Discount')
                    ->formatStateUsing(fn($state) => '৳ ' . number_format($state, 0))
                    ->sortable(),
                Tables\Columns\TextColumn::make('owner_reward')
                    ->label('Referrer Reward')
                    ->formatStateUsing(fn($state) => '৳ ' . number_format($state, 0))
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending'   => 'warning',
                        'credited'  => 'success',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'   => 'Pending',
                        'credited'  => 'Credited',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('manual_credit')
                    ->label('Credit Wallet')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn(ReferralUsage $record) => $record->status === 'pending' && $record->owner_reward > 0)
                    ->requiresConfirmation()
                    ->modalHeading('Manually Credit Referrer Wallet')
                    ->modalDescription(fn(ReferralUsage $record) => "Credit ৳" . number_format($record->owner_reward, 0) . " to {$record->referrer?->name}'s wallet?")
                    ->action(function (ReferralUsage $record) {
                        if (! $record->referrer || $record->status !== 'pending') {
                            return;
                        }

                        DB::transaction(function () use ($record) {
                            $referrer   = $record->referrer;
                            $newBalance = (float) $referrer->wallet_balance + (float) $record->owner_reward;

                            $referrer->update(['wallet_balance' => $newBalance]);

                            WalletTransaction::create([
                                'user_id'        => $referrer->id,
                                'amount'         => $record->owner_reward,
                                'type'           => 'credit',
                                'source'         => 'referral_reward',
                                'description'    => 'Manual referral reward credit by admin',
                                'reference_id'   => $record->order_id,
                                'reference_type' => Order::class,
                                'balance_after'  => $newBalance,
                            ]);

                            $record->update(['status' => 'credited']);
                        });

                        Notification::make()->title('Wallet credited successfully')->success()->send();
                    }),

            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReferralUsages::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $model): bool
    {
        return false;
    }
}
