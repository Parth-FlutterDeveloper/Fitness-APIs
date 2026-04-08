<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIWorkoutExercise extends Model
{
    
    protected $table = 'ai_workout_exercises';

    protected $primaryKey = 'ai_workout_exercise_id';

    public $timestamps = false;

    protected $fillable = [
        'ai_workout_id',
        'exercise_id',
        'exercise_name',
        'exercise_sets',
        'exercise_reps',
        'exercise_duration_sec',
        'exercise_order',
        'exercise_xp'
    ];

    // Relationship: belongs to AI Workout
    // -----------------------------------
    public function aiWorkout()
    {
        return $this->belongsTo(AIWorkout::class, 'ai_workout_id');
    }

    // Relationship: belongs to Exercise (main exercise table)
    // -------------------------------------------------------
    public function exercise()
    {
        return $this->belongsTo(Exercise::class, 'exercise_id');
    }

}
