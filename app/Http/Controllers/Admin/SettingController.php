<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingsRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * Keys editable from the settings screen.
     *
     * @var list<string>
     */
    private const KEYS = [
        'shop_name', 'contact_email', 'contact_phone', 'whatsapp_number',
        'currency_symbol', 'currency_position', 'currency_decimals',
        'shipping_fee', 'free_shipping_threshold',
    ];

    public function edit(): View
    {
        return view('admin.settings.edit', [
            'settings' => collect(self::KEYS)->mapWithKeys(fn (string $key): array => [$key => Setting::get($key)]),
        ]);
    }

    public function update(SettingsRequest $request): RedirectResponse
    {
        Setting::putMany($request->validated());

        return back()->with('status', 'Paramètres enregistrés.');
    }
}
