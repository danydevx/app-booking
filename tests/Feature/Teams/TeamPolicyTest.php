<?php

namespace Tests\Feature\Teams;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TeamPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_account_owner_can_manage_billing(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $stranger = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $this->assertTrue(Gate::forUser($owner)->allows('manageBilling', $team));
        $this->assertFalse(Gate::forUser($member)->allows('manageBilling', $team));
        $this->assertFalse(Gate::forUser($stranger)->allows('manageBilling', $team));
    }

    public function test_only_members_can_view_the_account(): void
    {
        $member = User::factory()->create();
        $stranger = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $this->assertTrue(Gate::forUser($member)->allows('view', $team));
        $this->assertFalse(Gate::forUser($stranger)->allows('view', $team));
    }
}
