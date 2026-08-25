/**
 * Client-side mirror of App\Enums\QuestionType, grouped the same way the
 * spec groups them (§6.1), plus which config-panel "kind" each type uses.
 * The server is the source of truth for validation (QuestionConfigData) —
 * this registry only drives which UI to render.
 */
const TEXT_TYPES = ['short_text', 'long_text', 'number', 'email', 'phone', 'url'];
const CHOICE_TYPES = ['single_choice', 'multiple_choice', 'dropdown', 'searchable_dropdown', 'yes_no'];
const RATING_TYPES = ['star', 'numeric_rating', 'emoji', 'likert', 'nps', 'satisfaction'];
const ADVANCED_TYPES = [
    'matrix',
    'ranking',
    'slider',
    'range',
    'image_choice',
    'date',
    'time',
    'date_time',
    'file_upload',
    'image_upload',
    'signature',
];
const LOCATION_TYPES = ['country', 'division', 'district', 'city', 'address', 'map_location'];

export const QUESTION_TYPE_GROUPS = [
    { key: 'text', types: TEXT_TYPES },
    { key: 'choice', types: CHOICE_TYPES },
    { key: 'rating', types: RATING_TYPES },
    { key: 'advanced', types: ADVANCED_TYPES },
    { key: 'location', types: LOCATION_TYPES },
];

const OPTION_TYPES = ['single_choice', 'multiple_choice', 'dropdown', 'searchable_dropdown', 'image_choice', 'ranking'];
const RATING_CONFIG_TYPES = RATING_TYPES;

export function usesOptions(type) {
    return OPTION_TYPES.includes(type);
}

export function usesRatingConfig(type) {
    return RATING_CONFIG_TYPES.includes(type);
}

export function isMatrix(type) {
    return type === 'matrix';
}

export function isFileType(type) {
    return ['file_upload', 'image_upload', 'signature'].includes(type);
}

export function isNumberLike(type) {
    return ['number', 'slider', 'range'].includes(type);
}

export function isTextLike(type) {
    return ['short_text', 'long_text'].includes(type);
}

export function allQuestionTypes() {
    return QUESTION_TYPE_GROUPS.flatMap((group) => group.types);
}
