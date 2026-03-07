<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaItem;
use App\Models\MediaCategory;
use App\Models\MediaSubcategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = MediaCategory::query()
            ->with('subcategories')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $categories->map(fn (MediaCategory $category): array => $this->serializeCategory($category))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('media_categories', 'name')],
        ]);

        $category = MediaCategory::create($validated)->load('subcategories');

        return response()->json([
            'data' => $this->serializeCategory($category),
        ], 201);
    }

    public function update(Request $request, MediaCategory $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('media_categories', 'name')->ignore($category->id),
            ],
        ]);

        $oldName = $category->name;
        $category->fill($validated);
        $category->save();

        if ($oldName !== $category->name) {
            \App\Models\MediaItem::query()->where('category', $oldName)->update(['category' => $category->name]);
        }

        return response()->json([
            'data' => $this->serializeCategory($category->load('subcategories')),
        ]);
    }

    public function destroy(MediaCategory $category): JsonResponse
    {
        $name = $category->name;

        $attachedContentCount = MediaItem::query()
            ->where('category', $name)
            ->count();

        if ($attachedContentCount > 0) {
            return response()->json([
                'message' => "Cannot delete category '{$name}' because it still has {$attachedContentCount} media item(s). Remove the content first.",
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }

    public function storeSubcategory(Request $request, MediaCategory $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('media_subcategories', 'name')->where(
                    fn ($query) => $query->where('media_category_id', $category->id),
                ),
            ],
        ]);

        $subcategory = MediaSubcategory::create([
            'media_category_id' => $category->id,
            'name' => $validated['name'],
        ]);

        return response()->json([
            'data' => $this->serializeSubcategory($subcategory),
        ], 201);
    }

    public function updateSubcategory(Request $request, MediaSubcategory $subcategory): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('media_subcategories', 'name')->where(
                    fn ($query) => $query->where('media_category_id', $subcategory->media_category_id),
                )->ignore($subcategory->id),
            ],
        ]);

        $oldName = $subcategory->name;
        $subcategory->fill($validated);
        $subcategory->save();

        if ($oldName !== $subcategory->name) {
            $categoryName = $subcategory->category?->name;

            if ($categoryName) {
                \App\Models\MediaItem::query()
                    ->where('category', $categoryName)
                    ->where('subcategory', $oldName)
                    ->update(['subcategory' => $subcategory->name]);
            }
        }

        return response()->json([
            'data' => $this->serializeSubcategory($subcategory),
        ]);
    }

    public function destroySubcategory(MediaSubcategory $subcategory): JsonResponse
    {
        $oldName = $subcategory->name;
        $categoryName = $subcategory->category?->name;

        if ($categoryName) {
            $attachedContentCount = MediaItem::query()
                ->where('category', $categoryName)
                ->where('subcategory', $oldName)
                ->count();

            if ($attachedContentCount > 0) {
                return response()->json([
                    'message' => "Cannot delete subcategory '{$oldName}' because it still has {$attachedContentCount} media item(s). Remove the content first.",
                ], 422);
            }
        }

        $subcategory->delete();

        return response()->json([
            'message' => 'Subcategory deleted successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCategory(MediaCategory $category): array
    {
        return [
            'id' => (string) $category->id,
            'name' => $category->name,
            'subcategories' => $category->subcategories
                ->map(fn (MediaSubcategory $subcategory): array => $this->serializeSubcategory($subcategory))
                ->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSubcategory(MediaSubcategory $subcategory): array
    {
        return [
            'id' => (string) $subcategory->id,
            'name' => $subcategory->name,
        ];
    }
}
