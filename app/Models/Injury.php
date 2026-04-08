<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Injury extends Model
{
    protected $table = 'injuries';
    protected $primaryKey = 'injury_id';
    public $timestamps = false;

    protected $fillable = [
        'injury_name',
        'injury_description',
        'injury_image',
        'injury_wrong_image',
        'injury_right_image',
        'prevention_steps',
        'recovery_tips',
        'exercise_id',
        'focus_area_id'
    ];

    protected $appends = [
        'injury_image_url',
        'injury_wrong_image_url',
        'injury_right_image_url'
    ];

    public function getInjuryImageUrlAttribute()
    {
        return $this->injury_image
            ? asset('storage/' . $this->injury_image)
            : null;
    }

    public function getInjuryWrongImageUrlAttribute()
    {
        return $this->injury_wrong_image
            ? asset('storage/' . $this->injury_wrong_image)
            : null;
    }

    public function getInjuryRightImageUrlAttribute()
    {
        return $this->injury_right_image
            ? asset('storage/' . $this->injury_right_image)
            : null;
    }

    // Relationship with Exercise
    public function exercise()
    {
        return $this->belongsTo(
            Exercise::class,
            'exercise_id',
            'exercise_id'
        );
    }

    // Relationship with Focus Area
    public function focusArea()
    {
        return $this->belongsTo(
            FocusArea::class,
            'focus_area_id',
            'focus_areas_id'
        );
    }
    
}
