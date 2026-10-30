<?php

namespace Modules\Bookings\Policies;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Modules\Bookings\Models\BookingInquiry;

class BookingInquiryPolicy
{
    public function viewAny(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function view(User $user, BookingInquiry $inquiry): bool
    {
        return $this->belongsToListingTeam($user, $inquiry);
    }

    public function update(User $user, BookingInquiry $inquiry): bool
    {
        return $this->belongsToListingTeam($user, $inquiry);
    }

    public function accept(User $user, BookingInquiry $inquiry): bool
    {
        return $this->canManage($user, $inquiry);
    }

    public function decline(User $user, BookingInquiry $inquiry): bool
    {
        return $this->canManage($user, $inquiry);
    }

    public function cancel(User $user, BookingInquiry $inquiry): bool
    {
        return $this->canManage($user, $inquiry);
    }

    public function manageDeposits(User $user, BookingInquiry $inquiry): bool
    {
        return $this->canManage($user, $inquiry);
    }

    private function belongsToListingTeam(User $user, BookingInquiry $inquiry): bool
    {
        return $user->belongsToTeam($inquiry->team);
    }

    private function canManage(User $user, BookingInquiry $inquiry): bool
    {
        return $user->teamRole($inquiry->team)?->isAtLeast(TeamRole::Admin) ?? false;
    }
}
