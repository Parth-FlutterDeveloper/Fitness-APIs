<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
    
    protected $table = 'user';   
    protected $primaryKey = 'user_id';
    public $timestamps = false;      

    protected $fillable = [
        'user_name',
        'user_email',
        'user_phone',
        'user_city',
        'user_password',
        'user_birthdate',
        'user_height',
        'user_weight',
        'user_target_weight',
        'user_gender',
        'user_goal',
        'user_body_type',
        'user_xp_points',
        'user_image',
        'otp',
        'otp_expires_at'
    ];

    protected $hidden = [
        'user_password'
    ];

    // Append full image URL And Badge automatically
    protected $appends = ['user_image_url', 'badge', 'rank'];

    // Automatically hash password
    public function setUserPasswordAttribute($value)
    {
        $this->attributes['user_password'] = Hash::make($value);
    }

    // Image URL accessor
    public function getUserImageUrlAttribute()
    {
        if (!$this->user_image) {
            return null;
        }

        return asset('storage/' . $this->user_image);
    }

    // User Rank
    public function getRankAttribute()
    {
        if ($this->user_xp_points === null) {
            return null;
        }

        return self::where('user_xp_points', '>', $this->user_xp_points)->count() + 1;
    }

    // User Badge
    public function getBadgeAttribute()
    {
        $xp = $this->user_xp_points ?? 0;

        if ($xp < 800) return "Bronze";
        if ($xp < 2000) return "Silver";
        if ($xp < 3200) return "Gold";
        if ($xp <= 4500) return "Platinum";

        return "Diamond";
    }

    public function aiWorkouts()
    {
        return $this->hasMany(AIWorkout::class, 'user_id');
    }

}
