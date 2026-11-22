<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\OrderChannel;
use App\PaymentMethod;
use App\Support\CartResolver;
use App\Support\OrderPlacement;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    /**
     * Turns the browser cart ({slug: quantity}) into an order. Prices, promotions and shipping are recomputed server-side.
     */
    public function store(CheckoutRequest $request): JsonResponse
    {
        $data = $request->validated();
        $lines = CartResolver::lines($data['items']);

        if ($lines === null) {
            return response()->json(['message' => 'Un des produits du panier n’est plus disponible. Actualisez la page.'], 422);
        }

        try {
            $order = OrderPlacement::place(
                $request->only(['name', 'phone', 'email', 'city', 'address']),
                $lines,
                OrderChannel::Web,
                PaymentMethod::from($data['payment_method']),
                $data['notes'] ?? null,
                couponCode: $data['coupon_code'] ?? null,
                account: $request->user(),
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'number' => $order->number,
            'total' => (float) $order->total,
            'redirect' => URL::temporarySignedRoute('checkout.show', now()->addDays(7), ['order' => $order->number]),
        ], 201);
    }

    /**
     * Order confirmation page; the signed URL keeps other customers' orders private.
     */
    public function show(Order $order): View
    {
        return view('pages.order-confirmation', [
            'order' => $order->load('items'),
            'receiptUrl' => URL::temporarySignedRoute('checkout.receipt', now()->addHour(), ['order' => $order->number]),
        ]);
    }

    /**
     * Printable receipt for the customer (same signed-link protection as the confirmation page).
     */
    public function receipt(Order $order): View
    {
        return view('documents.order', [
            'order' => $order->load(['customer', 'items']),
            'mode' => 'receipt',
            'auto' => request()->boolean('auto'),
            'back' => null,
            'switch' => [],
        ]);
    }
}
