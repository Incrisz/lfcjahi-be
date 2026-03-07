<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaItem;
use App\Models\Speaker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SpeakerController extends Controller
{
    public function index(): JsonResponse
    {
        $speakers = Speaker::query()
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $speakers->map(fn (Speaker $speaker): array => $this->serializeSpeaker($speaker))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('speakers', 'name')],
        ]);

        $speaker = Speaker::create($validated);

        return response()->json([
            'data' => $this->serializeSpeaker($speaker),
        ], 201);
    }

    public function update(Request $request, Speaker $speaker): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('speakers', 'name')->ignore($speaker->id),
            ],
        ]);

        $oldName = $speaker->name;
        $speaker->fill($validated);
        $speaker->save();

        if ($oldName !== $speaker->name) {
            MediaItem::query()->where('speaker', $oldName)->update(['speaker' => $speaker->name]);
        }

        return response()->json([
            'data' => $this->serializeSpeaker($speaker),
        ]);
    }

    public function destroy(Speaker $speaker): JsonResponse
    {
        $name = $speaker->name;
        $speaker->delete();

        MediaItem::query()->where('speaker', $name)->update(['speaker' => null]);

        return response()->json([
            'message' => 'Speaker deleted successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSpeaker(Speaker $speaker): array
    {
        return [
            'id' => (string) $speaker->id,
            'name' => $speaker->name,
        ];
    }
}
