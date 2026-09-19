<?php

namespace App\Services;

use App\Jobs\SendOrderCodesEmail;
use App\Models\GiftCard;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The admin side of manual fulfilment: the queue of paid lines still waiting
 * on a person, and what happens when one of them is handed over.
 *
 * Kept apart from OrderService on purpose. OrderService owns money and stock —
 * the paths a payment callback drives. This owns what an admin does afterwards,
 * which is a different actor, a different trigger and a different failure mode.
 */
class FulfilmentService
{
    /**
     * Paid lines still waiting on an admin, oldest first.
     *
     * @return Collection<int, OrderItem>
     */
    public function queue(): Collection
    {
        return OrderItem::query()
            ->awaitingFulfilment()
            ->whereHas('order', fn (Builder $q) => $q->whereIn('status', ['paid', 'processing', 'completed']))
            ->with(['order', 'giftCard.category'])
            ->orderBy('created_at')
            ->get();
    }

    public function pendingCount(): int
    {
        return OrderItem::query()
            ->awaitingFulfilment()
            ->whereHas('order', fn (Builder $q) => $q->whereIn('status', ['paid', 'processing', 'completed']))
            ->count();
    }

    /**
     * Hand one line over.
     *
     * `$payload` is what the buyer receives — a top-up confirmation, or the
     * account credentials for a subscription. It is stored through the
     * encrypted cast on the model and is never logged or put in a subject
     * line.
     *
     * Marking the last outstanding line fulfilled moves the order out of
     * `processing` and sends the delivery e-mail, so an order is never left
     * looking half-done once it is not.
     */
    public function fulfil(OrderItem $item, ?string $payload = null): void
    {
        DB::transaction(function () use ($item, $payload) {
            $item->update([
                'fulfilment_status' => OrderItem::FULFILMENT_FULFILLED,
                'delivered_payload' => $payload,
                'fulfilled_at'      => now(),
            ]);

            $this->settleOrder($item->order->fresh('items'));
        });
    }

    /**
     * Put one line back in the queue — the top-up bounced, the credentials
     * were wrong. The stock stays spent, because the order still stands.
     */
    public function reopen(OrderItem $item): void
    {
        DB::transaction(function () use ($item) {
            $item->update([
                'fulfilment_status' => OrderItem::FULFILMENT_AWAITING,
                'delivered_payload' => null,
                'fulfilled_at'      => null,
            ]);

            $order = $item->order->fresh('items');

            if ($order->status === 'paid') {
                $order->update(['status' => 'processing']);
            }
        });
    }

    /**
     * Cancel one line before it is fulfilled, returning its stock.
     *
     * Only the line's reservation is released; the order itself is left
     * alone, because refunding it is a money decision and belongs to
     * OrderService.
     */
    public function release(OrderItem $item): void
    {
        DB::transaction(function () use ($item) {
            if (! $item->needsFulfilment()) {
                return;
            }

            GiftCard::whereKey($item->gift_card_id)->increment('manual_stock', $item->quantity);

            $item->update(['fulfilment_status' => null]);

            $this->settleOrder($item->order->fresh('items'));
        });
    }

    /**
     * Move an order out of `processing` once nothing on it is outstanding,
     * and tell the buyer.
     */
    private function settleOrder(Order $order): void
    {
        if ($order->isAwaitingFulfilment() || $order->status !== 'processing') {
            return;
        }

        $order->update(['status' => 'paid']);

        dispatch(new SendOrderCodesEmail($order));
    }
}
