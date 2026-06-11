<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    protected $fillable = ['user_id', 'theme_mode', 'accent_color', 'fill_style', 'show_liked_posts'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
