<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogPostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $items = BlogPost::query()
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $items->map(fn (BlogPost $item): array => $this->serializeItem($item))->values(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        if (empty($validated['slug'])) {
            $validated['slug'] = $this->generateUniqueSlug($validated['title']);
        }

        $item = BlogPost::create($validated);

        return response()->json([
            'data' => $this->serializeItem($item),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $item = BlogPost::query()->findOrFail($id);

        return response()->json([
            'data' => $this->serializeItem($item),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $item = BlogPost::query()->findOrFail($id);
        $validated = $this->validatePayload($request, $item->id);

        if (empty($validated['slug'])) {
            $validated['slug'] = $this->generateUniqueSlug($validated['title'], $item->id);
        }

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
        $item = BlogPost::query()->findOrFail($id);
        $item->delete();

        return response()->json([
            'message' => 'Blog post deleted successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        $request->merge([
            'publish_date' => $request->input('publish_date', $request->input('publishDate')),
        ]);

        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('blog_posts', 'slug')->ignore($ignoreId),
            ],
            'excerpt' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'publish_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['draft', 'published'])],
        ]);
    }

    private function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'post';
        $slug = $base;
        $count = 1;

        while (BlogPost::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.$count;
            $count++;
        }

        return $slug;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeItem(BlogPost $item): array
    {
        return [
            'id' => (string) $item->id,
            'title' => $item->title,
            'slug' => $item->slug,
            'excerpt' => $item->excerpt ?? '',
            'content' => $item->content ?? '',
            'publishDate' => $item->publish_date?->format('Y-m-d') ?? '',
            'status' => $item->status,
            'createdAt' => $item->created_at?->toISOString(),
            'updatedAt' => $item->updated_at?->toISOString(),
        ];
    }
}
