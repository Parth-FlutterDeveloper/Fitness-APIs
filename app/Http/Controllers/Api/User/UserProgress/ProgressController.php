<?php

namespace App\Http\Controllers\Api\User\UserProgress;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserProgress;
use App\Models\WorkoutExercise;
use App\Models\Workout;
use App\Models\AIWorkout;
use App\Models\AIWorkoutExercise;

use Carbon\Carbon;

class ProgressController extends Controller
{
    
    // Progress Summary 
    // ----------------
    public function progressSummary(Request $request)
    {
        $userId = auth()->id(); // or $request->user_id

        // Total Calories
        $totalCalories = UserProgress::where('user_id', $userId)
            ->sum('calories_burned');

        // Weekly Calories
        $weeklyCalories = UserProgress::where('user_id', $userId)
            ->where('workout_date', '>=', now()->subDays(7))
            ->sum('calories_burned');

        // Total XP
        $totalXP = UserProgress::where('user_id', $userId)
            ->sum('xp_earned');

        // Total Workouts (count unique sessions)
        $totalWorkouts = UserProgress::where('user_id', $userId)
            ->distinct('session_id')
            ->count('session_id');

        // Workout Dates for Streak
        $dates = UserProgress::where('user_id', $userId)
            ->select('workout_date')
            ->distinct()
            ->orderBy('workout_date', 'desc')
            ->pluck('workout_date')
            ->toArray();

        $streak = 0;
        $currentDate = now()->toDateString();

        foreach ($dates as $date) {

            if ($date == $currentDate || $date == now()->subDays($streak)->toDateString()) {
                $streak++;
            } else {
                break;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_calories' => $totalCalories,
                'weekly_calories' => $weeklyCalories,
                'total_xp' => $totalXP,
                'total_workouts' => $totalWorkouts,
                'current_streak' => $streak
            ]
        ]);
    }


    // Workout History
    // ---------------

    // public function workoutHistory(Request $request)
    // {
    //     $userId = auth()->id();

    //     $history = UserProgress::where('user_id', $userId)
    //         ->selectRaw('
    //             session_id,
    //             workout_id,
    //             ai_workout_id,
    //             MAX(workout_date) as workout_date,
    //             MIN(workout_time) as workout_time,
    //             SUM(calories_burned) as total_calories,
    //             SUM(xp_earned) as total_xp,
    //             COUNT(exercise_id) as total_exercises
    //         ')
    //         ->groupBy('session_id', 'workout_id', 'ai_workout_id')
    //         ->orderByDesc('workout_date')
    //         ->get();

    //     return response()->json([
    //         'success' => true,
    //         'data' => $history
    //     ]);
    // }

    public function workoutHistory(Request $request)
    {
        $userId = auth()->id();

        $history = UserProgress::where('user_id', $userId)
            ->selectRaw('
                session_id,
                workout_id,
                ai_workout_id,
                MAX(workout_date) as workout_date,
                MIN(workout_time) as workout_time,
                SUM(calories_burned) as total_calories,
                SUM(xp_earned) as total_xp,
                COUNT(exercise_id) as total_exercises
            ')
            ->groupBy('session_id', 'workout_id', 'ai_workout_id')
            ->orderByDesc('workout_date')
            ->get();

        $history = $history->map(function ($item) {

            // ✅ Normal Workout (NO exercises)
            if ($item->workout_id) {
                $workout = Workout::find($item->workout_id);

                $item->workout_details = $workout ? [
                    'workout_id' => $workout->workout_id,
                    'workout_name' => $workout->workout_name,
                    'workout_difficulty' => $workout->workout_difficulty,
                    'workout_image_url' => $workout->workout_image ? url('storage/' . $workout->workout_image) : null,
                ] : null;
            } else {
                $item->workout_details = null;
            }

            // ✅ AI Workout (ONLY basic details, NO exercises)
            if ($item->ai_workout_id) {
                $aiWorkout = AIWorkout::find($item->ai_workout_id);

                $item->ai_workout_details = $aiWorkout ? [
                    'ai_workout_id' => $aiWorkout->ai_workout_id,
                    'workout_name' => $aiWorkout->workout_name,
                    'workout_goal' => $aiWorkout->workout_goal,
                    'workout_focus_area' => $aiWorkout->workout_focus_area,
                    'workout_duration' => $aiWorkout->workout_duration,
                    'workout_difficulty' => $aiWorkout->workout_difficulty,
                    'body_type' => $aiWorkout->body_type,
                    'created_at' => $aiWorkout->created_at,
                ] : null;
            } else {
                $item->ai_workout_details = null;
            }

            return $item;
        });

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }


    // Weekly Workout Status
    // ---------------------
    public function weeklyWorkoutStatus(Request $request)
    {
        $userId = auth()->id();

        $startOfWeek = now()->startOfWeek();   // Monday
        $endOfWeek = now()->endOfWeek();       // Sunday

        // Get workout dates in this week
        $workoutDates = UserProgress::where('user_id', $userId)
            ->whereBetween('workout_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->select('workout_date')
            ->distinct()
            ->pluck('workout_date')
            ->toArray();

        $weekData = [];

        $currentDate = $startOfWeek->copy();

        while ($currentDate <= $endOfWeek) {

            $date = $currentDate->toDateString();

            $weekData[] = [
                'date' => $date,
                'day' => $currentDate->format('D'),
                'completed' => in_array($date, $workoutDates)
            ];

            $currentDate->addDay();
        }

        return response()->json([
            'success' => true,
            'data' => $weekData
        ]);
    }


    // User Workout Session :- Daily Workout History
    // ---------------------------------------------
    // Shows all workouts user started
    public function todayWorkouts(Request $request)
    {
        $userId = auth()->id();

        // ✅ Step 1: Group ONLY by session_id
        $sessions = UserProgress::where('user_id', $userId)
            ->whereDate('workout_date', Carbon::today())
            ->selectRaw('
                session_id,
                MAX(workout_id) as workout_id,
                MAX(ai_workout_id) as ai_workout_id,
                MAX(workout_date) as workout_date,
                COUNT(*) as total_logged,
                SUM(is_completed) as completed_exercises
            ')
            ->groupBy('session_id')
            ->orderByDesc('workout_date')
            ->get();

        // ✅ Step 2: Map data
        $data = $sessions->map(function ($session) {

            $workoutName = null;
            $image = null;
            $totalExercises = 0;

            // ✅ NORMAL WORKOUT
            if (!is_null($session->workout_id)) {

                $workout = Workout::find($session->workout_id);

                $workoutName = $workout->workout_name ?? null;

                $image = ($workout && $workout->workout_image)
                    ? url('storage/' . $workout->workout_image)
                    : null;

                $totalExercises = WorkoutExercise::where(
                    'workout_id',
                    $session->workout_id
                )->count();
            }

            // ✅ AI WORKOUT
            elseif (!is_null($session->ai_workout_id)) {

                $aiWorkout = AIWorkout::find($session->ai_workout_id);

                $workoutName = $aiWorkout->workout_name ?? null;

                $image = null; // as per requirement

                $totalExercises = AIWorkoutExercise::where(
                    'ai_workout_id',
                    $session->ai_workout_id
                )->count();
            }

            // ✅ STATUS FIX
            $completed = (int) $session->completed_exercises;

            $status = ($totalExercises > 0 && $completed == $totalExercises)
                ? 'completed'
                : 'incomplete';

            return [
                'session_id' => $session->session_id,

                // ✅ only one will exist
                'workout_id' => $session->workout_id ? (int)$session->workout_id : null,
                'ai_workout_id' => $session->ai_workout_id ? (int)$session->ai_workout_id : null,

                'workout_name' => $workoutName,
                'workout_image_url' => $image,

                'workout_date' => $session->workout_date,

                'total_exercises' => $totalExercises,
                'completed_exercises' => $completed,

                'status' => $status,
                'resume_available' => $status === 'incomplete'
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }


}
