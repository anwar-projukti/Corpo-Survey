<?php

namespace App\Enums;

/**
 * A survey_versions.status value — draft while being edited, published
 * while live and collecting responses, archived once superseded by a
 * later published version (spec §6.2's versioning model).
 */
enum SurveyVersionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
