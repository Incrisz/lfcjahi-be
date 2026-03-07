<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaSubcategory;
use App\Models\MediaItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MediaItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $items = MediaItem::query()
            ->orderByDesc('media_date')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $items->map(fn (MediaItem $item): array => $this->serializeItem($item))->values(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request, null);
        $validated['is_published'] = (bool) ($validated['is_published'] ?? true);

        if ($request->hasFile('thumbnail_file')) {
            $path = $request->file('thumbnail_file')->store('media-thumbnails', 'public');
            $validated['thumbnail_url'] = Storage::url($path);
        }

        if ($request->hasFile('audio_file')) {
            $originalFilename = $this->safeAudioOriginalFilename($request->file('audio_file'));
            $path = $request->file('audio_file')->storeAs('media-audio', $originalFilename, 'public');
            $validated['media_url'] = Storage::url($path);
            $validated['media_source_type'] = 'file';
        }

        $item = MediaItem::create($validated);

        return response()->json([
            'data' => $this->serializeItem($item),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $item = MediaItem::query()->findOrFail($id);

        return response()->json([
            'data' => $this->serializeItem($item),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $item = MediaItem::query()->findOrFail($id);
        $previousThumbnailUrl = $item->thumbnail_url;
        $previousMediaUrl = $item->media_url;
        $previousMediaSourceType = $item->media_source_type;

        $validated = $this->validatePayload($request, $item);

        if ($request->hasFile('thumbnail_file')) {
            $this->deleteManagedPublicFile($previousThumbnailUrl);
            $path = $request->file('thumbnail_file')->store('media-thumbnails', 'public');
            $validated['thumbnail_url'] = Storage::url($path);
        }

        if ($request->hasFile('audio_file')) {
            $this->deleteManagedPublicFile($previousMediaUrl);
            $originalFilename = $this->safeAudioOriginalFilename($request->file('audio_file'));
            $path = $request->file('audio_file')->storeAs('media-audio', $originalFilename, 'public');
            $validated['media_url'] = Storage::url($path);
            $validated['media_source_type'] = 'file';
        } elseif (
            ($validated['media_source_type'] ?? null) !== 'file'
            && $previousMediaSourceType === 'file'
        ) {
            $this->deleteManagedPublicFile($previousMediaUrl);
        }

        $item->fill($validated);
        $item->save();

        return response()->json([
            'data' => $this->serializeItem($item),
        ]);
    }

    public function updatePublishStatus(Request $request, string $id): JsonResponse
    {
        $item = MediaItem::query()->findOrFail($id);
        $validated = $request->validate([
            'is_published' => ['required', 'boolean'],
        ]);

        $item->is_published = (bool) $validated['is_published'];
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
        $item = MediaItem::query()->findOrFail($id);
        $this->deleteManagedPublicFile($item->thumbnail_url);

        if ($item->media_source_type === 'file') {
            $this->deleteManagedPublicFile($item->media_url);
        }

        $item->delete();

        return response()->json([
            'message' => 'Media item deleted successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?MediaItem $existingItem): array
    {
        $audioUpload = $request->file('audio_file');
        if ($audioUpload instanceof UploadedFile && ! $audioUpload->isValid()) {
            $message = $audioUpload->getErrorMessage();

            if (str_contains(strtolower($message), 'exceeds')) {
                $message .= ' Increase PHP upload_max_filesize/post_max_size and restart the backend server.';
            }

            throw ValidationException::withMessages([
                'audio_file' => [$message],
            ]);
        }

        $request->merge([
            'media_date' => $request->input('media_date', $request->input('mediaDate')),
            'thumbnail_url' => $request->input('thumbnail_url', $request->input('thumbnailUrl')),
            'media_url' => $request->input('media_url', $request->input('mediaUrl')),
            'media_source_type' => $request->input('media_source_type', $request->input('mediaSourceType')),
            'is_published' => $request->input('is_published', $request->input('isPublished')),
        ]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'max:255', Rule::exists('media_categories', 'name')],
            'subcategory' => ['nullable', 'string', 'max:255'],
            'speaker' => ['nullable', 'string', 'max:255', Rule::exists('speakers', 'name')],
            'media_date' => ['nullable', 'date'],
            'thumbnail_url' => ['nullable', 'string', 'max:2048'],
            'thumbnail_file' => ['nullable', 'image', 'max:5120'],
            'media_url' => ['nullable', 'string', 'max:2048'],
            'media_source_type' => ['nullable', Rule::in(['link', 'file'])],
            'is_published' => ['nullable', 'boolean'],
            'audio_file' => [
                'nullable',
                'file',
                'mimes:mp3,wav,m4a,aac,ogg',
                'max:51200',
            ],
        ]);

        $category = $validated['category'];
        $subcategory = trim((string) ($validated['subcategory'] ?? ''));
        $validated['speaker'] = trim((string) ($validated['speaker'] ?? '')) ?: null;
        $sourceType = $validated['media_source_type'] ?? null;

        if ($subcategory !== '') {
            $subcategoryExists = MediaSubcategory::query()
                ->where('name', $subcategory)
                ->whereHas('category', fn ($query) => $query->where('name', $category))
                ->exists();

            if (! $subcategoryExists) {
                throw ValidationException::withMessages([
                    'subcategory' => ['Selected subcategory does not belong to the selected category.'],
                ]);
            }
        }

        if ($this->isAudioCategory($category)) {
            if (! $sourceType) {
                $sourceType = $request->hasFile('audio_file') ? 'file' : 'link';
            }

            $existingMediaUrl = $existingItem?->media_url;
            $hasMediaUrl = ! empty($validated['media_url']) || ! empty($existingMediaUrl);

            if ($sourceType === 'file' && ! $request->hasFile('audio_file') && ! $hasMediaUrl) {
                throw ValidationException::withMessages([
                    'audio_file' => ['Audio file is required when source type is file.'],
                ]);
            }

            if ($sourceType === 'link' && ! $hasMediaUrl) {
                throw ValidationException::withMessages([
                    'media_url' => ['Audio link is required when source type is link.'],
                ]);
            }

            $validated['media_source_type'] = $sourceType;
        } else {
            $validated['media_source_type'] = null;
        }

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeItem(MediaItem $item): array
    {
        return [
            'id' => (string) $item->id,
            'title' => $item->title,
            'description' => $item->description ?? '',
            'category' => $item->category,
            'subcategory' => $item->subcategory ?? '',
            'speaker' => $item->speaker ?? '',
            'mediaDate' => $item->media_date?->format('Y-m-d') ?? '',
            'thumbnailUrl' => $this->absoluteUrl($item->thumbnail_url),
            'mediaUrl' => $this->absoluteUrl($item->media_url),
            'mediaSourceType' => $item->media_source_type ?? '',
            'isPublished' => (bool) $item->is_published,
            'createdAt' => $item->created_at?->toISOString(),
            'updatedAt' => $item->updated_at?->toISOString(),
        ];
    }

    private function absoluteUrl(?string $path): string
    {
        if (! $path) {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $path) === 1) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return URL::to($path);
        }

        return $path;
    }

    private function deleteManagedPublicFile(?string $fileUrl): void
    {
        $publicPath = $this->extractPublicStoragePath($fileUrl);

        if (! $publicPath) {
            return;
        }

        if (Storage::disk('public')->exists($publicPath)) {
            Storage::disk('public')->delete($publicPath);
        }
    }

    private function extractPublicStoragePath(?string $fileUrl): ?string
    {
        if (! $fileUrl) {
            return null;
        }

        $prefix = '/storage/';

        if (str_starts_with($fileUrl, $prefix)) {
            return substr($fileUrl, strlen($prefix));
        }

        $absolutePrefix = rtrim(config('app.url'), '/').$prefix;

        if ($absolutePrefix !== '' && str_starts_with($fileUrl, $absolutePrefix)) {
            return substr($fileUrl, strlen($absolutePrefix));
        }

        return null;
    }

    private function isAudioCategory(string $category): bool
    {
        return strtolower(trim($category)) === 'audio';
    }

    private function safeAudioOriginalFilename(?UploadedFile $file): string
    {
        if (! $file) {
            return '';
        }

        return basename($file->getClientOriginalName());
    }
}
