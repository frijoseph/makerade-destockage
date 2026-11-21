<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryZoneRequest;
use App\Models\DeliveryZone;
use Illuminate\Http\JsonResponse;

/**
 * JSON endpoints behind the add/edit modal of the delivery zones table (Paramètres › Livraison).
 */
class DeliveryZoneController extends Controller
{
    public function store(DeliveryZoneRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['position'] ??= (int) DeliveryZone::query()->max('position') + 1;

        $zone = DeliveryZone::query()->create($data);

        return response()->json(['message' => "« {$zone->name} » ajoutée au tableau."], 201);
    }

    public function update(DeliveryZoneRequest $request, DeliveryZone $zone): JsonResponse
    {
        $data = $request->validated();

        if (($data['position'] ?? null) === null) {
            unset($data['position']);
        }

        $zone->update($data);

        return response()->json(['message' => "« {$zone->name} » mise à jour."]);
    }

    public function destroy(DeliveryZone $zone): JsonResponse
    {
        $zone->delete();

        return response()->json(['message' => "« {$zone->name} » supprimée."]);
    }
}
