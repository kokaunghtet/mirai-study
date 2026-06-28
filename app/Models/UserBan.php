<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBan extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'reason',
        'report_id',
        'banned_by',
        'expires_at',
        'lifted_at',
        'lifted_by',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'lifted_at' => 'datetime',
        ];
    }

    // ---- Relationships ----

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bannedBy()
    {
        return $this->belongsTo(User::class, 'banned_by');
    }

    public function liftedBy()
    {
        return $this->belongsTo(User::class, 'lifted_by');
    }

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function appeals()
    {
        return $this->hasMany(Appeal::class);
    }

    // ---- Scopes ----

    /** Active ban: not yet lifted and either permanent or not yet expired. */
    public function scopeActive($query)
    {
        return $query->whereNull('lifted_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    // ---- Helpers ----

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->lte(now());
    }

    public function hasOpenAppeal(): bool
    {
        return $this->appeals()->where('status', 'pending')->exists();
    }
}
