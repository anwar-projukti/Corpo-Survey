<template x-if="settings.show_progress_bar && isOnePerScreen && visibleQuestions.length">
    <div class="space-y-2">
        <div class="flex items-center justify-between text-xs">
            <span class="font-medium text-brand-600 dark:text-brand-400" x-text="@js(__('surveys.public.progress'))
                .replace(':current', progress.current)
                .replace(':total', progress.total)"></span>
            <span class="text-gray-400 dark:text-gray-500" x-text="progress.percent + '%'"></span>
        </div>
        <div
            class="h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden"
            role="progressbar"
            :aria-valuenow="progress.current"
            :aria-valuemin="1"
            :aria-valuemax="progress.total"
            :aria-valuetext="@js(__('surveys.public.progress')).replace(':current', progress.current).replace(':total', progress.total)"
        >
            <div
                class="h-full rounded-full bg-gradient-to-r from-brand-500 to-brand-600 transition-all duration-500 ease-out"
                :style="`width: ${progress.percent}%`"
            ></div>
        </div>
    </div>
</template>
