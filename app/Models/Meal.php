<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meal extends Model
{
    
    protected $table = 'diet_meals';  
    protected $primaryKey = 'meal_id'; 
    public $timestamps = false;

    protected $fillable = [
        'diet_plan_id',
        'meal_name',
        'meal_description',
        'meal_type',
        'meal_calories',
        'meal_protein',
        'meal_carbs',
        'meal_fats',
        'meal_recipe',
        'meal_image',
        'created_at'
    ];

    // Auto add image full URL in API response
    protected $appends = ['meal_image_url'];

    // Image URL accessor
    public function getMealImageUrlAttribute()
    {
        if (!$this->meal_image) {
            return null;
        }

        return asset('storage/' . $this->meal_image);
    }

    // Relationship with Diet Plan
    public function dietPlan()
    {
        return $this->belongsTo(DietPlans::class, 'diet_plan_id', 'diet_plan_id');
    }

}
