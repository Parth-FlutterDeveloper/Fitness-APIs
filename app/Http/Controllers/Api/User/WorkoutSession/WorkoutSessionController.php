<?php

namespace App\Http\Controllers\Api\User\WorkoutSession;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WorkoutExercise;
use App\Models\Exercise;
use App\Models\UserProgress;
use App\Models\AIWorkout;
use App\Models\AIWorkoutExercise;

class WorkoutSessionController extends Controller
{
    

    // Start Wrokout
    // -------------

    // Creates a session_id for the workout.
    // public function startWorkout(Request $request)
    // {
    //     $request->validate([
    //         'workout_id' => 'required|exists:workout,workout_id'
    //     ]);

    //     $sessionId = uniqid('session_');

    //     return response()->json([
    //         'success' => true,
    //         'data' => [
    //             'session_id' => $sessionId,
    //             'workout_id' => $request->workout_id
    //         ]
    //     ]);
    // }  

    public function startWorkout(Request $request)
    {
        $request->validate([
            'workout_id' => 'nullable|exists:workout,workout_id',
            'ai_workout_id' => 'nullable|exists:ai_workouts,ai_workout_id'
        ]);

        if (!$request->workout_id && !$request->ai_workout_id) {
            return response()->json([
                'success' => false,
                'message' => 'Workout ID or AI Workout ID required'
            ]);
        }

        $sessionId = uniqid('session_');

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $sessionId,
                'workout_id' => $request->workout_id,
                'ai_workout_id' => $request->ai_workout_id
            ]
        ]);
    }


    // Get Workout Exercise
    // --------------------
    
    // Returns exercises in correct order.
    // public function workoutExercises($workout_id)
    // {
    //     $exercises = WorkoutExercise::where('workout_id',$workout_id)
    //         ->with('exercise')
    //         ->orderBy('exercise_order')
    //         ->get();

    //     return response()->json([
    //         'success'=>true,
    //         'data'=>$exercises
    //     ]);
    // }

    public function getExercises(Request $request)
    {
        if ($request->workout_id) {

            $exercises = WorkoutExercise::where('workout_id', $request->workout_id)
                ->with('exercise')
                ->orderBy('exercise_order')
                ->get();

        } else {

            // Load exercise relationship
            $exercises = AIWorkoutExercise::where('ai_workout_id', $request->ai_workout_id)
                ->with('exercise') // ✅ THIS IS IMPORTANT
                ->orderBy('exercise_order', 'asc')
                ->get();

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

        }

        return response()->json([
            'success' => true,
            'data' => $exercises
        ]);
    }


    // Update Exercise Progress
    // ------------------------
    
    // This API handles Done OR Skip
    public function updateExerciseProgress(Request $request)
    {
        $userId = auth()->id();

        $exercise = Exercise::find($request->exercise_id);

        UserProgress::updateOrCreate(

            [
                'session_id'=>$request->session_id,
                'exercise_id'=>$request->exercise_id,
                'exercise_order'=>$request->exercise_order
            ],

            [
                'user_id'=>$userId,
                'workout_id'=>$request->workout_id,
                'ai_workout_id' => $request->ai_workout_id, // New Added
                'workout_date'=>now()->toDateString(),
                'workout_time'=>now()->toTimeString(),
                'sets_completed'=>$request->sets_completed ?? 0,
                'reps_completed'=>$request->reps_completed ?? 0,
                'exercise_duration_sec'=>$request->exercise_duration_sec ?? 0,
                'calories_burned'=>$exercise->exercise_calories_burn ?? 0,
                'xp_earned'=>$exercise->exercise_xp ?? 0,
                'is_completed'=>$request->is_completed
            ]

        );

        return response()->json([
            'success'=>true,
            'message'=>'Progress updated'
        ]);
    }


    // Resume Workout 
    // --------------
    // Returns all completed exercises so the user can resume.
    public function sessionProgress($session_id)
    {
        $progress = UserProgress::where('session_id',$session_id)
            ->orderBy('exercise_order')
            ->get();

        $nextExercise = UserProgress::where('session_id',$session_id)
            ->where('is_completed',0)
            ->orderBy('exercise_order')
            ->first();

        $resumeOrder = $nextExercise ? $nextExercise->exercise_order : null;

        return response()->json([
            'success'=>true,
            'data'=>[
                'resume_from_order'=>$resumeOrder,
                'progress'=>$progress
            ]
        ]);
    }


    // Finish Workout
    // --------------
    public function finishWorkout(Request $request)
    {
        $sessionId = $request->session_id;

        $progress = UserProgress::where('session_id',$sessionId)->get();

        $totalCalories = $progress->sum('calories_burned');
        $totalXP = $progress->sum('xp_earned');
        $totalExercises = $progress->count();

        return response()->json([
            'success'=>true,
            'data'=>[
                'total_calories'=>$totalCalories,
                'total_xp'=>$totalXP,
                'total_exercises'=>$totalExercises
            ]
        ]);
    }


}
