<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingsRequest;
use App\Models\DeliveryZone;
use App\Models\Setting;
use App\Support\SettingsSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(string $group = 'shop'): View
    {
        $groups = SettingsSchema::groups();
        abort_unless(isset($groups[$group]), 404);

        return view('admin.settings.edit', [
            'groups' => $groups,
            'group' => $group,
            'fields' => $groups[$group]['fields'],
            'values' => collect($groups[$group]['fields'])->mapWithKeys(fn (array $field, string $key): array => [$key => Setting::get($key)]),
            'zones' => $group === 'delivery' ? DeliveryZone::query()->orderBy('position')->orderBy('name')->get() : collect(),
        ]);
    }

    public function update(SettingsRequest $request, string $group): RedirectResponse
    {
        $values = [];

        foreach ($request->groupFields() as $key => $field) {
            $value = $request->validated("settings.{$key}");

            $values[$key] = match ($field['type']) {
                'image' => $this->storeImage($request->file("files.{$key}")) ?? $value,
                'list' => $this->cleanList($request, $key, $field, (array) $value),
                default => $value,
            };
        }

        Setting::putMany($values);

        return redirect()->route('admin.settings.edit', $group)->with('status', 'Paramètres « '.SettingsSchema::groups()[$group]['label'].' » enregistrés.');
    }

    /**
     * Stores uploaded images and drops completely empty rows.
     *
     * @param  array<string, mixed>  $field
     * @param  array<int, array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function cleanList(SettingsRequest $request, string $key, array $field, array $rows): array
    {
        $clean = [];

        foreach ($rows as $index => $row) {
            $item = [];

            foreach ($field['fields'] as $subKey => $subField) {
                $item[$subKey] = $subField['type'] === 'image'
                    ? ($this->storeImage($request->file("files.{$key}.{$index}.{$subKey}")) ?? ($row[$subKey] ?? null))
                    : ($row[$subKey] ?? null);
            }

            if (collect($item)->filter(fn ($value): bool => $value !== null && $value !== '')->isNotEmpty()) {
                $clean[] = $item;
            }
        }

        return $clean;
    }

    private function storeImage(?UploadedFile $file): ?string
    {
        return $file?->store('settings', 'public') ?: null;
    }
}
