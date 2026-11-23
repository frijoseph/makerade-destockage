<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingsRequest;
use App\Mail\ShopMail;
use App\Models\DeliveryZone;
use App\Models\Setting;
use App\Support\SettingsSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

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
            'values' => collect($groups[$group]['fields'])->mapWithKeys(fn (array $field, string $key): array => [$key => $field['type'] === 'secret' ? (Setting::get($key) !== '') : Setting::get($key)]),
            'zones' => $group === 'delivery' ? DeliveryZone::query()->orderBy('position')->orderBy('name')->get() : collect(),
        ]);
    }

    /**
     * Sends a test message with the saved e-mail settings so the shop can check its SMTP server.
     */
    public function testMail(Request $request): RedirectResponse
    {
        $data = $request->validate(['to' => ['required', 'email']], ['to.required' => 'Indiquez l’adresse qui doit recevoir le test.', 'to.email' => 'Adresse e-mail invalide.']);

        try {
            Mail::to($data['to'])->send(new ShopMail(
                mailSubject: 'Test d’envoi — '.Setting::get('shop_name'),
                paragraphs: ['Si vous lisez ce message, l’envoi d’e-mails de votre boutique fonctionne.', 'Mode d’envoi actuel : '.config('mail.default').'.'],
            ));
        } catch (Throwable $exception) {
            return back()->withErrors(['mail' => 'Échec de l’envoi : '.Str::limit($exception->getMessage(), 300)]);
        }

        $logged = in_array(config('mail.default'), ['log', 'array'], true);

        return back()->with('status', $logged ? 'Message écrit dans le journal (mode « '.config('mail.default').' ») : aucun e-mail réel n’est parti. Choisissez « Serveur SMTP » et renseignez le serveur.' : 'E-mail de test envoyé à '.$data['to'].'.');
    }

    public function update(SettingsRequest $request, string $group): RedirectResponse
    {
        $values = [];

        foreach ($request->groupFields() as $key => $field) {
            $value = $request->validated("settings.{$key}");

            if ($field['type'] === 'secret') {
                if ($request->boolean("clear_secrets.{$key}")) {
                    $values[$key] = '';
                } elseif ($value !== null && $value !== '') {
                    $values[$key] = trim($value);
                }

                continue;
            }

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
