<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Immutable, append-only (spec §5.10). Rows are never updated or deleted
 * by the application — see AuditLogger, whose log() is the only writer.
 * There is deliberately no `updated_at`.
 *
 * The guard below enforces this at the model layer: it's not just a
 * comment anyone could accidentally violate later by adding an update()/
 * delete() call somewhere. This is defense-in-depth, not the primary
 * control — the real guarantee for a genuinely tamper-proof log is a
 * database-level one (e.g. the app's production DB user granted INSERT+
 * SELECT only on this table, no UPDATE/DELETE), which is an operational
 * deployment step outside what application code can enforce on its own.
 */
class AuditLog extends Model
{
    public $timestamps = false;

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \LogicException('Audit log entries are immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new \LogicException('Audit log entries are immutable and cannot be deleted.');
        });
    }

    protected $fillable = [
        'organization_id',
        'actor_id',
        'actor_email',
        'action',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
