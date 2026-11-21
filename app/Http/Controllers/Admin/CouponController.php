<?php

namespace App\Http\Controllers\Admin;

use App\CouponType;
use App\Http\Controllers\Controller;
use App\Http\Requests\CouponRequest;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(): View
    {
        $coupons = Coupon::query()->latest()->get();

        return view('admin.coupons.index', [
            'coupons' => $coupons,
            'types' => collect(CouponType::cases())->mapWithKeys(fn (CouponType $type): array => [$type->value => $type->label()])->all(),
            'active' => $coupons->filter(fn (Coupon $coupon): bool => $coupon->is_active && (! $coupon->ends_at || $coupon->ends_at->isFuture()))->count(),
            'used' => (int) $coupons->sum('used_count'),
        ]);
    }

    public function store(CouponRequest $request): JsonResponse
    {
        $coupon = Coupon::query()->create($request->validated());

        return response()->json(['message' => "Code « {$coupon->code} » créé."], 201);
    }

    public function update(CouponRequest $request, Coupon $coupon): JsonResponse
    {
        $coupon->update($request->validated());

        return response()->json(['message' => "Code « {$coupon->code} » mis à jour."]);
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        $coupon->delete();

        return response()->json(['message' => "Code « {$coupon->code} » supprimé."]);
    }
}
