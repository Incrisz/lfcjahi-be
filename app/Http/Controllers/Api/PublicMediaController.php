<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MediaItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

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
}
