<?php

namespace Modules\Listings\Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Listings\Enums\ListingStatus;
use Modules\Listings\Models\Listing;

/**
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    protected $model = Listing::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->sentence(3);

        return [
            'team_id' => Team::factory(),
            'type' => 'terrace',
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100000, 999999),
            'description' => fake()->paragraph(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->randomElement(['CDMX', 'Jalisco', 'Nuevo León', 'Puebla']),
            'country' => 'México',
            'postal_code' => fake()->postcode(),
            'latitude' => fake()->latitude(19.0, 20.0),
            'longitude' => fake()->longitude(-99.5, -98.5),
            'capacity' => fake()->numberBetween(10, 200),
            'services' => ['wifi', 'bathroom'],
            'details' => null,
            'rules' => null,
            'contact_name' => fake()->name(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'whatsapp_phone' => null,
            'timezone' => 'America/Mexico_City',
            'status' => ListingStatus::Draft,
            'published_at' => null,
        ];
    }

    public function forTeam(Team $team): static
    {
        return $this->state(fn (): array => ['team_id' => $team->id]);
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => ListingStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (): array => [
            'status' => ListingStatus::Suspended,
            'published_at' => null,
        ]);
    }

    public function withCoordinates(float $latitude, float $longitude): static
    {
        return $this->state(fn (): array => [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }
}
