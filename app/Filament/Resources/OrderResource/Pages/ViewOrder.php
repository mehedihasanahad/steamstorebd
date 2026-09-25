<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Jobs\SendOrderCodesEmail;
use App\Services\OrderEditService;
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
            Actions\Action::make('edit_items')
                ->label('Edit Items')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->visible(fn () => app(OrderEditService::class)->canEdit($this->record))
                ->url(fn () => static::getResource()::getUrl('edit-items', ['record' => $this->record])),

            Actions\Action::make('cancel_order')
                ->label('Cancel Order')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn() => in_array($this->record->status, ['pending', 'pending_review']))
                ->action(function (OrderService $orderService) {
                    try {
                        $orderService->cancelOrder($this->record);
                        $this->refreshFormData(['status']);
                        Notification::make()->title('Order cancelled successfully')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
                    }
                }),

            Actions\Action::make('refund_order')
                ->label('Refund Order')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn() => in_array($this->record->status, ['paid', 'completed', 'pending_review']))
                ->action(function (OrderService $orderService) {
                    try {
                        $orderService->refundOrder($this->record);
                        $this->refreshFormData(['status']);
                        Notification::make()->title('Order refunded successfully')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
                    }
                }),

            Actions\Action::make('approve_send_money')
                ->label(fn () => $this->record->hasInstantDelivery() ? 'Approve & Send Codes' : 'Approve')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn() => $this->record->status === 'pending_review')
                ->requiresConfirmation()
                ->modalHeading('Approve Send Money Order')
                ->modalDescription(fn() => 'Confirm you have verified the ' . $this->record->paymentMethodLabel() . ' transaction ID: ' . $this->record->send_money_trx_id . '. This will mark the order as paid'
                    . ($this->record->hasInstantDelivery()
                        ? ' and send the gift card codes to the customer.'
                        : '. Nothing is emailed until you fulfil it by hand.'))
                ->modalSubmitActionLabel(fn () => $this->record->hasInstantDelivery()
                    ? 'Yes, Approve & Send Codes'
                    : 'Yes, Approve')
                ->action(function (OrderService $orderService) {
                    try {
                        $orderService->approveSendMoneyOrder($this->record);
                        $this->refreshFormData(['status']);
                        Notification::make()->title($this->record->hasInstantDelivery()
                            ? 'Order approved! Codes sent to customer.'
                            : 'Order approved. Nothing is emailed until you fulfil it below.')->success()->send();
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
            'order' => $this->record->load(['items.giftCard', 'items.orderItemCodes.giftCardCode', 'bkashPayment', 'edits.admin']),
        ];
    }
}
