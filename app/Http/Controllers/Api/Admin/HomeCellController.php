<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeCell;
use App\Models\HomeCellZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeCellController extends Controller
{
    public function store(Request $request, HomeCellZone $zone): JsonResponse
    {
        $cell = $zone->cells()->create($this->validatePayload($request));

        return response()->json([
            'data' => $this->serializeCell($cell),
        ], 201);
    }

    public function update(Request $request, HomeCell $cell): JsonResponse
    {
        $cell->fill($this->validatePayload($request));
        $cell->save();

        return response()->json([
            'data' => $this->serializeCell($cell),
        ]);
    }

    public function destroy(HomeCell $cell): JsonResponse
    {
        $cell->delete();

        return response()->json([
            'message' => 'Home cell deleted successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        $request->merge([
            'sort_order' => $request->input('sort_order', $request->input('sortOrder')),
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'minister' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
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
