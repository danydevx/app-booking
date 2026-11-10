<?php

namespace Modules\Bookings\Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Bookings\Models\EmailTemplate;

/**
 * @extends Factory<EmailTemplate>
 */
class EmailTemplateFactory extends Factory
{
    protected $model = EmailTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'created_by' => null,
            'name' => fake()->words(3, true),
            'subject' => 'Seguimiento a {{nombre}}',
            'body' => "Hola {{nombre}},\n\nte contactamos sobre {{terraza}}.\n\nSaludos.",
        ];
    }
}
