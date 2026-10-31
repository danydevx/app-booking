<?php

namespace Modules\Platform\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Listings\Enums\ListingStatus;
use Modules\Listings\Models\Listing;
use Modules\Platform\Enums\ReportReason;
use Modules\Platform\Enums\ReportStatus;
use Modules\Platform\Models\ListingReport;
use Modules\Platform\Support\PlatformAccess;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class PlatformReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_report_form_stores_reports_and_is_rate_limited(): void
    {
        $listing = Listing::factory()->published()->create();

        $this->get(route('public.reports.create', ['listing' => $listing->slug]))
            ->assertOk();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('public.reports.store', ['listing' => $listing->slug]), [
                'reason' => ReportReason::Fraud->value,
                'message' => 'El anuncio solicita anticipos por fuera de la plataforma.',
                'reporter_email' => 'reporter@example.test',
            ])->assertRedirect(route('public.listings.show', ['listing' => $listing->slug]));
        }

        $this->assertSame(5, ListingReport::query()->count());

        $report = ListingReport::query()->firstOrFail();

        $this->assertSame($listing->id, $report->listing_id);
        $this->assertSame($listing->team_id, $report->team_id);
        $this->assertSame(ReportStatus::Open, $report->status);
        $this->assertSame(ReportReason::Fraud, $report->reason);
        $this->assertSame('reporter@example.test', $report->reporter_email);

        $this->post(route('public.reports.store', ['listing' => $listing->slug]), [
            'reason' => ReportReason::Fraud->value,
        ])->assertStatus(429);

        $this->assertSame(5, ListingReport::query()->count());
    }

    public function test_report_form_rejects_draft_listings_and_suspended_accounts(): void
    {
        $draft = Listing::factory()->create();

        $this->get(route('public.reports.create', ['listing' => $draft->slug]))
            ->assertNotFound();

        $this->post(route('public.reports.store', ['listing' => $draft->slug]), [
            'reason' => ReportReason::Other->value,
            'message' => 'No debería poder reportarse.',
        ])->assertNotFound();

        $listing = Listing::factory()->published()->create();
        $team = Team::query()->findOrFail($listing->team_id);
        $team->forceFill([
            'suspended_at' => now(),
            'suspension_reason' => 'Cuenta bloqueada',
        ])->save();

        $this->get(route('public.reports.create', ['listing' => $listing->slug]))
            ->assertNotFound();

        $this->post(route('public.reports.store', ['listing' => $listing->slug]), [
            'reason' => ReportReason::Other->value,
            'message' => 'La cuenta está suspendida.',
        ])->assertNotFound();

        $this->assertSame(0, ListingReport::query()->count());
    }

    public function test_other_reason_requires_a_message(): void
    {
        $listing = Listing::factory()->published()->create();

        $this->post(route('public.reports.store', ['listing' => $listing->slug]), [
            'reason' => ReportReason::Other->value,
        ])->assertSessionHasErrors('message');

        $this->assertSame(0, ListingReport::query()->count());
    }

    public function test_superadmin_can_resolve_a_report_and_suspend_the_listing(): void
    {
        $superadmin = $this->superadmin();
        $listing = Listing::factory()->published()->create();
        $report = ListingReport::factory()->forListing($listing)->create();

        $this->actingAs($superadmin)
            ->patch(route('platform.reports.update', ['report' => $report->id]), [
                'status' => ReportStatus::Resolved->value,
                'resolution_action' => 'listing_suspended',
                'resolution_note' => 'Se verificó el cobro fuera de la plataforma.',
            ])
            ->assertRedirect();

        $report->refresh();

        $this->assertSame(ReportStatus::Resolved, $report->status);
        $this->assertSame($superadmin->id, $report->handled_by_id);
        $this->assertNotNull($report->handled_at);
        $this->assertSame(ListingStatus::Suspended, $listing->fresh()->status);

        $this->assertDatabaseHas('activity_log', [
            'description' => 'report.resolved',
            'subject_type' => ListingReport::class,
            'subject_id' => $report->id,
            'causer_id' => $superadmin->id,
        ]);

        $this->assertDatabaseHas('activity_log', [
            'description' => 'listing.suspended',
            'subject_type' => Listing::class,
            'subject_id' => $listing->id,
        ]);

        $activity = Activity::query()
            ->where('description', 'report.resolved')
            ->firstOrFail();

        $this->assertSame(
            'Se verificó el cobro fuera de la plataforma.',
            $activity->properties['resolution_note'],
        );
    }

    public function test_report_resolution_requires_action_and_note(): void
    {
        $superadmin = $this->superadmin();
        $listing = Listing::factory()->published()->create();
        $report = ListingReport::factory()->forListing($listing)->create();

        $this->actingAs($superadmin)
            ->patch(route('platform.reports.update', ['report' => $report->id]), [
                'status' => ReportStatus::Resolved->value,
            ])
            ->assertSessionHasErrors(['resolution_action', 'resolution_note']);

        $this->assertSame(ReportStatus::Open, $report->fresh()->status);

        $this->actingAs($superadmin)
            ->patch(route('platform.reports.update', ['report' => $report->id]), [
                'status' => ReportStatus::Reviewing->value,
            ])
            ->assertRedirect();

        $this->assertSame(ReportStatus::Reviewing, $report->fresh()->status);
    }

    public function test_normal_owners_cannot_resolve_reports(): void
    {
        $owner = User::factory()->create();
        $listing = Listing::factory()->forTeam($owner->currentTeam)->published()->create();
        $report = ListingReport::factory()->forListing($listing)->create();

        $this->actingAs($owner)
            ->patch(route('platform.reports.update', ['report' => $report->id]), [
                'status' => ReportStatus::Dismissed->value,
                'resolution_action' => 'no_violation',
                'resolution_note' => 'Sin infracción.',
            ])
            ->assertForbidden();

        $this->assertSame(ReportStatus::Open, $report->fresh()->status);
    }

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(PlatformAccess::ensure());

        return $user;
    }
}
