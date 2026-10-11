{{-- Renders one question's input, bound to answers[question.id] on the
     enclosing Alpine component. `question` and `answers`/`errors` come
     from the including scope (either the one-question-per-screen view or
     the all-on-one-page view).

     A <fieldset>/<legend> pair (rather than a bare <label>) is used for
     the question as a whole, since most question types here are option
     groups (radios/checkboxes/rating buttons), not a single form control
     a <label for> could point at — <legend> is the correct way to give a
     group of controls one accessible name. Single-input types
     (text/number/date/etc.) still get a real label/id pair below via
     :id="fieldId" so those work exactly like an ordinary labeled field.
     errors[question.id] gets its own id so every input can point at it
     via aria-describedby/aria-invalid — screen reader users get the same
     validation feedback sighted users see in the red text underneath. --}}
<fieldset
    class="space-y-2.5 border-0 p-0 m-0"
    :aria-describedby="errors[question.id] ? 'q' + question.id + '-error' : null"
    x-data="{
        get inputBase() {
            return 'block w-full rounded-control border bg-surface dark:bg-gray-900 px-3.5 py-2.5 text-base text-gray-900 dark:text-gray-100 shadow-sm transition placeholder:text-xs placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-none focus:ring-4 focus:ring-brand-500/15 '
                + (errors[question.id]
                    ? 'border-red-400 dark:border-red-600 focus:border-red-500'
                    : 'border-border-subtle dark:border-gray-700 focus:border-brand-500');
        },
    }"
