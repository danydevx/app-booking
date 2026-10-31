<?php

namespace Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Platform\Http\Requests\UpdatePlatformBrandingRequest;

class PlatformBrandingController extends Controller
{
    public function edit(Request $request): Response
    {
        $settings = PlatformSetting::current();

        return Inertia::render('Platform/Branding/Edit', [
            'branding' => [
                'app_name' => $settings->app_name,
                'logo_url' => $settings->logoUrl(),
                'support_email' => $settings->support_email,
                'from_address' => $settings->from_address,
                'legal_terms_url' => $settings->legal_terms_url,
                'legal_privacy_url' => $settings->legal_privacy_url,
                'updated_at' => $settings->updated_at?->toIso8601String(),
            ],
            'urls' => [
                'update' => route('platform.branding.update'),
                'dashboard' => route('platform.dashboard'),
            ],
        ]);
    }

    public function update(UpdatePlatformBrandingRequest $request): RedirectResponse
    {
        $settings = PlatformSetting::current();

        $logoPath = $settings->logo_path;
        $logo = $request->file('logo');

        if ($logo instanceof UploadedFile) {
            $newPath = $logo->store('platform/branding', 'public');

            if ($logoPath !== null) {
                Storage::disk('public')->delete($logoPath);
            }

            $logoPath = $newPath;
        } elseif ($request->boolean('remove_logo') && $logoPath !== null) {
            Storage::disk('public')->delete($logoPath);
            $logoPath = null;
        }

        $settings->forceFill([
            'app_name' => (string) $request->validated('app_name'),
            'support_email' => $request->validated('support_email'),
            'from_address' => $request->validated('from_address'),
            'legal_terms_url' => $request->validated('legal_terms_url'),
            'legal_privacy_url' => $request->validated('legal_privacy_url'),
            'logo_path' => $logoPath,
            'updated_by_id' => $request->user()?->id,
        ])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Marca de la plataforma actualizada.',
        ]);

        return to_route('platform.branding.edit');
    }
}
