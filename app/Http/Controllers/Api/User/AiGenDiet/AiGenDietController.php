<?php

namespace App\Http\Controllers\Api\User\AiGenDiet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AIDietPlans;
use App\Models\AIDietDays;
use App\Models\AIDietMeals;

class AiGenDietController extends Controller
{
    
    // Get All User Generated Diet
    // ------------------------------
    public function getUserDietPlans()
    {
        try {

            $userId = auth()->id();

            $plans = AIDietPlans::where('user_id', $userId)
                ->orderBy('ai_diet_plan_id', 'desc')
                ->get([
                    'ai_diet_plan_id',
                    'plan_name',
                    'plan_goal',
                    'body_type',
                    'daily_calories',
                    'duration_days',
                    'created_at'
                ]);

            return response()->json([
                'status' => true,
                'message' => 'Diet plans fetched successfully',
                'data' => $plans
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Get User Generated Diet By ID
    // ------------------------------
    public function getDietPlanById($id)
    {
        try {

            $userId = auth()->id();

            $plan = AIDietPlans::with([
                    'days' => function ($query) {
                        $query->select('diet_day_id', 'ai_diet_plan_id', 'day_number')
                            ->orderBy('day_number', 'asc');
                    },
                    'days.meals' => function ($query) {
                        $query->select(
                            'ai_diet_meal_id',
                            'diet_day_id',
                            'meal_type',
                            'meal_name',
                            'meal_description',
                            'calories',
                            'protein',
                            'carbs',
                            'fats',
                            'meal_order'
                        )->orderBy('meal_order', 'asc');
                    }
                ])
                ->where('user_id', $userId)
                ->where('ai_diet_plan_id', $id)
                ->select(
                    'ai_diet_plan_id',
                    'user_id',
                    'plan_name',
                    'plan_goal',
                    'body_type',
                    'daily_calories',
                    'duration_days',
                    'created_at'
                )
                ->first();

            if (!$plan) {
                return response()->json([
                    'status' => false,
                    'message' => 'Diet plan not found'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Diet plan fetched successfully',
                'data' => $plan
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
