<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Soft-Delete Retention (spec §5.12)
    |--------------------------------------------------------------------------
    |
    | A soft-deleted row (organizations, surveys, survey_responses, users)
    | is hard-deleted once it has been soft-deleted for longer than this
    | many days — giving admins a real recovery window while still
    | eventually purging the data for real, rather than soft deletes being
    | a de facto "forever" retention with no actual deletion ever
    | happening. See App\Jobs\PurgeExpiredDataJob, scheduled nightly.
    |
    */

    'soft_delete_days' => (int) env('RETENTION_SOFT_DELETE_DAYS', 90),

];
