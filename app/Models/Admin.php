<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public $timestamps = false; 
    protected $table = 'admins';
    protected $primaryKey = 'admin_id';

    protected $fillable = [
        'admin_name',
        'admin_email',
        'admin_password',
        'admin_phone',    
        'admin_image',   
        'last_login'
    ];

    protected $hidden = [
        'admin_password'
    ];

    // Tell Laravel which column is password
    public function getAuthPassword()
    {
        return $this->admin_password;
    }

    // Tell Laravel which column is email
    public function getAuthIdentifierName()
    {
        return 'admin_email';
    }

    // Auto add image full URL in API response
    protected $appends = ['admin_image_url'];

    // Image URL accessor
    public function getAdminImageUrlAttribute()
    {
        if (!$this->admin_image) {
            return null;
        }

        return asset('storage/' . $this->admin_image);
    }


}
