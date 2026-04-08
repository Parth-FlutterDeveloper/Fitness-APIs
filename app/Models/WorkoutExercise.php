<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WorkoutExercise extends Model
{

    protected $table = 'workout_exercises';
    protected $primaryKey = 'workout_exercises_id';
    public $timestamps = false;

    protected $fillable = [
        'workout_id',
        'exercise_id',
        'exercise_order'
    ];

    // Exercise Relationship
    public function exercise()
    {
        return $this->belongsTo(
            Exercise::class,
            'exercise_id',
            'exercise_id'
        );
    }

    protected static function boot()
    {
        parent::boot();

        // After adding exercise
        static::created(function ($workoutExercise) {
            self::updateWorkoutDuration($workoutExercise->workout_id);
        });

        // After removing exercise
        static::deleted(function ($workoutExercise) {
            self::updateWorkoutDuration($workoutExercise->workout_id);
        });
    }

    private static function updateWorkoutDuration($workout_id)
    {
        $totalSeconds = DB::table('workout_exercises')
            ->join('exercise', 'exercise.exercise_id', '=', 'workout_exercises.exercise_id')
            ->where('workout_exercises.workout_id', $workout_id)
            ->sum('exercise.exercise_duration_second');

        $minutes = ceil($totalSeconds / 60);

        DB::table('workout')
            ->where('workout_id', $workout_id)
            ->update([
                'workout_duration_minute' => $minutes
            ]);
    }

}
