<?php

namespace App\Actions\Fortify;

use App\Models\Organization;
use App\Models\PricingPlan;
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
                    // The pricing plan the registrant arrived from
                    // (RegisterController carries its id through as a
                    // "plan" input — see its own doc-block). An invalid or
                    // missing id just means no plan/trial is recorded,
                    // same as before this feature existed.
                    $plan = PricingPlan::query()->find($input['plan'] ?? null);

                    $organization = Organization::create([
                        'name' => $input['organization_name'],
                        'slug' => $this->uniqueSlug($input['organization_name'], random: $attempt > 1),
                        'pricing_plan_id' => $plan?->id,
                        'trial_ends_at' => $plan?->hasTrial() ? now()->addDays($plan->trial_days) : null,
                    ]);

                    $user = User::create([
                        'organization_id' => $organization->id,
                        'name' => $input['name'],
                        'email' => $input['email'],
                        'password' => Hash::make($input['password']),
                    ]);

                    // email_verified_at isn't mass-assignable (User::$fillable)
                    // — force it here since organization registration no
                    // longer requires proving inbox ownership before use.
                    // Without this, every 'verified'-gated route (routes/web.php)
                    // would redirect a brand-new Org Admin straight to
                    // Fortify's "verify your email" screen after registering,
                    // with no way past it short of clicking a real emailed
                    // link. Mirrors App\Actions\Users\CreateTeammate, which
                    // force-fills the same field for the same reason.
                    $user->forceFill(['email_verified_at' => now()])->save();

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
