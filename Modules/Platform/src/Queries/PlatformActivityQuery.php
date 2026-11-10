<?php

namespace Modules\Platform\Queries;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

final class PlatformActivityQuery
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Activity>
     */
    public function builder(array $filters): Builder
    {
        $search = trim((string) ($filters['q'] ?? ''));
        $actor = trim((string) ($filters['actor'] ?? ''));
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        return Activity::query()
            ->with(['causer', 'subject'])
            ->where('log_name', 'moderation')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('description', 'like', '%'.$search.'%')
                        ->orWhere('subject_type', 'like', '%'.$search.'%');
                });
            })
            ->when($actor !== '', function (Builder $query) use ($actor): void {
                $query->whereHasMorph(
                    'causer',
                    [User::class],
                    fn (Builder $causer) => $causer
                        ->where('name', 'like', '%'.$actor.'%')
                        ->orWhere('email', 'like', '%'.$actor.'%'),
                );
            })
            ->when($from !== null, fn (Builder $query) => $query->whereDate('created_at', '>=', $from))
            ->when($to !== null, fn (Builder $query) => $query->whereDate('created_at', '<=', $to));
    }
}
