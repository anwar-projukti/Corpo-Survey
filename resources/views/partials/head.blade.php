<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ $title ?? config('app.name') }}</title>

<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

{{-- Noto Sans/Serif Bengali for correct Bangla rendering (spec §4); Inter
     for Latin text. Only Google Fonts is reachable from a strict CSP. --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700;800&family=Noto+Serif+Bengali:wght@400;600&display=swap" rel="stylesheet">

@include('partials.theme-init')

@vite(['resources/css/app.css', $entry ?? 'resources/js/app.js'])
