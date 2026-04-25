<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Jobs\SendOrderCodesEmail;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Orders';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer_name')->searchable(),
                Tables\Columns\TextColumn::make('customer_email')->searchable()->color('gray'),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Payment')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'bkash_online'     => 'bKash Online',
                        'bkash_send_money' => 'bKash Send ₼',
                        'nagad_send_money' => 'Nagad Send ₼',
                        default            => $state,
                    })
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'bkash_online'     => 'pink',
                        'bkash_send_money' => 'rose',
                        'nagad_send_money' => 'orange',
                        default            => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total_bdt')
                    ->label('Total')
                    ->formatStateUsing(fn($state) => format_bdt($state))
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray'    => 'pending',
                        'warning' => 'pending_review',
                        'primary' => 'payment_initiated',
                        'info'    => 'paid',
                        'success' => 'completed',
                        'danger'  => fn($state) => \in_array($state, ['failed', 'refunded']),
                    ]),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'            => 'Pending',
                        'pending_review'     => 'Pending Review (Send Money)',
                        'payment_initiated'  => 'Payment Initiated',
                        'paid'               => 'Paid',
                        'processing'         => 'Processing',
                        'completed'          => 'Completed',
                        'failed'             => 'Failed',
                        'refunded'           => 'Refunded',
                    ]),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'bkash_online'     => 'bKash Online',
                        'bkash_send_money' => 'bKash Send Money',
                        'nagad_send_money' => 'Nagad Send Money',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from'),
                        \Filament\Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn($q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'], fn($q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('approve_send_money')
                    ->label('Approve & Send Codes')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn(Order $record) => $record->status === 'pending_review')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Send Money Order')
                    ->modalDescription(fn(Order $record) => 'Confirm you have verified the ' . $record->paymentMethodLabel() . ' transaction ID: ' . $record->send_money_trx_id . '. This will mark the order as paid and send the gift card codes to the customer.')
                    ->modalSubmitActionLabel('Yes, Approve & Send Codes')
                    ->action(function (Order $record, OrderService $orderService) {
                        try {
                            $orderService->approveSendMoneyOrder($record);
                            Notification::make()->title('Order approved! Codes sent to customer.')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
                        }
                    }),

                Tables\Actions\Action::make('resend_codes')
                    ->label('Resend Codes')
                    ->icon('heroicon-o-envelope')
                    ->visible(fn(Order $record) => $record->isPaid())
                    ->action(function (Order $record) {
                        dispatch(new SendOrderCodesEmail($record));
                        Notification::make()->title('Codes email queued')->success()->send();
                    }),

                Tables\Actions\Action::make('mark_completed')
                    ->label('Mark Completed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Order $record) => $record->status === 'paid')
                    ->action(function (Order $record) {
                        $record->update(['status' => 'completed']);
                        Notification::make()->title('Order marked as completed')->success()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view'  => Pages\ViewOrder::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }
}
