<?php

namespace App\Enums;

/**
 * Every question type from spec §6.1. The survey builder never introduces
 * a new fixed database column per type — this enum plus
 * survey_questions.config (JSON) is the entire type system.
 */
enum QuestionType: string
{
    // Text
    case ShortText = 'short_text';
    case LongText = 'long_text';
    case Number = 'number';
    case Email = 'email';
    case Phone = 'phone';
    case Url = 'url';

    // Selection
    case SingleChoice = 'single_choice';
    case MultipleChoice = 'multiple_choice';
    case Dropdown = 'dropdown';
    case SearchableDropdown = 'searchable_dropdown';
    case YesNo = 'yes_no';

    // Rating
    case Star = 'star';
    case NumericRating = 'numeric_rating';
    case Emoji = 'emoji';
    case Likert = 'likert';
    case Nps = 'nps';
    case Satisfaction = 'satisfaction';

    // Advanced
    case Matrix = 'matrix';
    case Ranking = 'ranking';
    case Slider = 'slider';
    case Range = 'range';
    case ImageChoice = 'image_choice';
    case Date = 'date';
    case Time = 'time';
    case DateTime = 'date_time';
    case FileUpload = 'file_upload';
    case ImageUpload = 'image_upload';
    case Signature = 'signature';

    // Location
    case Country = 'country';
    case Division = 'division';
    case District = 'district';
    case City = 'city';
    case Address = 'address';
    case MapLocation = 'map_location';

    /**
     * Question types whose config includes an option list (question_options).
     */
    public function usesOptions(): bool
    {
        return in_array($this, [
            self::SingleChoice,
            self::MultipleChoice,
            self::Dropdown,
            self::SearchableDropdown,
            self::ImageChoice,
            self::Ranking,
        ], true);
    }

    /**
     * Question types that accept a file upload (needs storage/validation
     * wiring per spec §5.9, not just a text answer).
     */
    public function isFileType(): bool
    {
        return in_array($this, [self::FileUpload, self::ImageUpload, self::Signature], true);
    }
}
