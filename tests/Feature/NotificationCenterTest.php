<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerQuote;
use Modules\Bookings\Notifications\CustomerQuoteAccepted;
use Modules\Bookings\Notifications\CustomerTaskDigest;
use Modules\Bookings\Notifications\NewInquiryOwnerNotification;
use Modules\Bookings\Notifications\UpcomingBookingReminder;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_and_mark_notifications_as_read(): void
    {
        $owner = User::factory()->create();
        $notificationId = $this->notification($owner, [
            'title' => 'Nueva solicitud',
            'message' => 'Ana quiere rentar una terraza.',
            'url' => '/test',
            'kind' => 'inquiry',
        ]);

        $this->actingAs($owner)
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.id', $notificationId)
            ->assertJsonPath('notifications.0.title', 'Nueva solicitud');

        $this->actingAs($owner)
            ->patchJson(route('notifications.read', ['notification' => $notificationId]))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertNotNull(
            DB::table('notifications')->where('id', $notificationId)->value('read_at'),
        );
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $owner = User::factory()->create();
        $this->notification($owner, ['title' => 'Uno']);
        $this->notification($owner, ['title' => 'Dos']);

        $this->actingAs($owner)
            ->postJson(route('notifications.read-all'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertSame(
            0,
            $owner->unreadNotifications()->count(),
        );
    }

    public function test_notifications_are_isolated_by_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->notification($owner, ['title' => 'Mía']);
        $this->notification($other, ['title' => 'Ajena']);

        $this->actingAs($owner)
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.title', 'Mía');
    }

    public function test_unread_count_is_shared_with_inertia(): void
    {
        $owner = User::factory()->create();
        $this->notification($owner, ['title' => 'Pendiente']);

        $this->actingAs($owner)
            ->get(route('dashboard', ['current_team' => $owner->currentTeam->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('notifications.unread_count', 1));
    }

    public function test_key_notifications_use_the_database_channel(): void
    {
        $owner = User::factory()->create();
        $team = $owner->currentTeam;
        $listing = Listing::factory()->forTeam($team)->published()->create();
        $inquiry = BookingInquiry::factory()->forListing($listing)->create();
        $customer = Customer::factory()->create(['team_id' => $team->id]);
        $quote = CustomerQuote::factory()->forCustomer($customer)->create([
            'listing_id' => $listing->id,
        ]);

        $this->assertContains('database', (new NewInquiryOwnerNotification($inquiry))->via($owner));
        $this->assertContains('database', (new UpcomingBookingReminder($inquiry))->via($owner));
        $this->assertContains('database', (new CustomerQuoteAccepted($quote))->via($owner));
        $this->assertContains('database', (new CustomerTaskDigest(
            $team->name,
            [['title' => 'Llamar', 'customer' => 'Ana', 'due' => '10/11/2026', 'overdue' => false]],
            '/test',
        ))->via($owner));
    }

    public function test_guest_cannot_access_notifications(): void
    {
        $this->getJson(route('notifications.index'))->assertUnauthorized();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function notification(User $user, array $data): string
    {
        $id = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $id,
            'type' => 'test-notification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode($data),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}
