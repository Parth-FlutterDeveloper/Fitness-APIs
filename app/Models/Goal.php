<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    
    protected $table = 'goals';
    protected $primaryKey = 'goal_id';
    public $timestamps = false;

    protected $fillable = ['goal_name'];

    public function workouts()
    {
        return $this->belongsToMany(
            Workout::class,
            'workout_goals',
            'goal_id',
            'workout_id'
        );
    }

}
