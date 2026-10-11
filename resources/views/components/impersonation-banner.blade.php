@if (session()->has('impersonator_id'))
    <div class="flex items-center justify-center gap-3 px-4 py-2 bg-amber-500 text-amber-950 text-sm font-medium">
        <span>{{ __('messages.impersonation.banner', ['name' => auth()->user()->name]) }}</span>

        <form method="POST" action="{{ route('impersonation.destroy') }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="underline hover:no-underline font-semibold">
                {{ __('messages.impersonation.return') }}
            </button>
        </form>
    </div>
@endif
