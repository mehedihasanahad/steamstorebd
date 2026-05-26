<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, string $orderNumber)
    {
        $order = auth()->user()->orders()
            ->where('order_number', $orderNumber)
            ->whereIn('status', ['paid', 'completed'])
            ->firstOrFail();

        if ($order->reviews()->where('user_id', auth()->id())->exists()) {
            return back()->with('error', 'You have already submitted a review for this order.');
        }

        $data = $request->validate([
            'rating'     => ['required', 'integer', 'min:1', 'max:5'],
            'comment'    => ['required', 'string', 'min:10', 'max:1000'],
            'screenshot' => ['nullable', 'image', 'max:5120'],
        ]);

        $screenshotPath = null;
        if ($request->hasFile('screenshot')) {
            $screenshotPath = $request->file('screenshot')->store('reviews', 'public');
        }

        Review::create([
            'user_id'              => auth()->id(),
            'order_id'             => $order->id,
            'reviewer_name'        => auth()->user()->name,
            'rating'               => $data['rating'],
            'comment'              => $data['comment'],
            'platform'             => 'website',
            'screenshot_path'      => $screenshotPath,
            'status'               => 'pending',
            'is_verified_purchase' => true,
            'source'               => 'customer',
        ]);

        return back()->with('success', 'Thank you for your review! It will appear after approval.');
    }
}
