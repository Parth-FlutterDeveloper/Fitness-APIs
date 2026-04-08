<?php

namespace App\Http\Controllers\Api\Admin\Goal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Goal;
use Illuminate\Support\Facades\DB;

class GoalController extends Controller
{

    // Get All Goals
    // -------------
    public function goals()
    {
        $goals = Goal::all();

        return response()->json([
            'success' => true,
            'message' => 'Goals fetched successfully',
            'data' => $goals
        ], 200);
    }


    // Insert New Goal
    // ---------------
    public function insertGoal(Request $request)
    {
        $request->validate([
            'goal_name' => 'required|string|max:50|unique:goals,goal_name'
        ]);

        $goal = Goal::create([
            'goal_name' => $request->goal_name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Goal created successfully',
            'data' => $goal
        ], 201);
    }


    // Update Goal
    // -----------
    public function updateGoal(Request $request, $goal_id)
    {
        $request->validate([
            'goal_name' => 'required|string|max:50|unique:goals,goal_name,' . $goal_id . ',goal_id'
        ]);

        $goal = Goal::find($goal_id);

        if (!$goal) {
            return response()->json([
                'success' => false,
                'message' => 'Goal not found'
            ], 404);
        }

        $goal->update([
            'goal_name' => $request->goal_name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Goal updated successfully',
            'data' => $goal
        ], 200);
    }


    // Delete Goal
    // -----------
    public function deleteGoal($goal_id)
    {
        $goal = Goal::find($goal_id);

        if (!$goal) {
            return response()->json([
                'success' => false,
                'message' => 'Goal not found'
            ], 404);
        }

        // Check workout_goals table
        $usedInWorkouts = DB::table('workout_goals')
            ->where('goal_id', $goal_id)
            ->exists();

        // Check users table
        $usedInUsers = DB::table('user')
            ->where('user_goal', $goal_id)
            ->exists();

        // Check diet_plans table
        $usedInDietPlans = DB::table('diet_plans')
            ->where('diet_plan_goal', $goal_id)
            ->exists();

        // If used anywhere → block delete
        if ($usedInWorkouts || $usedInUsers || $usedInDietPlans) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete goal. Goal is already used in workouts, users, or diet plans.'
            ], 409);
        }

        // Safe delete
        $goal->delete();

        return response()->json([
            'success' => true,
            'message' => 'Goal deleted successfully'
        ], 200);
    }

}
