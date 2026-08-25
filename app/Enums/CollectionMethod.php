<?php

namespace App\Enums;

/**
 * How a survey_responses row was collected (spec §7). collected_by_user_id
 * is set server-side only when this is FieldAgent — never trusted from
 * client input.
 */
enum CollectionMethod: string
{
    case SelfServe = 'self_serve';
    case FieldAgent = 'field_agent';
}
