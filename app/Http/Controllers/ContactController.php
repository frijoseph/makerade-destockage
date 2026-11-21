<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Popup;
use App\OrderStatus;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Storefront forms: contact message, newsletter sign-up and order tracking.
 */
class ContactController extends Controller
{
    public function message(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        ContactMessage::query()->create($data);

        return response()->json(['message' => 'Message envoyé. Nous vous répondons très vite.'], 201);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'popup_id' => ['nullable', 'integer'],
        ]);

        NewsletterSubscriber::query()->firstOrCreate(['email' => mb_strtolower($data['email'])]);

        $popup = isset($data['popup_id']) ? Popup::query()->live()->where('kind', 'newsletter')->find($data['popup_id']) : null;
        $code = $popup?->coupon_code ? Coupon::query()->usable()->where('code', Coupon::normalize($popup->coupon_code))->value('code') : null;
        $popup?->increment('clicks');

        return response()->json(['message' => 'Merci pour votre inscription !', 'code' => $code], 201);
    }

    /**
     * Order status for a customer who knows both the order number and the phone used.
     */
    public function track(Request $request): JsonResponse
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $order = Order::query()
            ->where('number', mb_strtoupper(trim($data['number'])))
            ->where('shipping_phone', Customer::normalizePhone($data['phone']))
            ->first();

        if (! $order) {
            return response()->json(['message' => 'Aucune commande ne correspond à ce numéro et ce téléphone.'], 404);
        }

        $flow = [OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered];
        $reached = array_search($order->status, $flow, true);

        return response()->json([
            'number' => $order->number,
            'placed_at' => $order->placed_at->translatedFormat('j F Y'),
            'total' => Money::format($order->total),
            'status' => $order->status->label(),
            'cancelled' => $order->status === OrderStatus::Cancelled,
            'steps' => array_map(fn (OrderStatus $status, int $index): array => [
                'label' => $status->label(),
                'state' => $reached === false ? 'todo' : ($index < $reached ? 'done' : ($index === $reached ? 'cur' : 'todo')),
            ], $flow, array_keys($flow)),
        ]);
    }
}