>
    <legend class="block text-[15px] font-medium text-gray-900 dark:text-gray-100 p-0 leading-snug">
        <span x-text="(typeof questionIndex !== 'undefined' ? (questionIndex + 1) + '. ' : '') + question.label"></span>
        <span x-show="isRequired(question)" class="text-red-500">{{ __('surveys.public.required_indicator') }}</span>
    </legend>

    <p x-show="question.help_text" x-text="question.help_text" class="text-sm text-gray-500 dark:text-gray-400"></p>

    <template x-if="['short_text', 'email', 'phone', 'url'].includes(question.type)">
        <input
            :id="'q' + question.id"
            :type="{ short_text: 'text', email: 'email', phone: 'tel', url: 'url' }[question.type]"
            x-model="answers[question.id]"
            :placeholder="question.config?.placeholder"
            :maxlength="question.config?.maxLength"
            :aria-invalid="!!errors[question.id]"
            :aria-describedby="errors[question.id] ? 'q' + question.id + '-error' : null"
            :class="inputBase"
        />
    </template>

    <template x-if="question.type === 'long_text'">
        <textarea
            :id="'q' + question.id"
            x-model="answers[question.id]"
            :placeholder="question.config?.placeholder"
            :maxlength="question.config?.maxLength"
            rows="4"
            :aria-invalid="!!errors[question.id]"
            :aria-describedby="errors[question.id] ? 'q' + question.id + '-error' : null"
            :class="inputBase"
            class="resize-y"
        ></textarea>
    </template>

    <template x-if="['number', 'slider', 'range'].includes(question.type)">
        <input
            :id="'q' + question.id"
            type="number"
            x-model.number="answers[question.id]"
            :min="question.config?.min"
            :max="question.config?.max"
            :step="question.config?.step || 1"
            :aria-invalid="!!errors[question.id]"
            :aria-describedby="errors[question.id] ? 'q' + question.id + '-error' : null"
            :class="inputBase"
        />
    </template>

    <template x-if="question.type === 'yes_no'">
        <div class="flex gap-3">
            <template x-for="opt in ['yes', 'no']" :key="opt">
                <label
                    class="flex-1 flex items-center justify-center gap-2 rounded-control border-2 px-4 py-3 text-sm font-medium cursor-pointer transition"
                    :class="answers[question.id] === opt
                        ? 'border-brand-500 bg-brand-50 dark:bg-brand-950 text-brand-700 dark:text-brand-300'
                        : 'border-border-subtle dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                >
                    <input type="radio" class="sr-only" :name="'q' + question.id" :value="opt" x-model="answers[question.id]">
                    <svg x-show="answers[question.id] === opt" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    <span x-text="opt === 'yes' ? @js(__('surveys.public.yes')) : @js(__('surveys.public.no'))"></span>
                </label>
            </template>
        </div>
    </template>

    <template x-if="['single_choice', 'dropdown', 'searchable_dropdown'].includes(question.type)">
        <div class="space-y-2">
            <template x-for="option in question.options" :key="option.id">
                <label
                    class="flex items-center gap-3 rounded-control border-2 px-3.5 py-2.5 text-sm cursor-pointer transition"
                    :class="answers[question.id] === option.value
                        ? 'border-brand-500 bg-brand-50 dark:bg-brand-950'
                        : 'border-border-subtle dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
                >
                    <input type="radio" class="sr-only" :name="'q' + question.id" :value="option.value" x-model="answers[question.id]">
                    <span
                        class="shrink-0 flex h-4.5 w-4.5 items-center justify-center rounded-full border-2 transition"
                        :class="answers[question.id] === option.value ? 'border-brand-600' : 'border-gray-300 dark:border-gray-600'"
                    >
                        <span x-show="answers[question.id] === option.value" class="h-2 w-2 rounded-full bg-brand-600"></span>
                    </span>
                    <span x-text="option.label" class="text-gray-900 dark:text-gray-100"></span>
                </label>
            </template>
        </div>
    </template>

    <template x-if="['multiple_choice', 'image_choice'].includes(question.type)">
        <div class="space-y-2">
            <template x-for="option in question.options" :key="option.id">
                <label
                    class="flex items-center gap-3 rounded-control border-2 px-3.5 py-2.5 text-sm cursor-pointer transition"
                    :class="(answers[question.id] || []).includes(option.value)
                        ? 'border-brand-500 bg-brand-50 dark:bg-brand-950'
                        : 'border-border-subtle dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
                >
                    <input
                        type="checkbox"
                        class="sr-only"
                        :value="option.value"
                        :checked="(answers[question.id] || []).includes(option.value)"
                        @change="
                            answers[question.id] = answers[question.id] || [];
                            $event.target.checked
                                ? answers[question.id].push(option.value)
                                : answers[question.id] = answers[question.id].filter(v => v !== option.value)
                        "
                    >
                    <span
                        class="shrink-0 flex h-4.5 w-4.5 items-center justify-center rounded border-2 transition"
                        :class="(answers[question.id] || []).includes(option.value) ? 'border-brand-600 bg-brand-600' : 'border-gray-300 dark:border-gray-600'"
                    >
                        <svg x-show="(answers[question.id] || []).includes(option.value)" class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </span>
                    <span x-text="option.label" class="text-gray-900 dark:text-gray-100"></span>
                </label>
            </template>
        </div>
    </template>

    <template x-if="['star', 'numeric_rating', 'nps'].includes(question.type)">
        <div class="flex flex-wrap gap-2" role="radiogroup" :aria-label="question.label">
            <template x-for="n in Array.from({ length: (question.config?.scaleMax ?? 5) - (question.config?.scaleMin ?? 1) + 1 }, (_, i) => (question.config?.scaleMin ?? 1) + i)" :key="n">
                <button
                    type="button"
                    role="radio"
                    :aria-checked="answers[question.id] === n"
                    @click="answers[question.id] = n"
                    class="h-11 w-11 rounded-control border-2 text-sm font-semibold transition"
                    :class="answers[question.id] === n
                        ? 'border-brand-500 bg-brand-600 text-white shadow-sm'
                        : 'border-border-subtle dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                    x-text="n"
                ></button>
            </template>
        </div>
    </template>

    <template x-if="['emoji', 'likert', 'satisfaction'].includes(question.type)">
        <div class="flex flex-wrap gap-2" role="radiogroup" :aria-label="question.label">
            <template x-for="(label, i) in (question.config?.scaleLabels || [])" :key="i">
                <button
                    type="button"
                    role="radio"
                    :aria-checked="answers[question.id] === label"
                    @click="answers[question.id] = label"
                    class="rounded-control border-2 px-3.5 py-2 text-sm font-medium transition"
                    :class="answers[question.id] === label
                        ? 'border-brand-500 bg-brand-600 text-white shadow-sm'
                        : 'border-border-subtle dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                    x-text="label"
                ></button>
            </template>
        </div>
    </template>

    <template x-if="question.type === 'matrix'">
        <div class="overflow-x-auto rounded-control border border-border-subtle dark:border-gray-700">
            <table class="min-w-full text-sm">
                <thead class="bg-surface-subtle dark:bg-gray-800">
                    <tr>
                        <th class="px-3 py-2"></th>
                        <template x-for="col in question.config?.matrixColumns || []" :key="col">
                            <th class="px-3 py-2 text-center font-medium text-gray-700 dark:text-gray-300 text-xs uppercase tracking-wide" x-text="col"></th>
                        </template>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-subtle dark:divide-gray-800">
                    <template x-for="row in question.config?.matrixRows || []" :key="row">
                        <tr>
                            <td class="px-3 py-2.5 text-gray-900 dark:text-gray-100 whitespace-nowrap" x-text="row"></td>
                            <template x-for="col in question.config?.matrixColumns || []" :key="col">
                                <td class="px-3 py-2.5 text-center">
                                    <button
                                        type="button"
                                        role="radio"
                                        :aria-checked="(answers[question.id] || {})[row] === col"
                                        @click="answers[question.id] = { ...(answers[question.id] || {}), [row]: col }"
                                        class="inline-flex h-5 w-5 items-center justify-center rounded-full border-2 transition"
                                        :class="(answers[question.id] || {})[row] === col ? 'border-brand-600' : 'border-gray-300 dark:border-gray-600'"
                                    >
                                        <span x-show="(answers[question.id] || {})[row] === col" class="h-2.5 w-2.5 rounded-full bg-brand-600"></span>
                                    </button>
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
            :id="'q' + question.id"
            :type="{ date: 'date', time: 'time', date_time: 'datetime-local' }[question.type]"
            x-model="answers[question.id]"
            :aria-invalid="!!errors[question.id]"
            :aria-describedby="errors[question.id] ? 'q' + question.id + '-error' : null"
            :class="inputBase"
        />
    </template>

    <template x-if="['country', 'division', 'district', 'city'].includes(question.type)">
        <input
            :id="'q' + question.id"
            type="text"
            x-model="answers[question.id]"
            :aria-invalid="!!errors[question.id]"
            :aria-describedby="errors[question.id] ? 'q' + question.id + '-error' : null"
            :class="inputBase"
        />
    </template>

    <template x-if="question.type === 'address'">
        <textarea
            :id="'q' + question.id"
            x-model="answers[question.id]"
            rows="2"
            :aria-invalid="!!errors[question.id]"
            :aria-describedby="errors[question.id] ? 'q' + question.id + '-error' : null"
            :class="inputBase"
            class="resize-y"
        ></textarea>
    </template>

    <template x-if="question.type === 'ranking'">
        <div class="space-y-2" x-init="answers[question.id] = answers[question.id] || question.options.map(o => o.value)">
            <template x-for="(value, index) in (answers[question.id] || [])" :key="value">
                <div class="flex items-center gap-3 rounded-control border border-border-subtle dark:border-gray-700 px-3.5 py-2.5 text-sm bg-surface dark:bg-gray-900">
                    <span class="shrink-0 flex h-6 w-6 items-center justify-center rounded-full bg-surface-subtle dark:bg-gray-800 text-gray-500 dark:text-gray-400 text-xs font-semibold" x-text="index + 1"></span>
                    <span class="flex-1 text-gray-900 dark:text-gray-100" x-text="question.options.find(o => o.value === value)?.label"></span>
                    <div class="flex items-center gap-1 shrink-0">
                        <button
                            type="button"
                            @click="index > 0 && ([answers[question.id][index - 1], answers[question.id][index]] = [answers[question.id][index], answers[question.id][index - 1]])"
                            :disabled="index === 0"
                            class="flex h-7 w-7 items-center justify-center rounded-control text-gray-400 dark:text-gray-500 hover:bg-surface-subtle dark:hover:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-200 disabled:opacity-30 disabled:hover:bg-transparent transition"
                            aria-label="{{ __('surveys.public.previous_button') }}"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" /></svg>
                        </button>
                        <button
                            type="button"
                            @click="index < answers[question.id].length - 1 && ([answers[question.id][index + 1], answers[question.id][index]] = [answers[question.id][index], answers[question.id][index + 1]])"
                            :disabled="index === (answers[question.id] || []).length - 1"
                            class="flex h-7 w-7 items-center justify-center rounded-control text-gray-400 dark:text-gray-500 hover:bg-surface-subtle dark:hover:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-200 disabled:opacity-30 disabled:hover:bg-transparent transition"
                            aria-label="{{ __('surveys.public.next_button') }}"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </template>

    <template x-if="question.type === 'map_location'">
        <div class="grid grid-cols-2 gap-2.5">
            <input
                type="number"
                step="any"
                placeholder="{{ __('surveys.public.latitude') }}"
                aria-label="{{ __('surveys.public.latitude') }}"
                :value="(answers[question.id] || {}).lat"
                @input="answers[question.id] = { ...(answers[question.id] || {}), lat: $event.target.value === '' ? null : Number($event.target.value) }"
                :class="inputBase"
            />
            <input
                type="number"
                step="any"
                placeholder="{{ __('surveys.public.longitude') }}"
                aria-label="{{ __('surveys.public.longitude') }}"
                :value="(answers[question.id] || {}).lng"
                @input="answers[question.id] = { ...(answers[question.id] || {}), lng: $event.target.value === '' ? null : Number($event.target.value) }"
                :class="inputBase"
            />
        </div>
    </template>

    <template x-if="['file_upload', 'image_upload', 'signature'].includes(question.type)">
        <p class="text-sm text-gray-500 dark:text-gray-400 italic" x-text="@js(__('surveys.public.file_upload_unavailable'))"></p>
    </template>

    <p :id="'q' + question.id + '-error'" x-show="errors[question.id]" x-text="errors[question.id]" role="alert" class="flex items-center gap-1.5 text-sm text-red-600 dark:text-red-400"></p>
</fieldset>
