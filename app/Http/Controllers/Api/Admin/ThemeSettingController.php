<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ThemeSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ThemeSettingController extends Controller
{
    public function show(): JsonResponse
    {
        $settings = ThemeSetting::query()->firstOrCreate([], [
            'church_name' => 'LFC Jahi',
            'logo_url' => '/assets/images/logo-1.png',
            'tagline' => 'Raising Kingdom Voices',
            'primary_color' => '#0a4d68',
            'accent_color' => '#f2994a',
            'font_family' => 'system-ui, -apple-system, Segoe UI, Roboto, sans-serif',
            'layout_style' => 'standard',
        ]);

        return response()->json([
            'data' => $this->serializeItem($settings),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->merge([
            'church_name' => $request->input('church_name', $request->input('churchName')),
            'logo_url' => $request->input('logo_url', $request->input('logoUrl')),
            'primary_color' => $request->input('primary_color', $request->input('primaryColor')),
            'accent_color' => $request->input('accent_color', $request->input('accentColor')),
            'font_family' => $request->input('font_family', $request->input('fontFamily')),
            'layout_style' => $request->input('layout_style', $request->input('layoutStyle')),
        ]);

        $validated = $request->validate([
            'church_name' => ['required', 'string', 'max:255'],
            'logo_url' => ['nullable', 'string', 'max:2048'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['required', 'string', 'max:20'],
            'accent_color' => ['required', 'string', 'max:20'],
            'font_family' => ['required', 'string', 'max:255'],
            'layout_style' => ['required', Rule::in(['standard', 'wide', 'compact'])],
        ]);

        $settings = ThemeSetting::query()->firstOrCreate([]);
        $settings->fill($validated);
        $settings->save();

        return response()->json([
            'data' => $this->serializeItem($settings),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeItem(ThemeSetting $settings): array
    {
        return [
            'churchName' => $settings->church_name,
            'logoUrl' => $settings->logo_url ?? '',
            'tagline' => $settings->tagline ?? '',
            'primaryColor' => $settings->primary_color,
            'accentColor' => $settings->accent_color,
            'fontFamily' => $settings->font_family,
            'layoutStyle' => $settings->layout_style,
            'updatedAt' => $settings->updated_at?->toISOString(),
        ];
    }
}
