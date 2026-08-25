@php
    $groups = [
        'text' => ['short_text', 'long_text', 'number', 'email', 'phone', 'url'],
        'choice' => ['single_choice', 'multiple_choice', 'dropdown', 'searchable_dropdown', 'yes_no'],
        'rating' => ['star', 'numeric_rating', 'emoji', 'likert', 'nps', 'satisfaction'],
        'advanced' => ['matrix', 'ranking', 'slider', 'range', 'image_choice', 'date', 'time', 'date_time', 'file_upload', 'image_upload', 'signature'],
        'location' => ['country', 'division', 'district', 'city', 'address', 'map_location'],
    ];
@endphp

@foreach ($groups as $groupKey => $types)
    <p class="px-2 pt-2 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
        {{ __('surveys.question_type_groups.'.$groupKey) }}
    </p>
    @foreach ($types as $type)
        <button
            type="button"
            @click="addQuestion(section, '{{ $type }}'); open = false"
            class="w-full text-left rounded-control px-2 py-1.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-surface-subtle dark:hover:bg-gray-800 transition"
        >
            {{ __('surveys.question_types.'.$type) }}
        </button>
    @endforeach
@endforeach
