<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaItem;
use App\Models\Speaker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SpeakerController extends Controller
{
    private const MAX_SPEAKER_IMAGE_FILE_SIZE_KB = 5120;

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
        $validated = $this->validatePayload($request);

        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('speaker-images', 'public');
            $validated['image_url'] = $this->publicStoragePath($path);
        }

        $speaker = Speaker::create($validated);

        return response()->json([
            'data' => $this->serializeSpeaker($speaker),
        ], 201);
    }

    public function update(Request $request, Speaker $speaker): JsonResponse
    {
        $validated = $this->validatePayload($request, $speaker);

        $oldName = $speaker->name;
        $previousImageUrl = $speaker->image_url;

        if ($request->boolean('remove_image')) {
            $validated['image_url'] = null;
        }

        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('speaker-images', 'public');
            $validated['image_url'] = $this->publicStoragePath($path);
        }

        if (($validated['image_url'] ?? $previousImageUrl) !== $previousImageUrl) {
            $this->deleteManagedPublicFile($previousImageUrl);
        }

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

        $attachedContentCount = MediaItem::query()
            ->where('speaker', $name)
            ->count();

        if ($attachedContentCount > 0) {
            return response()->json([
                'message' => "Cannot delete speaker '{$name}' because it still has {$attachedContentCount} media item(s). Remove the content first.",
            ], 422);
        }

        $this->deleteManagedPublicFile($speaker->image_url);
        $speaker->delete();

        return response()->json([
            'message' => 'Speaker deleted successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?Speaker $speaker = null): array
    {
        $request->merge([
            'image_url' => $request->input('image_url', $request->input('imageUrl')),
            'remove_image' => $request->input('remove_image', $request->input('removeImage')),
        ]);

        $nameRules = ['required', 'string', 'max:255'];
        $uniqueRule = Rule::unique('speakers', 'name');
        if ($speaker) {
            $uniqueRule = $uniqueRule->ignore($speaker->id);
        }
        $nameRules[] = $uniqueRule;

        $validated = $request->validate([
            'name' => $nameRules,
            'image_url' => ['nullable', 'string', 'max:2048'],
            'image_file' => ['nullable', 'image', 'max:' . self::MAX_SPEAKER_IMAGE_FILE_SIZE_KB],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        $validated['name'] = trim((string) $validated['name']);
        $validated['image_url'] = trim((string) ($validated['image_url'] ?? '')) ?: null;
        unset($validated['remove_image']);

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSpeaker(Speaker $speaker): array
    {
        return [
            'id' => (string) $speaker->id,
            'name' => $speaker->name,
            'imageUrl' => $this->absoluteUrl($speaker->image_url),
        ];
    }

    private function absoluteUrl(?string $path): string
    {
        if (! $path) {
            return '';
        }

        if (str_starts_with($path, '/')) {
            return $this->publicUrl($path);
        }

        if (preg_match('/^https?:\/\//i', $path) === 1) {
            $parsedPath = (string) parse_url($path, PHP_URL_PATH);
            $query = (string) parse_url($path, PHP_URL_QUERY);

            if (str_starts_with($parsedPath, '/storage/')) {
                return $this->publicUrl($parsedPath.($query !== '' ? '?'.$query : ''));
            }

            return $path;
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

        $parsedPath = (string) parse_url($fileUrl, PHP_URL_PATH);
        if (str_starts_with($parsedPath, $prefix)) {
            return substr($parsedPath, strlen($prefix));
        }

        return null;
    }

    private function publicStoragePath(string $path): string
    {
        return '/storage/'.ltrim($path, '/');
    }

    private function publicUrl(string $path): string
    {
        $request = request();
        $normalizedPath = '/'.ltrim($path, '/');

        if (! $request) {
            return rtrim(config('app.url'), '/').$normalizedPath;
        }

        $forwardedProto = trim(explode(',', (string) $request->headers->get('x-forwarded-proto', ''))[0] ?? '');
        $forwardedHost = trim(explode(',', (string) $request->headers->get('x-forwarded-host', ''))[0] ?? '');
        $forwardedPort = trim(explode(',', (string) $request->headers->get('x-forwarded-port', ''))[0] ?? '');

        $scheme = $forwardedProto !== '' ? $forwardedProto : $request->getScheme();
        $host = $forwardedHost !== '' ? $forwardedHost : $request->getHost();
        $port = $forwardedPort !== '' ? (int) $forwardedPort : $request->getPort();
        $includePort = $port > 0
            && ! in_array([$scheme, $port], [['http', 80], ['https', 443]], true);

        return $scheme.'://'.$host.($includePort ? ':'.$port : '').$normalizedPath;
    }
}
