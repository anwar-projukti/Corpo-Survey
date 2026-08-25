<x-app-layout :title="$survey->title">
    @include('surveys.partials.i18n')

    <div x-data="surveyBuilder({{ $survey->id }})" x-init="init()" class="flex flex-col h-[calc(100vh-4rem)]">
        <div x-show="loading" class="flex-1 flex items-center justify-center text-sm text-gray-500 dark:text-gray-400">
            {{ __('messages.system.loading') }}
        </div>

        <template x-if="!loading">
            <div class="flex-1 flex flex-col min-h-0">
                @include('surveys.partials.builder-header')

                <div x-show="saveError" x-cloak class="bg-red-50 dark:bg-red-950 text-red-700 dark:text-red-300 text-sm px-4 py-2" x-text="saveError"></div>

                <div class="flex-1 flex min-h-0">
                    <div class="flex-1 overflow-y-auto px-4 sm:px-6 py-6">
                        <template x-if="!version">
                            @include('surveys.partials.no-draft')
                        </template>

                        <template x-if="version">
                            <div class="max-w-3xl mx-auto space-y-4">
                                <div
                                    x-sortable="(ids) => reorderSections(ids)"
                                    :class="isDraftEditable ? '' : 'pointer-events-none'"
                                    class="space-y-4"
                                >
                                    <template x-for="section in version.sections" :key="section.id">
                                        <div x-sortable-item="section.id">
                                            @include('surveys.partials.section-card')
                                        </div>
                                    </template>
                                </div>

                                <button
                                    x-show="isDraftEditable"
                                    type="button"
                                    @click="addSection()"
                                    class="w-full rounded-card border-2 border-dashed border-border-subtle dark:border-gray-700 py-3 text-sm text-gray-500 dark:text-gray-400 hover:border-brand-400 hover:text-brand-600 dark:hover:text-brand-400 transition"
                                >
                                    + {{ __('surveys.builder.add_section') }}
                                </button>

                                @include('surveys.partials.rules-panel')
                            </div>
                        </template>
                    </div>

                    <template x-if="version && activeQuestionId">
                        <div class="w-80 shrink-0 border-l border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 overflow-y-auto">
                            @include('surveys.partials.question-config-panel')
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>
</x-app-layout>
