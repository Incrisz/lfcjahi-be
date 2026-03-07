<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MediaItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicMediaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = MediaItem::query()
            ->where('is_published', true)
            ->when(
                $request->filled('category'),
                fn ($query) => $query->where('category', (string) $request->query('category')),
            )
            ->orderByDesc('media_date')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $items->map(fn (MediaItem $item): array => $this->serializeItem($item))->values(),
        ]);
    }

    public function download(string $id): BinaryFileResponse|RedirectResponse|Response
    {
        $item = MediaItem::query()
            ->where('is_published', true)
            ->findOrFail($id);

        $mediaUrl = $item->media_url;
        if (! $mediaUrl) {
            abort(404);
        }

        $publicStoragePath = $this->extractPublicStoragePath($mediaUrl);
        if ($publicStoragePath && Storage::disk('public')->exists($publicStoragePath)) {
            return Storage::disk('public')->download($publicStoragePath, $this->downloadFilename($item, $publicStoragePath));
        }

        if (preg_match('/^https?:\/\//i', $mediaUrl) === 1) {
            return redirect()->away($mediaUrl);
        }

        abort(404);
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
            'downloadUrl' => URL::to('/api/media/'.$item->id.'/download'),
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

    private function downloadFilename(MediaItem $item, string $publicStoragePath): string
    {
        $extension = pathinfo($publicStoragePath, PATHINFO_EXTENSION);
        $fallbackName = basename($publicStoragePath);
        $slug = Str::slug($item->title ?: 'message');

        if (! $slug) {
            return $fallbackName;
        }

        return $extension ? $slug.'.'.$extension : $fallbackName;
    }
}
