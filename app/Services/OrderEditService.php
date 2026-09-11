<?php

namespace App\Services;

use App\Exceptions\OrderEditException;
use App\Models\GiftCard;
use App\Models\GiftCardCode;
use App\Models\Order;
use App\Models\OrderEdit;
use App\Models\OrderItem;
use App\Models\OrderItemCode;
use App\Models\ReferralUsage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rewrites the line items of an existing order.
 *
 * Orders hold two things that cannot be edited by simply saving new rows: the
 * gift card codes attached to each line, and the money already collected. This
 * service is the only place allowed to move both at once, so the two can never
 * drift apart.
 *
 * Codes that were only ever reserved go straight back to stock when a line
 * shrinks. Codes already emailed to the customer (status `sold`, linked through
 * order_item_codes) are a judgement call the admin makes per edit: restock them
 * on the assumption the customer never used them, or burn them as `revoked` so
 * they can never be sold twice.
 */
class OrderEditService
{
    /**
     * Statuses whose line items may still be rewritten.
     *
     * `completed` is deliberately absent: that status is the point where the
     * order is signed off and done, so it closes to item changes the same way
     * cancelled and refunded orders do.
     */
    public const EDITABLE_STATUSES = [
        'pending',
        'pending_review',
        'payment_initiated',
        'paid',
        'processing',
    ];

    /**
     * Statuses where codes are already in the customer's inbox. `completed`
     * belongs here on its own terms even though it can no longer be edited.
     */
    public const DELIVERED_STATUSES = ['paid', 'processing', 'completed'];

    /** Burn the code: it stays out of stock forever. */
    public const DISPOSITION_REVOKE = 'revoke';

    /** Put the code back on the shelf — admin asserts it was never used. */
    public const DISPOSITION_RESTOCK = 'restock';

    public function __construct(private ReferralService $referralService) {}

    public function canEdit(Order $order): bool
    {
        return in_array($order->status, self::EDITABLE_STATUSES, true);
    }

    /** Does an edit to this order hand new codes straight to the customer? */
    public function deliversCodes(Order $order): bool
    {
        return in_array($order->status, self::DELIVERED_STATUSES, true);
    }

    /**
     * Apply a new set of line items to an order.
     *
     * @param  array<int, array{gift_card_id: int|string, quantity: int|string, unit_price_bdt?: float|string|null}>  $lines
     *
     * @throws OrderEditException
     */
    public function apply(
        Order $order,
        array $lines,
        ?User $admin,
        string $reason,
        string $disposition = self::DISPOSITION_RESTOCK,
    ): OrderEdit {
        $reason = trim($reason);

        if ($reason === '') {
            throw OrderEditException::reasonRequired();
        }

        if (! in_array($disposition, [self::DISPOSITION_REVOKE, self::DISPOSITION_RESTOCK], true)) {
            throw OrderEditException::invalidDisposition($disposition);
        }

        return DB::transaction(function () use ($order, $lines, $admin, $reason, $disposition) {
            /** @var Order $order */
            $order = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if (! $this->canEdit($order)) {
                throw OrderEditException::statusNotEditable($order);
            }

            $order->load('items.giftCard');

            $itemsBefore    = $this->snapshot($order);
            $subtotalBefore = (float) $order->subtotal_bdt;
            $totalBefore    = (float) $order->total_bdt;

            $desired  = $this->normaliseLines($lines);
            $delivers = $this->deliversCodes($order);
            $ledger   = ['reserved' => [], 'delivered' => [], 'released' => [], 'revoked' => []];
            $subtotal = 0.0;

            $existing  = $this->collapseDuplicateItems($order, $disposition, $ledger);
            $giftCards = $this->resolveGiftCards($desired->keys()->merge($existing->keys()));

            // Lines the admin kept or added.
            foreach ($desired as $giftCardId => $line) {
                $giftCard = $giftCards->get($giftCardId);

                if (! $giftCard) {
                    throw OrderEditException::unknownGiftCard($giftCardId);
                }

                if ($line['quantity'] < 1) {
                    throw OrderEditException::invalidQuantity($giftCard->name);
                }

                $item = $existing->get($giftCardId);

                // Keep the price the customer originally agreed to; only a new
                // line falls back to today's catalogue price.
                $unitPrice = $line['unit_price_bdt']
                    ?? ($item !== null ? (float) $item->unit_price_bdt : (float) $giftCard->price_bdt);

                $lineSubtotal = round($unitPrice * $line['quantity'], 2);

                if ($item) {
                    $item->update([
                        'quantity'       => $line['quantity'],
                        'unit_price_bdt' => $unitPrice,
                        'subtotal_bdt'   => $lineSubtotal,
                    ]);
                } else {
                    $item = OrderItem::create([
                        'order_id'       => $order->id,
                        'gift_card_id'   => $giftCard->id,
                        'quantity'       => $line['quantity'],
                        'unit_price_bdt' => $unitPrice,
                        'buy_price_bdt'  => $giftCard->buy_price_bdt,
                        'subtotal_bdt'   => $lineSubtotal,
                    ]);
                }

                $this->reconcileCodes($item, $giftCard, $line['quantity'], $delivers, $disposition, $ledger);

                $subtotal += $lineSubtotal;
            }

            // Lines the admin dropped entirely.
            foreach ($existing as $giftCardId => $item) {
                if ($desired->has($giftCardId)) {
                    continue;
                }

                $this->reconcileCodes($item, $giftCards->get($giftCardId), 0, $delivers, $disposition, $ledger);
                $item->delete();
            }

            $subtotal = round($subtotal, 2);

            [$referralApplied, $walletApplied, $walletRefunded] = $this->reconcileDiscounts($order, $subtotal);

            $totalAfter = round(max(0, $subtotal - $referralApplied - $walletApplied), 2);

            $order->update([
                'subtotal_bdt'          => $subtotal,
                'referral_discount_bdt' => $referralApplied,
                'wallet_discount_bdt'   => $walletApplied,
                'total_bdt'             => $totalAfter,
            ]);

            $this->syncStockCounts($giftCards->keys());

            $order->load('items.giftCard');

            return OrderEdit::create([
                'order_id'            => $order->id,
                'admin_id'            => $admin?->id,
                'order_status'        => $order->status,
                'subtotal_before_bdt' => $subtotalBefore,
                'subtotal_after_bdt'  => $subtotal,
                'total_before_bdt'    => $totalBefore,
                'total_after_bdt'     => $totalAfter,
                'balance_delta_bdt'   => round($totalAfter - $totalBefore, 2),
                'wallet_refunded_bdt' => $walletRefunded,
                'items_before'        => $itemsBefore,
                'items_after'         => $this->snapshot($order),
                'code_changes'        => $ledger,
                'reason'              => $reason,
            ]);
        });
    }

