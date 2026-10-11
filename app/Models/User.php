<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'name',
        'email',
        'password',
        'status',
        'locale',
        'theme_preference',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function surveyAssignments(): HasMany
    {
        return $this->hasMany(SurveyAssignment::class);
    }

    public function assignedSurveys(): BelongsToMany
    {
        return $this->belongsToMany(Survey::class, 'survey_assignments')->withTimestamps();
    }

    public function collectedResponses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class, 'collected_by_user_id');
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class, 'created_by');
    }

    public function auditLogEntries(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->organization_id === null && $this->hasRole('Super Admin');
    }

    /**
     * True only for a user whose sole role is Field User — someone who
     * exclusively does door-to-door collection (spec §7) via /my/surveys
     * and never the admin survey builder. Someone who holds Field User
     * alongside a higher-privileged role (e.g. an Org Admin covering field
     * work personally) still gets full admin access, so this is checked
     * by exclusion rather than a plain hasRole('Field User').
     */
    public function isFieldUserOnly(): bool
    {
        return $this->hasRole('Field User') && ! $this->hasAnyRole(['Super Admin', 'Org Admin', 'Editor']);
    }

    /**
     * The single role shown in the UI (sidebar/header) when a user holds
     * more than one — picked by seniority since that's the more relevant
     * one to display, not by Spatie's arbitrary role-assignment order.
     */
    public function primaryRoleName(): ?string
    {
        foreach (['Super Admin', 'Org Admin', 'Editor', 'Field User', 'Viewer'] as $role) {
            if ($this->hasRole($role)) {
                return $role;
            }
        }

        return null;
    }

    /**
     * Up to two letters for the header's avatar — first letter of the
     * first two words of the name ("Org Admin" -> "OA"), or just the
     * first letter for a single-word name.
     */
    public function initials(): string
    {
        $words = array_filter(explode(' ', trim($this->name)));

        return mb_strtoupper(implode('', array_map(
            fn (string $word) => mb_substr($word, 0, 1),
            array_slice($words, 0, 2)
        )));
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
