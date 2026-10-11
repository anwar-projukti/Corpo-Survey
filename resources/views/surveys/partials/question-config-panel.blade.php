<div
    x-data="{
        get section() {
            return version.sections.find(s => s.questions.some(q => q.id === activeQuestionId));
        },
        get question() {
            return this.section?.questions.find(q => q.id === activeQuestionId);
        },
        usesOptions(type) {
            return ['single_choice', 'multiple_choice', 'dropdown', 'searchable_dropdown', 'image_choice', 'ranking'].includes(type);
        },
        usesRatingConfig(type) {
            return ['star', 'numeric_rating', 'emoji', 'likert', 'nps', 'satisfaction'].includes(type);
        },
        isMatrix(type) {
            return type === 'matrix';
        },
        isNumberLike(type) {
            return ['number', 'slider', 'range'].includes(type);
        },
        saveConfig(patch) {
            updateQuestion(this.question, { config: { ...(this.question.config || {}), ...patch } });
        },
    }"
    x-show="question"
    class="p-4 space-y-4"
>
    <template x-if="question">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('messages.actions.edit') }}</h3>
                <button type="button" @click="activeQuestionId = null" aria-label="{{ __('messages.actions.close') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('surveys.builder.question_label') }}</label>
                <textarea
                    :value="question.label"
                    @blur="updateQuestion(question, { label: $event.target.value })"
                    :disabled="!isDraftEditable"
                    rows="2"
                    class="mt-1 block w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-950 px-2 py-1.5 text-sm disabled:opacity-70"
                ></textarea>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('surveys.builder.help_text') }}</label>
                <input
                    type="text"
                    :value="question.help_text"
                    @blur="updateQuestion(question, { help_text: $event.target.value })"
                    :disabled="!isDraftEditable"
                    class="mt-1 block w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-950 px-2 py-1.5 text-sm disabled:opacity-70"
                >
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input
                    type="checkbox"
                    :checked="question.is_required"
                    @change="updateQuestion(question, { is_required: $event.target.checked })"
                    :disabled="!isDraftEditable"
                    class="rounded border-border-subtle dark:border-gray-700 text-brand-600 focus:ring-brand-500"
                >
                {{ __('surveys.builder.required') }}
            </label>

            <div x-show="usesOptions(question.type)" x-cloak>
                @include('surveys.partials.config.options')
            </div>

            <div x-show="isMatrix(question.type)" x-cloak>
                @include('surveys.partials.config.matrix')
            </div>

            <div x-show="usesRatingConfig(question.type)" x-cloak>
                @include('surveys.partials.config.rating')
            </div>

            <div x-show="isNumberLike(question.type)" x-cloak>
                @include('surveys.partials.config.number-bounds')
            </div>

            <div x-show="['short_text', 'long_text'].includes(question.type)" x-cloak>
                @include('surveys.partials.config.text-bounds')
            </div>
        </div>
    </template>
</div>