    /**
     * Guarantee exactly one line per gift card before anything else runs.
     *
     * The storefront cart is keyed by gift card id so it cannot produce two
     * lines for the same product, but nothing in the schema enforces that. If a
     * duplicate ever exists, keying the lines by gift card id would hide the
     * extra rows and leak their codes, so fold them away here instead.
     *
     * @param  array<string, array<int, int>>  $ledger
     * @return \Illuminate\Support\Collection<int, OrderItem>
     */
    private function collapseDuplicateItems(Order $order, string $disposition, array &$ledger): Collection
    {
        $byGiftCard = $order->items->groupBy('gift_card_id');
        $primaries  = collect();

        foreach ($byGiftCard as $giftCardId => $items) {
            $primaries->put((int) $giftCardId, $items->first());

            foreach ($items->skip(1) as $duplicate) {
                // Target 0 releases whatever the row actually holds, which may
                // differ from its recorded quantity.
                $this->reconcileCodes($duplicate, null, 0, delivers: false, disposition: $disposition, ledger: $ledger);
                $duplicate->delete();
            }
        }

        return $primaries;
    }

    /**
     * Bring the codes attached to a line up or down to `$targetQuantity`.
     *
     * The current count is read from the codes themselves rather than from the
     * old quantity, so a line that already drifted out of sync heals here
     * instead of compounding.
     *
     * @param  array<string, array<int, int>>  $ledger
     */
    private function reconcileCodes(
        OrderItem $item,
        ?GiftCard $giftCard,
        int $targetQuantity,
        bool $delivers,
        string $disposition,
        array &$ledger,
    ): void {
        $attached = GiftCardCode::where('order_item_id', $item->id)
            ->whereIn('status', ['reserved', 'sold'])
            ->count();

        $diff = $targetQuantity - $attached;

        if ($diff > 0) {
            $this->allocateCodes($item, $giftCard, $diff, $delivers, $ledger);
        } elseif ($diff < 0) {
            $this->releaseCodes($item, -$diff, $disposition, $ledger);
        }
    }

    /**
     * @param  array<string, array<int, int>>  $ledger
     */
    private function allocateCodes(OrderItem $item, ?GiftCard $giftCard, int $count, bool $delivers, array &$ledger): void
    {
        $codes = GiftCardCode::where('gift_card_id', $item->gift_card_id)
            ->where('status', 'available')
            ->orderBy('id')
            ->lockForUpdate()
            ->limit($count)
            ->get();

        if ($codes->count() < $count) {
            throw OrderEditException::insufficientStock(
                $giftCard->name ?? "gift card #{$item->gift_card_id}",
                $count,
                $codes->count(),
            );
        }

        foreach ($codes as $code) {
            $code->update([
                'status'        => $delivers ? 'sold' : 'reserved',
                'order_item_id' => $item->id,
            ]);

            if (! $delivers) {
                $ledger['reserved'][] = $code->id;

                continue;
            }

            // Paid orders deliver immediately: the link row is what the codes
            // email reads from.
            OrderItemCode::create([
                'order_item_id'     => $item->id,
                'gift_card_code_id' => $code->id,
            ]);

            $ledger['delivered'][] = $code->id;
        }
    }

