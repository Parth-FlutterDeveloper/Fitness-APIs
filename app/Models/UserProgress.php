<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProgress extends Model
{
    protected $table = 'user_progress';

    protected $primaryKey = 'user_progress_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'workout_id',
        'ai_workout_id',
        'exercise_id',
        'exercise_order',
        'session_id',
        'workout_date',
        'workout_time',
        'sets_completed',
        'reps_completed',
        'exercise_duration_sec',
        'calories_burned',
        'xp_earned',
        'is_completed',
        'created_at'
    ];

    // ================= RELATIONSHIPS =================

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'user_id'
        );
    }

    public function workout()
    {
        return $this->belongsTo(
            Workout::class,
            'workout_id',
            'workout_id'
        );
    }

    public function exercise()
    {
        return $this->belongsTo(
            Exercise::class,
            'exercise_id',
            'exercise_id'
        );
    }

}
    