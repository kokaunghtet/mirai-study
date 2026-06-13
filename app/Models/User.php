<?php

namespace App\Models;

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
        'bio',
        'profile_image',
        'role',
        'status',
        'two_factor_enabled',
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
}
