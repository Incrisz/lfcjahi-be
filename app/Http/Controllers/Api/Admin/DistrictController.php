<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\HomeCell;
use App\Models\HomeCellZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DistrictController extends Controller
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

    public function store(Request $request): JsonResponse
    {
        $district = District::create($this->validatePayload($request));

        return response()->json([
            'data' => $this->serializeDistrict($district->load([
                'zones' => fn ($query) => $query->orderBy('sort_order')->orderBy('name'),
                'zones.cells' => fn ($query) => $query->orderBy('sort_order')->orderBy('name'),
            ])),
        ], 201);
    }

    public function update(Request $request, District $district): JsonResponse
    {
        $district->fill($this->validatePayload($request, $district));
        $district->save();

        return response()->json([
            'data' => $this->serializeDistrict($district->load([
                'zones' => fn ($query) => $query->orderBy('sort_order')->orderBy('name'),
                'zones.cells' => fn ($query) => $query->orderBy('sort_order')->orderBy('name'),
            ])),
        ]);
    }

    public function destroy(District $district): JsonResponse
    {
        $district->delete();

        return response()->json([
            'message' => 'District deleted successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?District $district = null): array
    {
        $request->merge([
            'sort_order' => $request->input('sort_order', $request->input('sortOrder')),
            'coverage_areas' => $request->input('coverage_areas', $request->input('coverageAreas')),
            'home_cell_pastors' => $request->input('home_cell_pastors', $request->input('homeCellPastors')),
            'home_cell_minister' => $request->input('home_cell_minister', $request->input('homeCellMinister')),
            'outreach_pastor' => $request->input('outreach_pastor', $request->input('outreachPastor')),
            'outreach_minister' => $request->input('outreach_minister', $request->input('outreachMinister')),
            'outreach_location' => $request->input('outreach_location', $request->input('outreachLocation')),
        ]);

        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('districts', 'name')->ignore($district?->id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'coverage_areas' => ['nullable', 'string'],
            'home_cell_pastors' => ['nullable', 'array'],
            'home_cell_pastors.*' => ['nullable', 'string', 'max:255'],
            'home_cell_minister' => ['nullable', 'string', 'max:255'],
            'outreach_pastor' => ['nullable', 'string', 'max:255'],
            'outreach_minister' => ['nullable', 'string', 'max:255'],
            'outreach_location' => ['nullable', 'string', 'max:255'],
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
