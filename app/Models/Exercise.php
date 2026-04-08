<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\WorkoutExercise;
use App\Models\UserExercise;
use App\Models\UserProgress;
use App\Models\Injury;

class Exercise extends Model
{
    
    protected $table = 'exercise';
    protected $primaryKey = 'exercise_id';
    public $timestamps = false;

    protected $fillable = [
        'exercise_name',
        'exercise_description',
        'exercise_duration_second',
        'exercise_sets',
        'exercise_reps',
        'exercise_calories_burn',
        'exercise_gif_url',
        'exercise_xp'
    ];

     // 👇 Auto add this field in API response
    protected $appends = ['exercise_gif_full_url'];

    // 👇 GIF URL accessor (same logic as workout image)
    public function getExerciseGifFullUrlAttribute()
    {
        if (!$this->exercise_gif_url) {
            return null;
        }

        return asset('storage/' . $this->exercise_gif_url);
    }

    // 🔗 Relations
    public function workoutExercises()
    {
        return $this->hasMany(WorkoutExercise::class, 'exercise_id');
    }

    public function userProgress()
    {
        return $this->hasMany(UserProgress::class, 'exercise_id');
    }

    public function injuries()
    {
        return $this->hasMany(Injury::class, 'exercise_id');
    }

}
