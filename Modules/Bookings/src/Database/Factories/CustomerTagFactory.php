<?php

namespace Modules\Bookings\Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Bookings\Models\CustomerTag;

/**
 * @extends Factory<CustomerTag>
 */
class CustomerTagFactory extends Factory
{
    protected $model = CustomerTag::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'team_id' => Team::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'color' => '#64748b',
        ];
    }
}
