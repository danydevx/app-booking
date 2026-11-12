<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Bookings\Enums\QuoteStatus;
use Modules\Bookings\Models\CustomerQuote;

final class CancelQuotation
{
    public function __construct(
        private readonly RecordQuoteStatusChange $statusHistory,
    ) {}

    public function handle(CustomerQuote $quote, User $actor): CustomerQuote
    {
        if ($quote->status === QuoteStatus::Cancelled) {
            return $quote;
        }

        return DB::transaction(function () use ($quote, $actor): CustomerQuote {
            $previous = $quote->status;

            $quote->forceFill([
                'status' => QuoteStatus::Cancelled,
                'cancelled_at' => now(),
            ])->save();

            $this->statusHistory->handle(
                $quote,
                $previous,
                QuoteStatus::Cancelled,
                $actor,
                'Cotización cancelada.',
            );

            return $quote->refresh();
        });
    }
}
