<?php

namespace Modules\Bookings\Support;

use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Modules\Bookings\Models\QuoteSequence;

final class QuoteNumberGenerator
{
    public function next(Team $team): string
    {
        return DB::transaction(function () use ($team): string {
            // Serialize numbering per account.
            Team::query()->whereKey($team->id)->lockForUpdate()->first();

            $sequence = QuoteSequence::query()->firstOrCreate(
                ['team_id' => $team->id],
                ['next_number' => 1],
            );

            $number = (int) $sequence->next_number;
            $sequence->increment('next_number');

            return 'COT-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT);
        });
    }
}
