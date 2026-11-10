<?php

namespace Modules\Bookings\Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Bookings\Enums\CustomerPipelineStage;
use Modules\Bookings\Models\Customer;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'whatsapp_phone' => null,
            'notes' => null,
            'pipeline_stage' => CustomerPipelineStage::Lead,
        ];
    }
}
