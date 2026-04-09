<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workout extends Model
{

    protected $table = 'workout';
    protected $primaryKey = 'workout_id';
    public $timestamps = false;

    protected $fillable = [
        'workout_name',
        'workout_description',
        'workout_image',
        'workout_focus_area_id',
        'workout_duration_minute',
        'workout_difficulty'
    ];

    // 👇 Auto add this field in API response
    protected $appends = ['workout_image_url'];

    // 👇 Image URL accessor
    public function getWorkoutImageUrlAttribute()
    {
        if (!$this->workout_image) {
            return null;
        }

        return url(Storage::url($this->workout_image));
    }

    // ================== RELATIONSHIPS ==================

    public function focusArea()
    {
        return $this->belongsTo(
            FocusArea::class,
            'workout_focus_area_id',
            'focus_areas_id'
        );
    }

    public function goals()
    {
        return $this->belongsToMany(
            Goal::class,
            'workout_goals',
            'workout_id',
            'goal_id'
        );
    }

    public function exercises()
    {
        return $this->belongsToMany(
            Exercise::class,
            'workout_exercises',
            'workout_id',
            'exercise_id'
        )
        ->withPivot('exercise_order')
        ->orderBy('workout_exercises.exercise_order');
    }

}
