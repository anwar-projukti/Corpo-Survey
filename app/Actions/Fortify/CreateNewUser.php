<?php

namespace App\Actions\Fortify;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Public registration always creates a brand-new organization with the
 * registrant as its Org Admin. Inviting additional teammates into an
 * existing organization is a separate, authenticated user-management flow
 * (see spec §13 "Users & Permissions") — not this action.
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user and their organization.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'organization_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        // The slug uniqueness check-then-insert below is racy under
        // concurrent registrations of the same organization name; on a
        // collision (unique constraint violation) we retry once with a
        // random suffix instead of surfacing a raw 500.
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                return DB::transaction(function () use ($input, $attempt) {
                    $organization = Organization::create([
                        'name' => $input['organization_name'],
                        'slug' => $this->uniqueSlug($input['organization_name'], random: $attempt > 1),
                    ]);

                    $user = User::create([
                        'organization_id' => $organization->id,
                        'name' => $input['name'],
                        'email' => $input['email'],
                        'password' => Hash::make($input['password']),
                    ]);

                    $user->assignRole('Org Admin');

                    return $user;
                });
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === 2) {
                    throw $exception;
                }
            }
        }
    }

    private function uniqueSlug(string $name, bool $random = false): string
    {
        $base = Str::slug($name);
        $slug = $random ? $base.'-'.Str::random(6) : $base;
        $suffix = 1;

        while (Organization::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$suffix).($random ? '-'.Str::random(6) : '');
        }

        return $slug;
    }
}
