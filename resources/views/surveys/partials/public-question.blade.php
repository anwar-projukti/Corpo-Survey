{{-- Renders one question's input, bound to answers[question.id] on the
     enclosing Alpine component. `question` and `answers`/`errors` come
     from the including scope (either the one-question-per-screen view or
     the all-on-one-page view). --}}
<div class="space-y-2">
    <label class="block text-sm font-medium text-gray-900 dark:text-gray-100" x-text="question.label + (isRequired(question) ? ' ' + @js(__('surveys.public.required_indicator')) : '')"></label>

    <p x-show="question.help_text" x-text="question.help_text" class="text-sm text-gray-500 dark:text-gray-400"></p>

    <template x-if="['short_text', 'email', 'phone', 'url'].includes(question.type)">
        <input
            :type="{ short_text: 'text', email: 'email', phone: 'tel', url: 'url' }[question.type]"
            x-model="answers[question.id]"
            class="mt-1 block w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 px-3 py-2 text-base text-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none"
        />
    </template>

    <template x-if="question.type === 'long_text'">
        <textarea
            x-model="answers[question.id]"
            rows="4"
            class="mt-1 block w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 px-3 py-2 text-base text-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none"
        ></textarea>
    </template>

    <template x-if="['number', 'slider', 'range'].includes(question.type)">
        <input
            type="number"
            x-model.number="answers[question.id]"
            :min="question.config?.min"
            :max="question.config?.max"
            :step="question.config?.step || 1"
            class="mt-1 block w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 px-3 py-2 text-base text-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none"
        />
    </template>

    <template x-if="question.type === 'yes_no'">
        <div class="flex gap-3">
            <template x-for="opt in ['yes', 'no']" :key="opt">
                <label class="flex-1 flex items-center justify-center gap-2 rounded-control border border-border-subtle dark:border-gray-700 px-3 py-2 text-sm cursor-pointer" :class="answers[question.id] === opt ? 'border-brand-500 bg-brand-50 dark:bg-brand-950 text-brand-700 dark:text-brand-300' : 'text-gray-700 dark:text-gray-300'">
                    <input type="radio" class="sr-only" :name="'q' + question.id" :value="opt" x-model="answers[question.id]">
                    <span x-text="opt === 'yes' ? @js(__('surveys.public.yes')) : @js(__('surveys.public.no'))"></span>
                </label>
            </template>
        </div>
    </template>

    <template x-if="['single_choice', 'dropdown', 'searchable_dropdown'].includes(question.type)">
        <div class="space-y-2">
            <template x-for="option in question.options" :key="option.id">
                <label class="flex items-center gap-2 rounded-control border border-border-subtle dark:border-gray-700 px-3 py-2 text-sm cursor-pointer" :class="answers[question.id] === option.value ? 'border-brand-500 bg-brand-50 dark:bg-brand-950' : ''">
                    <input type="radio" :name="'q' + question.id" :value="option.value" x-model="answers[question.id]">
                    <span x-text="option.label" class="text-gray-900 dark:text-gray-100"></span>
                </label>
            </template>
        </div>
    </template>

    <template x-if="['multiple_choice', 'image_choice'].includes(question.type)">
        <div class="space-y-2">
            <template x-for="option in question.options" :key="option.id">
                <label class="flex items-center gap-2 rounded-control border border-border-subtle dark:border-gray-700 px-3 py-2 text-sm cursor-pointer">
                    <input
                        type="checkbox"
                        :value="option.value"
                        :checked="(answers[question.id] || []).includes(option.value)"
                        @change="
                            answers[question.id] = answers[question.id] || [];
                            $event.target.checked
                                ? answers[question.id].push(option.value)
                                : answers[question.id] = answers[question.id].filter(v => v !== option.value)
                        "
                    >
                    <span x-text="option.label" class="text-gray-900 dark:text-gray-100"></span>
                </label>
            </template>
        </div>
    </template>

    <template x-if="['star', 'numeric_rating', 'nps'].includes(question.type)">
        <div class="flex flex-wrap gap-2">
            <template x-for="n in Array.from({ length: (question.config?.scaleMax ?? 5) - (question.config?.scaleMin ?? 1) + 1 }, (_, i) => (question.config?.scaleMin ?? 1) + i)" :key="n">
                <button
                    type="button"
                    @click="answers[question.id] = n"
                    class="h-10 w-10 rounded-control border text-sm font-medium"
                    :class="answers[question.id] === n ? 'border-brand-500 bg-brand-50 dark:bg-brand-950 text-brand-700 dark:text-brand-300' : 'border-border-subtle dark:border-gray-700 text-gray-700 dark:text-gray-300'"
                    x-text="n"
                ></button>
            </template>
        </div>
    </template>

    <template x-if="['emoji', 'likert', 'satisfaction'].includes(question.type)">
        <div class="flex flex-wrap gap-2">
            <template x-for="(label, i) in (question.config?.scaleLabels || [])" :key="i">
                <button
                    type="button"
                    @click="answers[question.id] = label"
                    class="rounded-control border px-3 py-2 text-sm"
                    :class="answers[question.id] === label ? 'border-brand-500 bg-brand-50 dark:bg-brand-950 text-brand-700 dark:text-brand-300' : 'border-border-subtle dark:border-gray-700 text-gray-700 dark:text-gray-300'"
                    x-text="label"
                ></button>
            </template>
        </div>
    </template>

    <template x-if="question.type === 'matrix'">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr>
                        <th></th>
                        <template x-for="col in question.config?.matrixColumns || []" :key="col">
                            <th class="px-2 py-1 text-center font-medium text-gray-700 dark:text-gray-300" x-text="col"></th>
                        </template>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in question.config?.matrixRows || []" :key="row">
                        <tr>
                            <td class="px-2 py-1 text-gray-900 dark:text-gray-100" x-text="row"></td>
                            <template x-for="col in question.config?.matrixColumns || []" :key="col">
                                <td class="px-2 py-1 text-center">
                                    <input
                                        type="radio"
                                        :name="'q' + question.id + '_' + row"
                                        :checked="(answers[question.id] || {})[row] === col"
                                        @change="answers[question.id] = { ...(answers[question.id] || {}), [row]: col }"
                                    >
                                </td>
                            </template>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </template>

    <template x-if="['date', 'time', 'date_time'].includes(question.type)">
        <input
            :type="{ date: 'date', time: 'time', date_time: 'datetime-local' }[question.type]"
            x-model="answers[question.id]"
            class="mt-1 block w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 px-3 py-2 text-base text-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none"
        />
    </template>

    <template x-if="['country', 'division', 'district', 'city'].includes(question.type)">
        <input
            type="text"
            x-model="answers[question.id]"
            class="mt-1 block w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 px-3 py-2 text-base text-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none"
        />
    </template>

    <template x-if="question.type === 'address'">
        <textarea
            x-model="answers[question.id]"
            rows="2"
            class="mt-1 block w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 px-3 py-2 text-base text-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none"
        ></textarea>
    </template>

    <template x-if="question.type === 'ranking'">
        <div class="space-y-2" x-init="answers[question.id] = answers[question.id] || question.options.map(o => o.value)">
            <template x-for="(value, index) in (answers[question.id] || [])" :key="value">
                <div class="flex items-center gap-2 rounded-control border border-border-subtle dark:border-gray-700 px-3 py-2 text-sm bg-surface dark:bg-gray-900">
                    <span class="text-gray-400 dark:text-gray-500 w-5 text-center" x-text="index + 1"></span>
                    <span class="flex-1 text-gray-900 dark:text-gray-100" x-text="question.options.find(o => o.value === value)?.label"></span>
                    <button type="button" @click="index > 0 && ([answers[question.id][index - 1], answers[question.id][index]] = [answers[question.id][index], answers[question.id][index - 1]])" :disabled="index === 0" class="disabled:opacity-30 text-gray-500 dark:text-gray-400" aria-label="{{ __('surveys.public.previous_button') }}">▲</button>
                    <button type="button" @click="index < answers[question.id].length - 1 && ([answers[question.id][index + 1], answers[question.id][index]] = [answers[question.id][index], answers[question.id][index + 1]])" :disabled="index === (answers[question.id] || []).length - 1" class="disabled:opacity-30 text-gray-500 dark:text-gray-400" aria-label="{{ __('surveys.public.next_button') }}">▼</button>
                </div>
            </template>
        </div>
    </template>

    <template x-if="question.type === 'map_location'">
        <div class="grid grid-cols-2 gap-2">
            <input
                type="number"
                step="any"
                placeholder="Latitude"
                :value="(answers[question.id] || {}).lat"
                @input="answers[question.id] = { ...(answers[question.id] || {}), lat: $event.target.value === '' ? null : Number($event.target.value) }"
                class="mt-1 block w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 px-3 py-2 text-base text-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none"
            />
            <input
                type="number"
                step="any"
                placeholder="Longitude"
                :value="(answers[question.id] || {}).lng"
                @input="answers[question.id] = { ...(answers[question.id] || {}), lng: $event.target.value === '' ? null : Number($event.target.value) }"
                class="mt-1 block w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 px-3 py-2 text-base text-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none"
            />
        </div>
    </template>

    <template x-if="['file_upload', 'image_upload', 'signature'].includes(question.type)">
        <p class="text-sm text-gray-500 dark:text-gray-400 italic" x-text="@js(__('surveys.public.file_upload_unavailable'))"></p>
    </template>

    <p x-show="errors[question.id]" x-text="errors[question.id]" class="text-sm text-red-600 dark:text-red-400"></p>
</div>
