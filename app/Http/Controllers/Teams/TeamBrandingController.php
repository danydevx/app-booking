<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\SaveTeamBrandingRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TeamBrandingController extends Controller
{
    public function edit(Request $request, Team $team): Response
    {
        Gate::authorize('update', $team);

        return Inertia::render('settings/teams/Branding', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'timezone' => $team->timezone,
                'contact_email' => $team->contact_email,
                'contact_phone' => $team->contact_phone,
                'whatsapp_phone' => $team->whatsapp_phone,
                'logo_url' => $team->logoUrl(),
            ],
            'urls' => [
                'update' => route('teams.branding.update', ['team' => $team->slug]),
                'edit' => route('teams.edit', ['team' => $team->slug]),
            ],
        ]);
    }

    public function update(SaveTeamBrandingRequest $request, Team $team): RedirectResponse
    {
        Gate::authorize('update', $team);

        $logoPath = $team->logo_path;
        $logo = $request->file('logo');

        if ($logo instanceof UploadedFile) {
            $newPath = $logo->store("teams/{$team->id}/branding", 'public');

            if ($logoPath !== null) {
                Storage::disk('public')->delete($logoPath);
            }

            $logoPath = $newPath;
        } elseif ($request->boolean('remove_logo') && $logoPath !== null) {
            Storage::disk('public')->delete($logoPath);
            $logoPath = null;
        }

        $team->forceFill([
            'name' => (string) $request->validated('name'),
            'timezone' => (string) $request->validated('timezone'),
            'contact_email' => $request->validated('contact_email'),
            'contact_phone' => $request->validated('contact_phone'),
            'whatsapp_phone' => $request->validated('whatsapp_phone'),
            'logo_path' => $logoPath,
        ])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Marca y contacto actualizados.',
        ]);

        return to_route('teams.branding.edit', ['team' => $team->fresh()->slug]);
    }
}
