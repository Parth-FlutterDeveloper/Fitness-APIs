<?php

namespace App\Http\Controllers\Api\User\Exercise;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Exercise;

class ExerciseController extends Controller
{
        
    // -----------------------
    // Get Exercise Detail By ID
    // -----------------------
    public function getExerciseDetail($exercise_id)
    {

        // $exercise = Exercise::where('exercise_id', $exercise_id)->first();
       
        $exercise = Exercise::with('injuries:injury_id,exercise_id')
                ->where('exercise_id', $exercise_id)
                ->first();

        if (!$exercise) {
            return response()->json([
                'success' => false,
                'message' => 'Exercise not found',
                'data'    => null
            ], 404);
        }

        // Get only one injury_id
        $exercise->injury_id = optional($exercise->injuries->first())->injury_id;

        // Remove injuries array
        unset($exercise->injuries);

        return response()->json([
            'success' => true,
            'message' => 'Exercise details fetched successfully',
            'data'    => $exercise
        ], 200);
    }

    
}
