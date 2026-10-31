<?php

namespace Modules\Platform\Support;

use App\Models\Team;
use App\Models\User;
use Modules\Listings\Models\Listing;
use Spatie\Activitylog\Models\Activity;

final class ActivityPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function present(Activity $activity): array
    {
        $subject = $activity->subject;

        $subjectName = null;

        if ($subject instanceof Team || $subject instanceof Listing) {
            $subjectName = $subject->name;
        }

        $causer = $activity->causer;
        $causerName = $causer instanceof User ? $causer->name : null;

        return [
            'id' => $activity->id,
            'description' => $activity->description,
            'subject_type' => class_basename((string) $activity->subject_type),
            'subject_name' => $subjectName,
            'causer' => $causerName,
            'properties' => $activity->properties?->toArray() ?? [],
            'created_at' => $activity->created_at?->toIso8601String(),
        ];
    }
}
