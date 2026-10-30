<?php

/**
 * `orders.order_number` is unique, so a repeat is not a duplicate row but an
 * exception thrown at whichever customer happened to draw it mid-checkout.
 * Six digits that reset each January is a million slots; by the birthday
 * paradox a shop doing a thousand orders a year had about a two-in-five
 * chance of hitting one.
 */

use App\Models\Order;
use Illuminate\Support\Facades\DB;

it('keeps the shape customers already have on their invoices', function () {
    // Changing this would strand every number already quoted in an e-mail.
    expect(generate_order_number())->toMatch('/^BD' . date('Y') . '-\d{6}$/');
});

it('never hands back a number the orders table already holds', function () {
    $taken = collect(range(1, 40))->map(fn () => generate_order_number());

    // Distinct before anything is written, and still distinct once every one
    // of them is a real row.
    expect($taken->unique())->toHaveCount(40);

    // No OrderFactory in this suite, and a real order would need stock and a
    // cart; the column under test is the only one that matters here.
    DB::table('orders')->insert($taken->map(fn (string $number) => [
        'order_number'   => $number,
        'customer_name'  => 'Buyer',
        'customer_email' => 'buyer@example.com',
        'customer_phone' => '01700000000',
        'subtotal_bdt'   => 100,
        'total_bdt'      => 100,
        'created_at'     => now(),
        'updated_at'     => now(),
    ])->all());

    expect(generate_order_number())->not->toBeIn($taken->all())
        ->and(Order::whereIn('order_number', $taken->all())->count())->toBe(40);
});
