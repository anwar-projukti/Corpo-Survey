@props(['label', 'modelEn', 'modelBn', 'textarea' => false])

<div>
    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $label }}</label>
    <div class="grid sm:grid-cols-2 gap-3">
        <div>
            <span class="block text-[11px] text-gray-400 dark:text-gray-500 mb-1">{{ __('landing_content.column_english') }}</span>
            @if ($textarea)
                <textarea
                    x-model="{{ $modelEn }}"
                    rows="2"
                    class="w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2 py-1.5"
                ></textarea>
            @else
                <input
                    type="text"
                    x-model="{{ $modelEn }}"
                    class="w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2 py-1.5"
                >
            @endif
        </div>
        <div>
            <span class="block text-[11px] text-gray-400 dark:text-gray-500 mb-1">{{ __('landing_content.column_bangla') }}</span>
            @if ($textarea)
                <textarea
                    x-model="{{ $modelBn }}"
                    rows="2"
                    lang="bn"
                    class="w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2 py-1.5"
                ></textarea>
            @else
                <input
                    type="text"
                    x-model="{{ $modelBn }}"
                    lang="bn"
                    class="w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2 py-1.5"
                >
            @endif
        </div>
    </div>
</div>
