<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\AIDietPlans;
use App\Models\AIDietMeals;

class AIDietDays extends Model
{

    protected $table = 'ai_diet_days';
    protected $primaryKey = 'diet_day_id';
    public $timestamps = false;

    protected $fillable = [
        'ai_diet_plan_id',
        'day_number',
        'created_at'
    ];

    public function plan()
    {
        return $this->belongsTo(AIDietPlans::class, 'ai_diet_plan_id', 'ai_diet_plan_id');
    }

    public function meals()
    {
        return $this->hasMany(AIDietMeals::class, 'diet_day_id', 'diet_day_id')
                    ->orderBy('meal_order', 'asc');
    }

}
