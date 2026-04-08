<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DietPlans extends Model
{

    protected $table = 'diet_plans';
    protected $primaryKey = 'diet_plan_id';
    public $timestamps = false;

    protected $fillable = [
        'diet_plans_name',
        'diet_plan_description',
        'diet_plan_goal',
        'daily_calorie_target',
        'diet_plan_image',
        'created_at'
    ];

    // Auto add this field in API response
    protected $appends = ['diet_plan_image_url'];

    // Image URL accessor
    public function getDietPlanImageUrlAttribute()
    {
        if (!$this->diet_plan_image) {
            return null;
        }

        return asset('storage/' . $this->diet_plan_image);
    }

}
