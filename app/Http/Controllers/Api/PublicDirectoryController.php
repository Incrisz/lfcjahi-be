<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\HomeCell;
use App\Models\HomeCellZone;
use Illuminate\Http\JsonResponse;

class PublicDirectoryController extends Controller
{
    public function index(): JsonResponse
    {
        $districts = District::query()
            ->with([
                'zones' => fn ($query) => $query->orderBy('sort_order')->orderBy('name'),
                'zones.cells' => fn ($query) => $query->orderBy('sort_order')->orderBy('name'),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $districts->map(fn (District $district): array => $this->serializeDistrict($district))->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDistrict(District $district): array
    {
        return [
            'id' => (string) $district->id,
            'name' => $district->name,
            'sortOrder' => (int) ($district->sort_order ?? 0),
            'coverageAreas' => $district->coverage_areas ?? '',
            'homeCellPastors' => array_values(array_filter($district->home_cell_pastors ?? [])),
            'homeCellMinister' => $district->home_cell_minister ?? '',
            'outreachPastor' => $district->outreach_pastor ?? '',
            'outreachMinister' => $district->outreach_minister ?? '',
            'outreachLocation' => $district->outreach_location ?? '',
            'zones' => $district->zones->map(fn (HomeCellZone $zone): array => $this->serializeZone($zone))->values(),
        ];
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
            'cells' => $zone->cells->map(fn (HomeCell $cell): array => $this->serializeCell($cell))->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCell(HomeCell $cell): array
    {
        return [
            'id' => (string) $cell->id,
            'name' => $cell->name,
            'sortOrder' => (int) ($cell->sort_order ?? 0),
            'address' => $cell->address ?? '',
            'minister' => $cell->minister ?? '',
            'phone' => $cell->phone ?? '',
        ];
    }
}
