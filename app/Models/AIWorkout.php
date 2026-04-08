<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIWorkout extends Model
{
    
    protected $table = 'ai_workouts';

    protected $primaryKey = 'ai_workout_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'workout_name',
        'workout_goal',
        'workout_focus_area',
        'workout_duration',
        'workout_difficulty',
        'prompt',
        'ai_response',
        'body_type',
        'created_at'
    ];

    // Relationship: AI Workout belongs to User
    // ----------------------------------------
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // In AIWorkout model
    // ------------------
    public function exercises()
    {
        return $this->hasMany(AIWorkoutExercise::class, 'ai_workout_id');
    }

}
