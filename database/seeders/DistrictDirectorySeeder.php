<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Seeder;

class DistrictDirectorySeeder extends Seeder
{
    public function run(): void
    {
        if (District::query()->exists()) {
            return;
        }

        $path = database_path('seeders/data/district-directory.json');
        if (! file_exists($path)) {
            return;
        }

        $payload = json_decode((string) file_get_contents($path), true);
        if (! is_array($payload)) {
            return;
        }

        foreach ($payload as $districtPayload) {
            if (! is_array($districtPayload) || empty($districtPayload['name'])) {
                continue;
            }

            $district = District::query()->create([
                'name' => (string) $districtPayload['name'],
                'sort_order' => (int) ($districtPayload['sortOrder'] ?? 0),
                'coverage_areas' => (string) ($districtPayload['coverageAreas'] ?? ''),
                'home_cell_pastors' => array_values(array_filter($districtPayload['homeCellPastors'] ?? [])),
                'home_cell_minister' => (string) ($districtPayload['homeCellMinister'] ?? ''),
                'outreach_pastor' => (string) ($districtPayload['outreachPastor'] ?? ''),
                'outreach_minister' => (string) ($districtPayload['outreachMinister'] ?? ''),
                'outreach_location' => (string) ($districtPayload['outreachLocation'] ?? ''),
            ]);

            foreach (($districtPayload['zones'] ?? []) as $zonePayload) {
                if (! is_array($zonePayload) || empty($zonePayload['name'])) {
                    continue;
                }

                $zone = $district->zones()->create([
                    'name' => (string) $zonePayload['name'],
                    'sort_order' => (int) ($zonePayload['sortOrder'] ?? 0),
                    'zone_minister' => (string) ($zonePayload['zoneMinister'] ?? ''),
                ]);

                foreach (($zonePayload['cells'] ?? []) as $cellPayload) {
                    if (! is_array($cellPayload) || empty($cellPayload['name'])) {
                        continue;
                    }

                    $zone->cells()->create([
                        'name' => (string) $cellPayload['name'],
                        'sort_order' => (int) ($cellPayload['sortOrder'] ?? 0),
                        'address' => (string) ($cellPayload['address'] ?? ''),
                        'minister' => (string) ($cellPayload['minister'] ?? ''),
                        'phone' => (string) ($cellPayload['phone'] ?? ''),
                    ]);
                }
            }
        }
    }
}
