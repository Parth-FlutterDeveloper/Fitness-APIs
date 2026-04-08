<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\AIDietDays;

class AIDietMeals extends Model
{
    
    protected $table = 'ai_diet_meals';
    protected $primaryKey = 'ai_diet_meal_id';
    public $timestamps = false;

    protected $fillable = [
        'diet_day_id',
        'meal_type',
        'meal_name',
        'meal_description',
        'calories',
        'protein',
        'carbs',
        'fats',
        'meal_order',
        'created_at'
    ];

    public function day()
    {
        return $this->belongsTo(AIDietDays::class, 'diet_day_id', 'diet_day_id');
    }

}
