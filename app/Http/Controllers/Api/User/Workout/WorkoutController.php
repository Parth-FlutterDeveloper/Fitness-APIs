<?php

namespace App\Http\Controllers\Api\User\Workout;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Workout;

class WorkoutController extends Controller
{
    
    // -----------------------
    // Quick Workouts
    // -----------------------
    public function getQuickWorkouts()
    {
        $workouts = Workout::with(['focusArea', 'goals'])
                    ->orderBy('workout_duration_minute', 'asc') // Small duration first
                    ->take(5) // Top 5
                    ->get();

        if ($workouts->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No workouts found',
                'data'    => []
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Quick workouts fetched successfully',
            'total'   => $workouts->count(),
            'data'    => $workouts
        ], 200);
    }


    // -----------------------
    // Get Workouts By Focus Area ID
    // -----------------------
    public function getWorkoutsByFocusArea($focus_area_id)
    {
        $workouts = Workout::with(['focusArea', 'goals'])
                    ->where('workout_focus_area_id', $focus_area_id)
                    ->orderByDesc('workout_id')
                    ->take(6)
                    ->get();

        if ($workouts->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No workouts found for this focus area',
                'data'    => []
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Workouts fetched successfully',
            'total'   => $workouts->count(),
            'data'    => $workouts
        ], 200);
    }


    // -----------------------
    // Get All Workouts
    // -----------------------
    public function getAllWorkouts()
    {
        $workouts = Workout::with(['focusArea', 'goals'])
                    ->orderByDesc('workout_id')
                    ->get();

        if ($workouts->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No workouts found',
                'data'    => []
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Workouts fetched successfully',
            'total'   => $workouts->count(),
            'data'    => $workouts
        ], 200);
    }

    // -----------------------
    // Get All Exercise By Workout ID
    // -----------------------
    public function getWorkoutExercises($workout_id)
    {
        $workout = Workout::with([
                        'focusArea',
                        'goals',
                        'exercises'
                    ])
                    ->where('workout_id', $workout_id)
                    ->first();

        if (!$workout) {
            return response()->json([
                'success' => false,
                'message' => 'Workout not found',
                'data'    => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Workout exercises fetched successfully',
            'total_exercises' => $workout->exercises->count(),
            'data' => $workout
        ], 200);
    }

}
