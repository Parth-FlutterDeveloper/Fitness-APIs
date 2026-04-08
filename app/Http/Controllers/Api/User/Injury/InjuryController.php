<?php

namespace App\Http\Controllers\Api\User\Injury;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Injury;

class InjuryController extends Controller
{

    // -----------------------------
    // Get injuries by focus area id
    // -----------------------------
    public function getInjuriesByFocusArea($focus_area_id)
    {
        $injuries = Injury::where('focus_area_id', $focus_area_id)
                        ->with('exercise','focusArea')
                        ->get();

        if ($injuries->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No injuries found for this focus area'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'count' => $injuries->count(),
            'data' => $injuries
        ]);
    }

    // -----------------------------
    // Get injury detail by injury id
    // -----------------------------
    public function getInjuryDetail($injury_id)
    {
        $injury = Injury::with(['exercise', 'focusArea'])
                    ->where('injury_id', $injury_id)
                    ->first();

        if (!$injury) {
            return response()->json([
                'success' => false,
                'message' => 'Injury not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $injury
        ]);
    }

    // ----------------
    // Get All Injuries
    // ----------------
    public function getAllInjuries()
    {
        $injuries = Injury::with([
            'exercise:exercise_id,exercise_name,exercise_description,exercise_gif_url',
            'focusArea'
            ])
            ->orderByDesc('injury_id')
            ->get();

        if ($injuries->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No injuries found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'count' => $injuries->count(),
            'data' => $injuries
        ]);
    }

}
