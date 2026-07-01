<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    // Use the trait for hasVerifiedEmail()/markEmailAsVerified() helpers, but do NOT
    // implement the MustVerifyEmail *interface* — that would make the Registered event
    // auto-send Breeze's link email and double up with our OTP code flow.
    use HasFactory, MustVerifyEmailTrait, Notifiable, SoftDeletes;

    protected $fillable = [
        'username',
        'display_name',
        'email',
        'password',
        'google_id',
        'bio',
        'profile_image',
        'role',
        'status',
        'two_factor_enabled',
        'deletion_scheduled_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
            'deletion_scheduled_at' => 'datetime',
        ];
    }

    // ---- Relationships ----

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function preferences()
    {
        return $this->hasOne(UserPreference::class);
    }

    public function pomodoroSettings()
    {
        return $this->hasOne(PomodoroSetting::class);
    }

    public function timerSessions()
    {
        return $this->hasMany(TimerSession::class);
    }

    public function otps()
    {
        return $this->hasMany(Otp::class);
    }

    public function appNotifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function bans()
    {
        return $this->hasMany(UserBan::class);
    }

    public function appeals()
    {
        return $this->hasMany(Appeal::class);
    }

    public function likedPosts()
    {
        return $this->belongsToMany(Post::class, 'post_likes');
    }

    public function bookmarkedPosts()
    {
        return $this->belongsToMany(Post::class, 'bookmarks');
    }

    // Users this user is following
    public function following()
    {
        return $this->belongsToMany(
            User::class,
            'follows',
            'follower_id',
            'following_id'
        )->withPivot('status');
    }

    // Users who follow this user
    public function followers()
    {
        return $this->belongsToMany(
            User::class,
            'follows',
            'following_id',
            'follower_id'
        )->withPivot('status');
    }

    // ---- Helpers ----

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isModerator(): bool
    {
        return $this->role === 'moderator';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Permanent ban (status=banned, no expiry). */
    public function isBanned(): bool
    {
        return $this->status === 'banned';
    }

    /** Active temporary ban (status=suspended, not yet expired). */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * True if the user is currently banned or suspended.
     * Lazily lifts expired temp bans as a side-effect so no scheduler is needed.
     */
    public function isBannedNow(): bool
    {
        if ($this->status === 'banned') {
            return true;
        }

        if ($this->status === 'suspended') {
            $active = $this->bans()->active()->latest()->first();

            if ($active === null || $active->isExpired()) {
                // Auto-lift: ban expired or orphaned suspended status
                if ($active) {
                    $active->update(['lifted_at' => now()]);
                }
                $this->update(['status' => 'active']);

                return false;
            }

            return true;
        }

        return false;
    }

    /** The currently active UserBan row (null if not banned). */
    public function activeBan(): ?UserBan
    {
        if (! $this->isBanned() && ! $this->isSuspended()) {
            return null;
        }

        return $this->bans()->active()->with(['bannedBy', 'report'])->latest()->first();
    }

    // ---- Account Deletion Helpers ----

    /** Check if this user has a scheduled deletion (grace period active). */
    public function isDeletionScheduled(): bool
    {
        return $this->trashed() && $this->deletion_scheduled_at !== null;
    }

    /** Get the scheduled deletion date. */
    public function deletionDate(): ?Carbon
    {
        return $this->deletion_scheduled_at;
    }

    /** Restore a soft-deleted account by clearing deletion markers. */
    public function restoreFromDeletion(): bool
    {
        return $this->restore() && $this->update(['deletion_scheduled_at' => null]);
    }
}
