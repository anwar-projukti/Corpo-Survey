@props(['class' => ''])

@php
    $user = auth()->user();
    $roleName = $user->primaryRoleName();
@endphp

<div x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false" class="relative {{ $class }}">
    <button
        type="button"
        @click="open = !open"
        class="flex items-center gap-2 h-9 pl-1.5 pr-2 rounded-control border border-border-subtle dark:border-gray-700 hover:bg-surface-subtle dark:hover:bg-gray-800 transition"
        :aria-expanded="open"
        aria-haspopup="menu"
    >
        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-100 dark:bg-brand-900 text-brand-700 dark:text-brand-300 text-xs font-semibold">
            {{ $user->initials() }}
        </span>
        <span class="hidden sm:block text-sm font-medium text-gray-700 dark:text-gray-200 truncate max-w-[10rem]">
            {{ $user->name }}
        </span>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        role="menu"
        class="absolute right-0 mt-2 w-56 rounded-card border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 shadow-lg py-1 z-50"
    >
        <div class="px-3 py-2 border-b border-border-subtle dark:border-gray-800">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $user->name }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $user->email }}</p>
            @if ($roleName)
                <p class="mt-1 inline-block text-xs px-1.5 py-0.5 rounded-control bg-brand-50 dark:bg-brand-950 text-brand-700 dark:text-brand-300">
                    {{ __('messages.roles.'.$roleName) }}
                </p>
            @endif
        </div>

        <form method="POST" action="{{ route('logout') }}" class="pt-1">
            @csrf
            <button
                type="submit"
                role="menuitem"
                class="flex w-full items-center px-3 py-1.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-surface-subtle dark:hover:bg-gray-800"
            >
                {{ __('messages.actions.log_out') }}
            </button>
        </form>
    </div>
</div>
