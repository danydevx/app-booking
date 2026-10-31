<?php

namespace Modules\Platform\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Listings\Models\Listing;
use Modules\Platform\Enums\ReportReason;
use Modules\Platform\Enums\ReportStatus;
use Modules\Platform\Models\ListingReport;

/**
 * @extends Factory<ListingReport>
 */
class ListingReportFactory extends Factory
{
    protected $model = ListingReport::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'listing_id' => Listing::factory()->published(),
            'team_id' => null,
            'reporter_email' => fake()->optional()->safeEmail(),
            'reason' => fake()->randomElement(ReportReason::values()),
            'message' => fake()->optional()->sentence(12),
            'status' => ReportStatus::Open,
            'resolution_action' => null,
            'resolution_note' => null,
            'handled_by_id' => null,
            'handled_at' => null,
        ];
    }

    public function forListing(Listing $listing): static
    {
        return $this->state(fn (): array => [
            'listing_id' => $listing->id,
            'team_id' => $listing->team_id,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (): array => [
            'status' => ReportStatus::Resolved,
            'resolution_action' => 'no_violation',
            'resolution_note' => 'Revisado por el equipo de plataforma.',
            'handled_at' => now(),
        ]);
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ListingReport $report): void {
            $listingId = $report->getAttribute('listing_id');
            $teamId = $report->getAttribute('team_id');

            if ($teamId === null && $listingId !== null) {
                $report->team_id = Listing::query()
                    ->whereKey($listingId)
                    ->value('team_id');
            }
        });
    }
}
