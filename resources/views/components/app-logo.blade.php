@props(['class' => 'h-8 w-8'])

<img src="{{ \App\Models\LandingContent::current()->logoUrl() }}" alt="{{ config('app.name') }}" {{ $attributes->merge(['class' => $class]) }}>
