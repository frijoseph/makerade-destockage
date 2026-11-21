<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\NewsletterSubscriber;
use App\Models\Popup;
use App\Models\Product;
use App\Models\WheelSpin;
use App\Support\Money;
use App\Support\OrderPlacement;
use App\Support\Promotions;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Storefront promotion endpoints: cart price preview, pop-up statistics and the spin-to-win wheel.
 */
class PromoController extends Controller
{
    /**
     * Preview of the cart total with a promo code (or the best automatic promotion). Nothing is stored.
     */
    public function coupon(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:30'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*' => ['integer', 'min:1', 'max:99'],
        ]);

        $subtotal = (float) Product::query()->active()->whereIn('slug', array_keys($data['items']))->get()
            ->sum(fn (Product $product): float => (float) $product->price * (int) $data['items'][$product->slug]);

        try {
            $promotion = Promotions::resolve($subtotal, $data['code'] ?? null, $data['phone'] ?? null);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $shipping = $promotion['free_shipping'] ? 0.0 : OrderPlacement::shippingFeeFor($subtotal, $data['city'] ?? null);
        $coupon = $promotion['coupon'];

        return response()->json([
            'subtotal' => $subtotal,
            'discount' => $promotion['discount'],
            'shipping' => $shipping,
            'total' => $subtotal - $promotion['discount'] + $shipping,
            'coupon' => $coupon ? [
                'code' => $coupon->code,
                'label' => $coupon->label ?: $coupon->benefit(),
                'benefit' => $coupon->benefit(),
                'auto' => $promotion['auto'],
            ] : null,
            'formatted' => ['discount' => Money::format($promotion['discount'])],
        ]);
    }

    /**
     * Counts a pop-up impression or click.
     */
    public function event(Request $request, Popup $popup): JsonResponse
    {
        $type = $request->validate(['type' => ['required', Rule::in(['impression', 'click'])]])['type'];

        $popup->increment($type === 'click' ? 'clicks' : 'impressions');

        return response()->json(['ok' => true]);
    }

    /**
     * One spin per e-mail and wheel. The prize is drawn here, never in the browser.
     */
    public function spin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'popup_id' => ['required', 'integer'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $popup = Popup::query()->live()->where('kind', 'wheel')->find($data['popup_id']);
        $segments = collect((array) $popup?->segments)->values();

        if (! $popup || $segments->isEmpty()) {
            return response()->json(['message' => 'Cette animation n’est plus disponible.'], 404);
        }

        $email = mb_strtolower($data['email']);

        if (WheelSpin::query()->where('popup_id', $popup->id)->where('email', $email)->exists()) {
            return response()->json(['message' => 'Vous avez déjà tenté votre chance avec cet e-mail. Un seul tour par personne !'], 422);
        }

        $index = $this->drawSegment($segments->all());
        $segment = $segments[$index];
        $code = Coupon::normalize($segment['coupon_code'] ?? '');

        if ($code !== '' && ! Coupon::query()->usable()->where('code', $code)->exists()) {
            $code = '';
        }

        WheelSpin::query()->create([
            'popup_id' => $popup->id,
            'email' => $email,
            'prize' => (string) ($segment['label'] ?? ''),
            'coupon_code' => $code ?: null,
            'ip' => $request->ip(),
        ]);
        NewsletterSubscriber::query()->firstOrCreate(['email' => $email]);
        $popup->increment('clicks');

        return response()->json([
            'index' => $index,
            'label' => (string) ($segment['label'] ?? ''),
            'code' => $code ?: null,
            'won' => $code !== '',
        ]);
    }

    /**
     * Weighted random draw; segments with a zero weight never win unless every weight is zero.
     *
     * @param  list<array<string, mixed>>  $segments
     */
    private function drawSegment(array $segments): int
    {
        $weights = array_map(fn (array $segment): int => max(0, (int) ($segment['weight'] ?? 0)), $segments);
        $total = array_sum($weights);

        if ($total === 0) {
            return random_int(0, count($segments) - 1);
        }

        $pick = random_int(1, $total);
        foreach ($weights as $index => $weight) {
            $pick -= $weight;
            if ($pick <= 0) {
                return $index;
            }
        }

        return count($segments) - 1;
    }
}
