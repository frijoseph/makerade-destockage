<?php

namespace App\Http\Requests;

use App\Support\SettingsSchema;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation built from SettingsSchema for the group being saved.
 */
class SettingsRequest extends FormRequest
{
    /**
     * Access is already restricted to administrators by the route middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function groupFields(): array
    {
        $group = SettingsSchema::groups()[$this->route('group')] ?? null;
        abort_if($group === null, 404);

        return $group['fields'];
    }

    /**
     * Unchecked checkboxes and emptied lists are not sent by the browser.
     */
    protected function prepareForValidation(): void
    {
        $settings = (array) $this->input('settings', []);

        foreach ($this->groupFields() as $key => $field) {
            $settings[$key] = match ($field['type']) {
                'boolean' => (bool) ($settings[$key] ?? false),
                'list', 'checkboxes' => array_values((array) ($settings[$key] ?? [])),
                default => $settings[$key] ?? null,
            };
        }

        $this->merge(['settings' => $settings]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = ['settings' => ['array']];

        foreach ($this->groupFields() as $key => $field) {
            $rules["settings.{$key}"] = $field['rules'] ?? $this->rulesFor($field);

            if ($field['type'] === 'checkboxes') {
                $rules["settings.{$key}.*"] = [Rule::in(array_keys($field['options']))];
            }

            if ($field['type'] === 'image') {
                $rules["files.{$key}"] = ['nullable', 'image', 'max:4096'];
            }

            if ($field['type'] === 'list') {
                foreach ($field['fields'] as $subKey => $subField) {
                    $rules["settings.{$key}.*.{$subKey}"] = $this->rulesFor($subField);

                    if ($subField['type'] === 'image') {
                        $rules["files.{$key}.*.{$subKey}"] = ['nullable', 'image', 'max:4096'];
                    }
                }
            }
        }

        return $rules;
    }

    /**
     * Readable names in error messages ("Diaporama › Titre" rather than settings.hero_slides.0.title).
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [];

        foreach ($this->groupFields() as $key => $field) {
            $attributes["settings.{$key}"] = $field['label'];
            $attributes["files.{$key}"] = $field['label'];

            foreach ($field['fields'] ?? [] as $subKey => $subField) {
                $attributes["settings.{$key}.*.{$subKey}"] = $field['label'].' › '.$subField['label'];
                $attributes["files.{$key}.*.{$subKey}"] = $field['label'].' › '.$subField['label'];
            }
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return list<mixed>
     */
    private function rulesFor(array $field): array
    {
        return match ($field['type']) {
            'textarea', 'markdown' => ['nullable', 'string', 'max:5000'],
            'email' => ['nullable', 'email', 'max:255'],
            'url' => ['nullable', 'url', 'max:255'],
            'tel' => ['nullable', 'string', 'max:30'],
            'secret' => ['nullable', 'string', 'max:400'],
            'number' => ['nullable', 'numeric'],
            'select' => ['nullable', Rule::in(array_map('strval', array_keys($field['options'])))],
            'boolean' => ['boolean'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'datetime' => ['nullable', 'date'],
            'image' => ['nullable', 'string', 'max:255'],
            'checkboxes', 'list' => ['array'],
            default => ['nullable', 'string', 'max:255'],
        };
    }
}
