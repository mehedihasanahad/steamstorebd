<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderLookupController extends Controller
{
    public function index()
    {
        if (auth()->check()) {
            $user = auth()->user();
            $orders = Order::where('customer_email', $user->email)
                ->orWhere('user_id', $user->id)
                ->with(['items.giftCard'])
                ->latest()
                ->get();

            return view('storefront.my-orders', compact('orders'));
        }

        return view('storefront.order-lookup');
    }

    public function show(string $orderNumber)
    {
        $user = auth()->user();

        $order = Order::where('order_number', $orderNumber)
            ->where(function ($q) use ($user) {
                $q->where('customer_email', $user->email)
                  ->orWhere('user_id', $user->id);
            })
            ->with(['items.giftCard', 'items.orderItemCodes.giftCardCode'])
            ->firstOrFail();

        return view('storefront.order-detail', compact('order'));
    }

    public function lookup(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'order_number' => ['required', 'string'],
        ]);

        $order = Order::where('customer_email', $request->email)
            ->where('order_number', $request->order_number)
            ->with(['items.giftCard', 'items.orderItemCodes.giftCardCode'])
            ->first();

        if (! $order) {
            return back()->withErrors(['order_number' => 'No order found with that email and order number.'])->withInput();
        }

        return view('storefront.order-detail', compact('order'));
    }
}
