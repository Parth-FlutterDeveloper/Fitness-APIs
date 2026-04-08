<?php

namespace App\Http\Controllers\Api\User\AiGenWorkout;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AIWorkout;
use App\Models\AIWorkoutExercise;

class AiGenWorkoutController extends Controller
{

    // Get All User Generated Workout
    // ------------------------------
    public function getUserWorkouts()
    {
        $user = auth()->user();

        // Fetch workouts with only selected fields
        $workouts = $user->aiWorkouts()
                        ->orderBy('created_at', 'desc')
                        ->get(['ai_workout_id', 'workout_name', 'workout_goal', 'workout_focus_area', 'workout_duration', 'workout_difficulty', 'body_type', 'created_at']);

        if ($workouts->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No workouts found',
                'data' => []
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'User workouts fetched successfully',
            'data' => $workouts
        ]);  
    }


    // Get AI Workout Details With Its Exercises
    // -----------------------------------------
    public function getAiWorkoutFullDetails($ai_workout_id)
    {
        $workout = AIWorkout::with(['exercises.exercise'])
            ->where('ai_workout_id', $ai_workout_id)
            ->first();

        if (!$workout) {
            return response()->json([
                'status' => false,
                'message' => 'Workout not found',
                'data' => null
            ]);
        }

        // Format response
        $response = [
            'ai_workout_id' => $workout->ai_workout_id,
            'workout_name' => $workout->workout_name,
            'workout_goal' => $workout->workout_goal,
            'workout_focus_area' => $workout->workout_focus_area,
            'workout_duration' => $workout->workout_duration,
            'workout_difficulty' => $workout->workout_difficulty,
            'body_type' => $workout->body_type,
            'created_at' => $workout->created_at,

            // Exercises
            'exercises' => $workout->exercises->sortBy('exercise_order')->map(function ($item) {
                return [
                    'ai_workout_exercise_id' => $item->ai_workout_exercise_id,
                    'exercise_id' => $item->exercise_id,
                    'exercise_name' => $item->exercise_name,
                    'exercise_sets' => $item->exercise_sets,
                    'exercise_reps' => $item->exercise_reps,
                    'exercise_duration_sec' => $item->exercise_duration_sec,
                    'exercise_order' => $item->exercise_order,
                    'exercise_xp' => $item->exercise_xp,

                    // From Exercise table
                    'exercise_description' => $item->exercise->exercise_description ?? null,
                    'exercise_calories_burn' => $item->exercise->exercise_calories_burn ?? null,
                    'exercise_gif_full_url' => $item->exercise->exercise_gif_full_url ?? null,
                ];
            })->values()
        ];

        return response()->json([
            'status' => true,
            'message' => 'Workout full details fetched successfully',
            'data' => $response
        ]);
    }


    // Get AI Geneated Exercises By AI Workout Id
    // ------------------------------------------ 
    public function getWorkoutExercises($ai_workout_id)
    {
        $workout = AIWorkout::find($ai_workout_id);

        if (!$workout) {
            return response()->json([
                'status' => false,
                'message' => 'Workout not found',
                'data' => []
            ]);
        }

        // Eager load exercise relationship
        $exercises = $workout->exercises()->with('exercise')->orderBy('exercise_order', 'asc')->get();

        // Map exercises to include extra fields from Exercise table
        $exercises = $exercises->map(function($item) {
            return [
                'ai_workout_exercise_id' => $item->ai_workout_exercise_id,
                'ai_workout_id' => $item->ai_workout_id,
                'exercise_id' => $item->exercise_id,
                'exercise_name' => $item->exercise_name,
                'exercise_sets' => $item->exercise_sets,
                'exercise_reps' => $item->exercise_reps,
                'exercise_duration_sec' => $item->exercise_duration_sec,
                'exercise_order' => $item->exercise_order,
                'exercise_xp' => $item->exercise_xp,
                // Extra fields from exercise table
                'exercise_description' => $item->exercise->exercise_description ?? null,
                'exercise_calories_burn' => $item->exercise->exercise_calories_burn ?? null,
                'exercise_gif_full_url' => $item->exercise->exercise_gif_full_url ?? null,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Exercises fetched successfully',
            'data' => $exercises
        ]);
    }

}
