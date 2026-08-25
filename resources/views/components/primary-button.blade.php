<button {{ $attributes->merge(['type' => 'submit', 'class' => 'w-full rounded-control bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium py-2.5 transition']) }}>
    {{ $slot }}
</button>
