<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WithdrawalRequestResource\Pages;
use App\Models\WalletTransaction;
use App\Models\WithdrawalRequest;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class WithdrawalRequestResource extends Resource
{
    protected static ?string $model = WithdrawalRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Withdrawals';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->description(fn(WithdrawalRequest $r) => $r->user?->email ?? ''),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn($state) => '৳ ' . number_format($state, 0))
                    ->sortable(),
                Tables\Columns\TextColumn::make('method')
                    ->label('Method')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'bkash' => 'bKash',
                        'nagad' => 'Nagad',
                        default => ucfirst($state),
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'bkash' => 'danger',
                        'nagad' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('transfer_type')
                    ->label('Transfer Type')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'cashout'    => 'Cash Out',
                        'send_money' => 'Send Money',
                        default      => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending'  => 'warning',
                        'approved' => 'info',
                        'paid'     => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    }),
                Tables\Columns\TextColumn::make('admin_note')
                    ->label('Note')
                    ->placeholder('—')
                    ->limit(40),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'  => 'Pending',
                        'approved' => 'Approved',
                        'paid'     => 'Paid',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\SelectFilter::make('method')
                    ->options([
                        'bkash' => 'bKash',
                        'nagad' => 'Nagad',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->visible(fn(WithdrawalRequest $r) => $r->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Withdrawal Request')
                    ->modalDescription(fn(WithdrawalRequest $r) => "Mark ৳" . number_format($r->amount, 0) . " withdrawal for {$r->user?->name} as approved?")
                    ->action(function (WithdrawalRequest $record) {
                        $record->update([
                            'status'       => 'approved',
                            'processed_at' => now(),
                        ]);
                        Notification::make()->title('Request approved')->success()->send();
                    }),

                Tables\Actions\Action::make('mark_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn(WithdrawalRequest $r) => in_array($r->status, ['pending', 'approved']))
                    ->form([
                        Textarea::make('admin_note')
                            ->label('Note (optional)')
                            ->placeholder('Transaction ID, reference, etc.')
                            ->rows(2),
                    ])
                    ->modalHeading('Mark as Paid')
                    ->action(function (WithdrawalRequest $record, array $data) {
                        $record->update([
                            'status'       => 'paid',
                            'admin_note'   => $data['admin_note'] ?? null,
                            'processed_at' => now(),
                        ]);
                        Notification::make()->title('Marked as paid')->success()->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(WithdrawalRequest $r) => in_array($r->status, ['pending', 'approved']))
                    ->form([
                        Textarea::make('admin_note')
                            ->label('Reason for rejection (optional)')
                            ->rows(2),
                    ])
                    ->modalHeading('Reject & Refund Withdrawal')
                    ->modalDescription('The wallet amount will be refunded to the user.')
                    ->action(function (WithdrawalRequest $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $user       = $record->user;
                            $newBalance = (float) $user->wallet_balance + (float) $record->amount;

                            $user->update(['wallet_balance' => $newBalance]);

                            WalletTransaction::create([
                                'user_id'       => $user->id,
                                'amount'        => $record->amount,
                                'type'          => 'credit',
                                'source'        => 'withdrawal_refund',
                                'description'   => 'Withdrawal request rejected — amount refunded',
                                'balance_after' => $newBalance,
                            ]);

                            $record->update([
                                'status'       => 'rejected',
                                'admin_note'   => $data['admin_note'] ?? null,
                                'processed_at' => now(),
                            ]);
                        });

                        Notification::make()->title('Request rejected and amount refunded')->success()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWithdrawalRequests::route('/'),
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
