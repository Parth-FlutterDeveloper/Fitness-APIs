<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FocusArea extends Model
{
    
    protected $table = 'focus_areas';
    protected $primaryKey = 'focus_areas_id';
    public $timestamps = false;

    protected $fillable = ['focus_areas_name'];

    public function workouts()
    {
        return $this->hasMany(
            Workout::class,
            'workout_focus_area_id',
            'focus_areas_id'
        );
    }

}
