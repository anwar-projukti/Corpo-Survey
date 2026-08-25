<?php

use App\Actions\Fortify\CreateNewUser;
use App\Models\Organization;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Event;

test('a slug collision that slips past the pre-check is retried instead of failing', function () {
    // Simulate the race window: the moment CreateNewUser's exists() check
    // for slug "acme" runs (and finds nothing), a "concurrent" request
    // inserts that exact row before CreateNewUser's own insert lands.
    $inserted = false;

    Event::listen(QueryExecuted::class, function (QueryExecuted $event) use (&$inserted) {
        if (! $inserted && str_contains($event->sql, 'exists') && str_contains($event->sql, 'organizations')) {
            $inserted = true;
            Organization::withoutEvents(fn () => Organization::create(['name' => 'Acme', 'slug' => 'acme']));
        }
    });

    // The "concurrent" insert above happens inside this same test's DB
    // transaction wrapper, so if CreateNewUser's own transaction were to
    // roll back, it would take that simulated row with it too — the
    // meaningful assertion is that CreateNewUser itself recovers from the
    // collision instead of bubbling the exception up as an unhandled 500.
    $user = (new CreateNewUser)->create([
        'name' => 'Jane',
        'organization_name' => 'Acme',
        'email' => 'jane-race@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ]);

    expect($user->organization->slug)->not->toBe('acme');
});
