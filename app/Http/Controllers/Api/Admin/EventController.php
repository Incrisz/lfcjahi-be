<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $items = Event::query()
            ->orderBy('event_date')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $items->map(fn (Event $item): array => $this->serializeItem($item))->values(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);
        $item = Event::create($validated);

        return response()->json([
            'data' => $this->serializeItem($item),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $item = Event::query()->findOrFail($id);

        return response()->json([
            'data' => $this->serializeItem($item),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $item = Event::query()->findOrFail($id);
        $validated = $this->validatePayload($request);
        $item->fill($validated);
        $item->save();

        return response()->json([
            'data' => $this->serializeItem($item),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $item = Event::query()->findOrFail($id);
        $item->delete();

        return response()->json([
            'message' => 'Event deleted successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        $request->merge([
            'event_date' => $request->input('event_date', $request->input('eventDate')),
            'media_url' => $request->input('media_url', $request->input('mediaUrl')),
            'registration_enabled' => $request->input('registration_enabled', $request->input('registrationEnabled')),
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'media_url' => ['nullable', 'string', 'max:2048'],
            'registration_enabled' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeItem(Event $item): array
    {
        return [
            'id' => (string) $item->id,
            'name' => $item->name,
            'eventDate' => $item->event_date?->format('Y-m-d') ?? '',
            'description' => $item->description ?? '',
            'mediaUrl' => $item->media_url ?? '',
            'registrationEnabled' => (bool) $item->registration_enabled,
            'createdAt' => $item->created_at?->toISOString(),
            'updatedAt' => $item->updated_at?->toISOString(),
        ];
    }
}
