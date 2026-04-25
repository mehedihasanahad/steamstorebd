<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Jobs\SendOrderCodesEmail;
use App\Services\OrderService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve_send_money')
                ->label('Approve & Send Codes')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn() => $this->record->status === 'pending_review')
                ->requiresConfirmation()
                ->modalHeading('Approve Send Money Order')
                ->modalDescription(fn() => 'Confirm you have verified the ' . $this->record->paymentMethodLabel() . ' transaction ID: ' . $this->record->send_money_trx_id . '. This will mark the order as paid and send the gift card codes to the customer.')
                ->modalSubmitActionLabel('Yes, Approve & Send Codes')
                ->action(function (OrderService $orderService) {
                    try {
                        $orderService->approveSendMoneyOrder($this->record);
                        $this->refreshFormData(['status']);
                        Notification::make()->title('Order approved! Codes sent to customer.')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
                    }
                }),

            Actions\Action::make('resend_codes')
                ->label('Resend Codes Email')
                ->icon('heroicon-o-envelope')
                ->visible(fn() => $this->record->isPaid())
                ->action(function () {
                    dispatch(new SendOrderCodesEmail($this->record));
                    Notification::make()->title('Codes email queued')->success()->send();
                }),

            Actions\Action::make('mark_completed')
                ->label('Mark as Completed')
                ->color('success')
                ->visible(fn() => $this->record->status === 'paid')
                ->action(function () {
                    $this->record->update(['status' => 'completed']);
                    $this->refreshFormData(['status']);
                    Notification::make()->title('Marked as completed')->success()->send();
                }),
        ];
    }

    public function getTitle(): string
    {
        return 'Order #' . $this->record->order_number;
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function getViewData(): array
    {
        return [
            'order' => $this->record->load(['items.giftCard', 'items.orderItemCodes.giftCardCode', 'bkashPayment']),
        ];
    }
}
