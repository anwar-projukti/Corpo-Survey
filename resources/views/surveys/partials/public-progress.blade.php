<template x-if="settings.show_progress_bar && isOnePerScreen && visibleQuestions.length">
    <div class="space-y-1">
        <div class="h-1.5 rounded-full bg-surface-subtle dark:bg-gray-800 overflow-hidden">
            <div class="h-full bg-brand-600 transition-all" :style="`width: ${progress.percent}%`"></div>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="@js(__('surveys.public.progress'))
            .replace(':current', progress.current)
            .replace(':total', progress.total)"></p>
    </div>
</template>
