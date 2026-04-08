<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\AIDietDays;

class AIDietPlans extends Model
{
    
    protected $table = 'ai_diet_plans';
    protected $primaryKey = 'ai_diet_plan_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'plan_name',
        'plan_goal',
        'body_type',
        'daily_calories',
        'duration_days',
        'ai_prompt',
        'ai_response',
        'created_at'
    ];

    public function days()
    {
        return $this->hasMany(AIDietDays::class, 'ai_diet_plan_id', 'ai_diet_plan_id')
                    ->orderBy('day_number', 'asc');
    }

}
