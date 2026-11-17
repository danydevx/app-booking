<?php

namespace Modules\Platform\Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Listings\Models\Listing;
use Modules\Platform\Enums\ListingClaimRole;
use Modules\Platform\Enums\ListingClaimStatus;
use Modules\Platform\Models\ListingClaim;

/**
 * @extends Factory<ListingClaim>
 */
class ListingClaimFactory extends Factory
{
    protected $model = ListingClaim::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'listing_id' => Listing::factory(),
            'team_id' => null,
            'user_id' => User::factory(),
            'target_team_id' => null,
            'business_name' => fake()->company(),
            'role' => fake()->randomElement(ListingClaimRole::values()),
            'claimant_name' => fake()->name(),
            'claimant_email' => fake()->safeEmail(),
            'claimant_phone' => '33'.fake()->numerify('########'),
            'whatsapp_phone' => null,
            'message' => fake()->paragraph(),
            'evidence_path' => null,
            'evidence_name' => null,
            'evidence_mime' => null,
            'evidence_size' => null,
            'status' => ListingClaimStatus::Pending,
            'rejection_reason' => null,
            'reviewed_by_id' => null,
            'reviewed_at' => null,
        ];
    }

    public function forListing(Listing $listing): static
    {
        return $this->state(fn (): array => [
            'listing_id' => $listing->id,
            'team_id' => $listing->team_id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
            'claimant_email' => $user->email,
            'claimant_name' => $user->name,
        ]);
    }

    public function forTeam(Team $team): static
    {
        return $this->state(fn (): array => [
            'target_team_id' => $team->id,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => ListingClaimStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => ListingClaimStatus::Rejected,
            'rejection_reason' => 'No pudimos verificar la propiedad del anuncio.',
            'reviewed_at' => now(),
        ]);
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ListingClaim $claim): void {
            $listingId = $claim->getAttribute('listing_id');
            $teamId = $claim->getAttribute('team_id');

            if ($teamId === null && $listingId !== null) {
                $claim->team_id = Listing::query()
                    ->whereKey($listingId)
                    ->value('team_id');
            }
        });
    }
}
