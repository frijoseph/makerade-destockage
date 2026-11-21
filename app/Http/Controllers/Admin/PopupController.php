<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PopupRequest;
use App\Models\Coupon;
use App\Models\Popup;
use App\Models\Setting;
use App\Models\WheelSpin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PopupController extends Controller
{
    public function index(): View
    {
        return view('admin.popups.index', [
            'popups' => Popup::query()->withCount('spins')->orderByDesc('is_active')->orderByDesc('priority')->latest()->get(),
            'spinCount' => WheelSpin::query()->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.popups.form', ['popup' => new Popup([
            'kind' => 'promo',
            'trigger' => 'delay',
            'trigger_value' => 8,
            'frequency' => 'once',
            'devices' => 'all',
            'pages' => ['home'],
            'accent' => Setting::get('theme_color'),
            'is_active' => true,
            'priority' => 0,
            'segments' => $this->defaultSegments(),
        ]), 'coupons' => Coupon::query()->orderBy('code')->pluck('code')]);
    }

    public function store(PopupRequest $request): RedirectResponse
    {
        $popup = Popup::query()->create($this->payload($request));

        return redirect()->route('admin.popups.index')->with('status', "Popup « {$popup->name} » créé.");
    }

    public function edit(Popup $popup): View
    {
        if (empty($popup->segments)) {
            $popup->segments = $this->defaultSegments();
        }

        return view('admin.popups.form', ['popup' => $popup, 'coupons' => Coupon::query()->orderBy('code')->pluck('code')]);
    }

    public function update(PopupRequest $request, Popup $popup): RedirectResponse
    {
        $popup->update($this->payload($request, $popup));

        return redirect()->route('admin.popups.index')->with('status', "Popup « {$popup->name} » mis à jour.");
    }

    public function destroy(Popup $popup): RedirectResponse
    {
        if ($popup->image && ! str_starts_with($popup->image, 'img/')) {
            Storage::disk('public')->delete($popup->image);
        }
        $popup->delete();

        return back()->with('status', "Popup « {$popup->name} » supprimé.");
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(PopupRequest $request, ?Popup $popup = null): array
    {
        $data = $request->safe()->except(['image_file', 'remove_image']);
        $data['image'] = $this->syncImage($request, 'image', 'popups', $popup?->image);
        $data['priority'] = (int) ($data['priority'] ?? 0);

        if (($data['kind'] ?? null) === 'wheel') {
            $data['segments'] = collect($data['segments'] ?? [])->map(fn (array $segment): array => [
                'label' => $segment['label'],
                'coupon_code' => mb_strtoupper(preg_replace('/\s+/', '', (string) ($segment['coupon_code'] ?? '')) ?? ''),
                'weight' => (int) ($segment['weight'] ?? 0),
                'color' => $segment['color'] ?? '#237a32',
            ])->values()->all();
        }

        return $data;
    }

    /**
     * Starting point offered when creating a wheel; every prize is editable.
     *
     * @return list<array{label: string, coupon_code: string, weight: int, color: string}>
     */
    private function defaultSegments(): array
    {
        return [
            ['label' => '-10 %', 'coupon_code' => '', 'weight' => 30, 'color' => '#237a32'],
            ['label' => 'Perdu', 'coupon_code' => '', 'weight' => 40, 'color' => '#9aa3a0'],
            ['label' => 'Livraison offerte', 'coupon_code' => '', 'weight' => 20, 'color' => '#f2a516'],
            ['label' => 'Perdu', 'coupon_code' => '', 'weight' => 40, 'color' => '#6fae77'],
        ];
    }
}
