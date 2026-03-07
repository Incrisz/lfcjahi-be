<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\HomeCellZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DistrictZoneController extends Controller
{
    public function store(Request $request, District $district): JsonResponse
    {
        $zone = $district->zones()->create($this->validatePayload($request));

        return response()->json([
            'data' => $this->serializeZone($zone),
        ], 201);
    }

    public function update(Request $request, HomeCellZone $zone): JsonResponse
    {
        $zone->fill($this->validatePayload($request));
        $zone->save();

        return response()->json([
            'data' => $this->serializeZone($zone),
        ]);
    }

    public function destroy(HomeCellZone $zone): JsonResponse
    {
        $zone->delete();

        return response()->json([
            'message' => 'Zone deleted successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        $request->merge([
            'sort_order' => $request->input('sort_order', $request->input('sortOrder')),
            'zone_minister' => $request->input('zone_minister', $request->input('zoneMinister')),
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'zone_minister' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeZone(HomeCellZone $zone): array
    {
        return [
            'id' => (string) $zone->id,
            'name' => $zone->name,
            'sortOrder' => (int) ($zone->sort_order ?? 0),
            'zoneMinister' => $zone->zone_minister ?? '',
        ];
    }
}