    /**
     * Detach `$count` codes from a line, newest first.
     *
     * Reservations the customer never saw go back to stock unconditionally.
     * Delivered codes are only restocked when the admin explicitly says so;
     * otherwise they are revoked, because the customer already has them.
     *
     * @param  array<string, array<int, int>>  $ledger
     */
    private function releaseCodes(OrderItem $item, int $count, string $disposition, array &$ledger): void
    {
        $undelivered = GiftCardCode::where('order_item_id', $item->id)
            ->where('status', 'reserved')
            ->whereDoesntHave('orderItemCode')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->limit($count)
            ->get();

        foreach ($undelivered as $code) {
            $code->update(['status' => 'available', 'order_item_id' => null]);
            $ledger['released'][] = $code->id;
        }

        $remaining = $count - $undelivered->count();

        if ($remaining < 1) {
            return;
        }

        $links = OrderItemCode::where('order_item_id', $item->id)
            ->with('giftCardCode')
            ->orderByDesc('id')
            ->limit($remaining)
            ->get();

        foreach ($links as $link) {
            $code = $link->giftCardCode;
            $link->delete();

            if (! $code) {
                continue;
            }

            if ($disposition === self::DISPOSITION_RESTOCK) {
                $code->update(['status' => 'available', 'order_item_id' => null]);
                $ledger['released'][] = $code->id;

                continue;
            }

            // Keep order_item_id pointing at the line it was sold under for as
            // long as that line exists — the FK nulls itself if the line goes.
            $code->update(['status' => 'revoked']);
            $ledger['revoked'][] = $code->id;
        }
    }

    /**
     * Shrink discounts that no longer fit under the new subtotal.
     *
     * Wallet credit is real money the customer already handed over, so anything
     * that no longer fits goes straight back to their balance.
     *
     * @return array{0: float, 1: float, 2: float} [referral, wallet, walletRefunded]
     */
    private function reconcileDiscounts(Order $order, float $subtotal): array
    {
        $referral = (float) $order->referral_discount_bdt;
        $wallet   = (float) $order->wallet_discount_bdt;

        $referralApplied = round(min($referral, $subtotal), 2);
        $walletApplied   = round(min($wallet, max(0, $subtotal - $referralApplied)), 2);
        $walletRefunded  = round($wallet - $walletApplied, 2);

        if ($referralApplied < $referral) {
            ReferralUsage::where('order_id', $order->id)
                ->whereIn('status', ['pending', 'credited'])
                ->update(['discount_given' => $referralApplied]);
        }

        if ($walletRefunded > 0 && $order->user_id) {
            $user = User::find($order->user_id);

            if ($user) {
                $this->referralService->refundWallet($user, $walletRefunded, $order);
            }
        }

        return [$referralApplied, $walletApplied, $walletRefunded];
    }

    /**
     * `gift_cards.stock_count` is shadowed by an accessor on reads but is still
     * the column every stock *query* filters on, so recompute it from the codes
     * for each card this edit touched.
     *
     * @param  Collection<int, int>  $giftCardIds
     */
    private function syncStockCounts(Collection $giftCardIds): void
    {
        foreach ($giftCardIds as $giftCardId) {
            GiftCard::whereKey($giftCardId)->update([
                'stock_count' => GiftCardCode::where('gift_card_id', $giftCardId)
                    ->where('status', 'available')
                    ->count(),
            ]);
        }
    }

    /**
     * Collapse the submitted rows into one entry per gift card. Two rows for the
     * same card mean "this many in total", which is what a cart would do.
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @return Collection<int, array{quantity: int, unit_price_bdt: float|null}>
     */
    private function normaliseLines(array $lines): Collection
    {
        $normalised = collect();

        foreach ($lines as $line) {
            $giftCardId = (int) ($line['gift_card_id'] ?? 0);

            if ($giftCardId < 1) {
                continue;
            }

            $quantity  = (int) ($line['quantity'] ?? 0);
            $rawPrice  = $line['unit_price_bdt'] ?? null;
            $unitPrice = ($rawPrice === null || $rawPrice === '') ? null : (float) $rawPrice;

            if ($normalised->has($giftCardId)) {
                $previous = $normalised->get($giftCardId);
                $quantity += $previous['quantity'];
                $unitPrice ??= $previous['unit_price_bdt'];
            }

            $normalised->put($giftCardId, [
                'quantity'       => $quantity,
                'unit_price_bdt' => $unitPrice,
            ]);
        }

        if ($normalised->isEmpty()) {
            throw OrderEditException::emptyOrder();
        }

        return $normalised;
    }

    /**
     * @param  Collection<int, int>  $ids
     * @return Collection<int, GiftCard>
     */
    private function resolveGiftCards(Collection $ids): Collection
    {
        return GiftCard::whereIn('id', $ids->unique()->values())->get()->keyBy('id');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function snapshot(Order $order): array
    {
        return $order->items->map(fn (OrderItem $item) => [
            'gift_card_id'   => $item->gift_card_id,
            'gift_card_name' => $item->giftCard?->name,
            'quantity'       => $item->quantity,
            'unit_price_bdt' => (float) $item->unit_price_bdt,
            'subtotal_bdt'   => (float) $item->subtotal_bdt,
        ])->values()->all();
    }
}
