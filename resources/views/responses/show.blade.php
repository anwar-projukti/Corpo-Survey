<x-app-layout :title="__('surveys.responses.title')">
    @php
        $responsesI18n = [
            'load_failed' => __('surveys.public.validation_error'),
            'save_failed' => __('surveys.responses.save_failed'),
            'no_answer' => __('surveys.responses.detail_no_answer'),
        ];
    @endphp
    <script>
        window.__i18n = @json($responsesI18n);
    </script>

    <div x-data="responseDetail({{ $survey->id }}, {{ $response->id }})" x-init="init()" class="max-w-3xl mx-auto py-8 px-4 sm:px-6">
        <a href="/surveys/{{ $survey->id }}/responses" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
            &larr; {{ __('surveys.responses.title') }}
        </a>

        <div x-show="loading" class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ __('messages.system.loading') }}</div>
        <p x-show="loadError" x-text="loadError" x-cloak class="mt-4 text-sm text-red-600 dark:text-red-400"></p>

        <template x-if="!loading && response">
            <div class="mt-4 space-y-6">
                <div class="flex items-center justify-between">
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $survey->title }}</h1>

                    <div class="flex items-center gap-2 text-sm">
                        <button type="button" :disabled="reviewWorking" @click="review('approved')" class="rounded-control border border-border-subtle dark:border-gray-700 px-3 py-1.5 hover:bg-surface-subtle dark:hover:bg-gray-800">
                            {{ __('surveys.responses.bulk_approve') }}
                        </button>
                        <button type="button" :disabled="reviewWorking" @click="review('rejected')" class="rounded-control border border-border-subtle dark:border-gray-700 px-3 py-1.5 hover:bg-surface-subtle dark:hover:bg-gray-800">
                            {{ __('surveys.responses.bulk_reject') }}
                        </button>
                        <button type="button" :disabled="reviewWorking" @click="review('archived')" class="rounded-control border border-border-subtle dark:border-gray-700 px-3 py-1.5 hover:bg-surface-subtle dark:hover:bg-gray-800">
                            {{ __('surveys.responses.bulk_archive') }}
                        </button>
                    </div>
                </div>

                {{-- Metadata --}}
                <div class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-4 grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('surveys.responses.detail_review_status') }}</p>
                        <span
                            class="inline-block text-xs px-2 py-0.5 rounded-control"
                            :class="{
                                pending: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
                                approved: 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300',
                                rejected: 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300',
                                archived: 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
                            }[response.review_status]"
                            x-text="response.review_status"
                        ></span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('surveys.responses.column_status') }}</p>
                        <p class="text-gray-900 dark:text-gray-100" x-text="response.status"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('surveys.responses.column_collection_method') }}</p>
                        <p class="text-gray-900 dark:text-gray-100" x-text="response.collection_method"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('surveys.responses.detail_started_at') }}</p>
                        <p class="text-gray-900 dark:text-gray-100" x-text="response.started_at ? new Date(response.started_at).toLocaleString() : '—'"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('surveys.responses.detail_submitted_at') }}</p>
                        <p class="text-gray-900 dark:text-gray-100" x-text="response.submitted_at ? new Date(response.submitted_at).toLocaleString() : '—'"></p>
                    </div>
                    <div x-show="response.latitude && response.longitude">
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('surveys.responses.detail_location') }}</p>
                        <p class="text-gray-900 dark:text-gray-100" x-text="`${response.latitude}, ${response.longitude}`"></p>
                    </div>
                </div>

                {{-- Answers --}}
                <div>
                    <h2 class="font-medium text-gray-900 dark:text-gray-100 mb-3">{{ __('surveys.responses.detail_answers') }}</h2>

                    <div class="space-y-3">
                        <template x-for="question in questions" :key="question.id">
                            <div class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="question.label"></p>
                                    <button
                                        type="button"
                                        x-show="editingAnswerId !== (answerFor(question.id)?.id ?? `new-${question.id}`)"
                                        @click="startEdit(answerFor(question.id), question.id)"
                                        class="shrink-0 text-xs text-brand-600 dark:text-brand-400 hover:underline"
                                    >
                                        {{ __('surveys.responses.detail_edit_answer') }}
                                    </button>
                                </div>

                                <template x-if="editingAnswerId !== (answerFor(question.id)?.id ?? `new-${question.id}`)">
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300" x-text="formatAnswer(answerFor(question.id)?.value)"></p>
                                </template>

                                <template x-if="editingAnswerId === (answerFor(question.id)?.id ?? `new-${question.id}`)">
                                    <div class="mt-2 space-y-2">
                                        <textarea
                                            x-model="editValue"
                                            rows="2"
                                            class="block w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 px-2 py-1.5 text-sm"
                                        ></textarea>
                                        <p x-show="answerError" x-text="answerError" class="text-xs text-red-600 dark:text-red-400"></p>
                                        <div class="flex gap-2">
                                            <button
                                                type="button"
                                                :disabled="savingAnswer"
                                                @click="saveAnswer(answerFor(question.id))"
                                                class="rounded-control bg-brand-600 hover:bg-brand-700 text-white text-xs font-medium px-2.5 py-1 transition"
                                            >
                                                {{ __('surveys.responses.detail_save_answer') }}
                                            </button>
                                            <button
                                                type="button"
                                                @click="cancelEdit()"
                                                class="rounded-control border border-border-subtle dark:border-gray-700 text-xs px-2.5 py-1"
                                            >
                                                {{ __('surveys.responses.detail_cancel_edit') }}
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <h2 class="font-medium text-gray-900 dark:text-gray-100 mb-3">{{ __('surveys.responses.detail_notes') }}</h2>

                    <div class="space-y-2 mb-3">
                        <p x-show="(response.notes || []).length === 0" class="text-sm text-gray-400 dark:text-gray-500">
                            {{ __('surveys.responses.detail_no_notes') }}
                        </p>

                        <template x-for="note in response.notes" :key="note.id">
                            <div class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-3">
                                <p class="text-sm text-gray-700 dark:text-gray-200" x-text="note.body"></p>
                                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                    <span x-text="note.user?.name"></span> &middot;
                                    <span x-text="new Date(note.created_at).toLocaleString()"></span>
                                </p>
                            </div>
                        </template>
                    </div>

                    <div class="flex gap-2">
                        <input
                            type="text"
                            x-model="newNote"
                            @keydown.enter="addNote()"
                            placeholder="{{ __('surveys.responses.detail_note_placeholder') }}"
                            class="flex-1 rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 px-3 py-2 text-sm"
                        >
                        <button
                            type="button"
                            :disabled="addingNote || !newNote.trim()"
                            @click="addNote()"
                            class="rounded-control bg-brand-600 hover:bg-brand-700 disabled:opacity-50 text-white text-sm font-medium px-3 py-2 transition"
                        >
                            {{ __('surveys.responses.detail_add_note') }}
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-app-layout>
