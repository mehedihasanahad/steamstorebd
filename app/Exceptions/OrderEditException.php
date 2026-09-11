<?php

namespace App\Exceptions;

use App\Models\Order;
use RuntimeException;

/**
 * Every way an order edit can legitimately be refused. These are admin-facing
 * messages — they are shown verbatim in the panel, so they say what to do next.
 */
class OrderEditException extends RuntimeException
{
    public static function statusNotEditable(Order $order): self
    {
        return new self("Order #{$order->order_number} is {$order->status} and can no longer be edited.");
    }

    public static function emptyOrder(): self
    {
        return new self('An order must keep at least one item. Cancel or refund the order instead of emptying it.');
    }

    public static function reasonRequired(): self
    {
        return new self('A reason is required so the edit can be justified later.');
    }

    public static function invalidQuantity(string $giftCardName): self
    {
        return new self("Quantity for \"{$giftCardName}\" must be at least 1. Remove the line instead of setting it to zero.");
    }

    public static function unknownGiftCard(int $giftCardId): self
    {
        return new self("Gift card #{$giftCardId} no longer exists.");
    }

    public static function insufficientStock(string $giftCardName, int $needed, int $available): self
    {
        return new self("Not enough stock for \"{$giftCardName}\": {$needed} more code(s) needed, {$available} available. Add codes to inventory first.");
    }

    public static function invalidDisposition(string $disposition): self
    {
        return new self("Unknown disposition \"{$disposition}\" for already-delivered codes.");
    }
}
