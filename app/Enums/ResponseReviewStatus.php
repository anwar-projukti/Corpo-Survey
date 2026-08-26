<?php

namespace App\Enums;

/**
 * An admin's review decision on a response (spec §8) — independent of
 * App\Enums\ResponseStatus (partial/complete), which tracks whether the
 * respondent themselves finished submitting. A response can be complete
 * and still pending review, or even partial and already archived; the two
 * axes never constrain each other.
 */
enum ResponseReviewStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Archived = 'archived';
}
