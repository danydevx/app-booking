<?php

namespace Modules\Platform\Tests\Feature;

use App\Models\PlatformSetting;
use App\Models\User;
use App\Support\Branding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Platform\Support\PlatformAccess;
use Tests\TestCase;

class PlatformBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_update_platform_branding(): void
    {
        Storage::fake('public');

        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)
            ->get(route('platform.branding.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Platform/Branding/Edit'));

        $this->actingAs($superadmin)
            ->patch(route('platform.branding.update'), [
                'app_name' => 'Terrazas MX',
                'support_email' => 'soporte@terrazas.test',
                'from_address' => 'no-reply@terrazas.test',
                'legal_terms_url' => 'https://terrazas.test/terminos',
                'legal_privacy_url' => 'https://terrazas.test/privacidad',
                'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
            ])
            ->assertRedirect(route('platform.branding.edit'));

        $settings = PlatformSetting::current();

        $this->assertSame('Terrazas MX', $settings->app_name);
        $this->assertSame('soporte@terrazas.test', $settings->support_email);
        $this->assertSame('no-reply@terrazas.test', $settings->from_address);
        $this->assertSame($superadmin->id, $settings->updated_by_id);
        $this->assertNotNull($settings->logo_path);
        Storage::disk('public')->assertExists($settings->logo_path);

        $this->assertSame('no-reply@terrazas.test', Branding::fromAddress());
        $this->assertSame('Terrazas MX', Branding::appName());
    }

    public function test_normal_users_cannot_manage_platform_branding(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('platform.branding.edit'))
            ->assertForbidden();

        $this->actingAs($user)
            ->patch(route('platform.branding.update'), [
                'app_name' => 'Intento',
                'from_address' => 'intento@example.test',
            ])
            ->assertForbidden();
    }

    public function test_branding_rejects_invalid_urls(): void
    {
        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)
            ->patch(route('platform.branding.update'), [
                'app_name' => 'Terrazas',
                'from_address' => 'no-reply@terrazas.test',
                'legal_terms_url' => 'not-a-url',
            ])
            ->assertSessionHasErrors('legal_terms_url');
    }

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(PlatformAccess::ensure());

        return $user;
    }
}
