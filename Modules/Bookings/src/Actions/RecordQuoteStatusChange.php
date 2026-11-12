<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Modules\Bookings\Enums\QuoteStatus;
use Modules\Bookings\Models\CustomerQuote;
use Modules\Bookings\Models\CustomerQuoteStatusHistory;

final class RecordQuoteStatusChange
{
    public function handle(
        CustomerQuote $quote,
        ?QuoteStatus $from,
        QuoteStatus $to,
        ?User $actor,
        ?string $note = null,
    ): void {
        CustomerQuoteStatusHistory::query()->create([
            'customer_quote_id' => $quote->id,
            'team_id' => $quote->team_id,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'changed_by' => $actor?->id,
            'note' => $note,
        ]);
    }
}
