<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function __construct(
        private SettingService $settingService
    ) {}

    public function edit()
    {
        return view('settings.edit', [
            'settings' => app(SettingService::class)->getSettings(),
            'fieldDefs' => config('settings.fields'),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'settings' => ['required', 'array'],
            'logo' => ['nullable', 'image', 'max:4096'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,jpg,jpeg,svg,webp', 'max:1024'],
        ]);

        $settings = $request->input('settings', []);
        $settings['navbar_enabled'] = true;
        $current = $this->settingService->getSettings();

        if ($request->hasFile('logo')) {
            $this->deleteStoredPublicAsset($current['logo'] ?? null);
            $fileName = $request->file('logo')->getClientOriginalName();
            $path = $request->file('logo')->storeAs('settings/branding', $fileName, 'public');
            $settings['logo'] = $path;
        }

        if ($request->hasFile('favicon')) {
            $this->deleteStoredPublicAsset($current['favicon'] ?? null);
            $fileName = $request->file('favicon')->getClientOriginalName();
            $path = $request->file('favicon')->storeAs('settings/branding', $fileName, 'public');
            $settings['favicon'] = $path;
        }

        $this->settingService->setSettings($settings);

        return redirect()
            ->route('settings.edit')
            ->with('success', __('Settings saved.'));
    }

    private function deleteStoredPublicAsset(?string $value): void
    {
        if ($value === null || $value === '' || preg_match('#^https?://#i', $value) === 1) {
            return;
        }

        if (Storage::disk('public')->exists($value)) {
            Storage::disk('public')->delete($value);
        }
    }
}
