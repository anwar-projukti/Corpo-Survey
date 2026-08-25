@props(['messages'])

@if ($messages)
    <p {{ $attributes->merge(['class' => 'mt-1 text-sm text-red-600 dark:text-red-400']) }}>
        {{ (is_array($messages) || $messages instanceof \Illuminate\Support\Collection) ? $messages[0] : $messages }}
    </p>
@endif
