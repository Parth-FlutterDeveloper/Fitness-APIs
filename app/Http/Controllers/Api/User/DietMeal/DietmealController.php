<?php

namespace App\Http\Controllers\Api\User\DietMeal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Meal;

class DietmealController extends Controller
{
    
    // -----------------------
    // Get All Meals
    // -----------------------
    public function getAllMeals()
    {
        $meals = Meal::with('dietPlan')
                    ->orderByDesc('meal_id')
                    ->get();

        return response()->json([
            'success' => true,
            'message' => 'Meals fetched successfully',
            'total'   => $meals->count(),
            'data'    => $meals
        ]);
    }


    // -----------------------
    // Get Meal Detail By ID
    // -----------------------
    public function getMealById($meal_id)
    {
        $meal = Meal::with('dietPlan')
                    ->where('meal_id', $meal_id)
                    ->first();

        if (!$meal) {
            return response()->json([
                'success' => false,
                'message' => 'Meal not found',
                'data'    => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Meal details fetched successfully',
            'data'    => $meal
        ], 200);
    }

    
    // -------------------------
    // Get Meals By Diet Plan ID
    // -------------------------
    public function getMealsByDietId($diet_plan_id)
    {
        $meals = Meal::with('dietPlan')
                    ->where('diet_plan_id', $diet_plan_id)
                    ->orderByDesc('meal_id')
                    ->get();

        if ($meals->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No meals found for this diet plan',
                'data'    => []
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Meals fetched successfully',
            'total'   => $meals->count(),
            'data'    => $meals
        ], 200);
    }

}
