<?php

namespace App\Http\Controllers\Api\Admin\FocusArea;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FocusArea;
use Illuminate\Support\Facades\DB;

class FocusAreaController extends Controller
{


    // Get All Focus Areas
    // -------------------
    public function focusAreas()
    {
        $focusAreas = FocusArea::all();

        return response()->json([
            'success' => true,
            'data' => $focusAreas
        ]);
    }


    // Insert New Focus Area
    // ----------------------
    public function insertFocusArea(Request $request)
    {
        $request->validate([
            'focus_areas_name' => 'required|string|max:50|unique:focus_areas,focus_areas_name'
        ]);

        $focusArea = FocusArea::create([
            'focus_areas_name' => $request->focus_areas_name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Focus area created successfully',
            'data' => $focusArea
        ], 201);
    }


    // Update Focus Area
    // -----------------
    public function updateFocusArea(Request $request, $focus_areas_id)
    {
        $request->validate([
            'focus_areas_name' => 'required|string|max:50|unique:focus_areas,focus_areas_name,' . $focus_areas_id . ',focus_areas_id'
        ]);

        $focusArea = FocusArea::find($focus_areas_id);

        if (!$focusArea) {
            return response()->json([
                'success' => false,
                'message' => 'Focus area not found'
            ], 404);
        }

        $focusArea->update([
            'focus_areas_name' => $request->focus_areas_name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Focus area updated successfully',
            'data' => $focusArea
        ], 200);
    }


    // Delete Focus Area
    // -------------------
    public function deleteFocusArea($focus_areas_id)
    {
        $focusArea = FocusArea::find($focus_areas_id);

        if (!$focusArea) {
            return response()->json([
                'success' => false,
                'message' => 'Focus area not found'
            ], 404);
        }

        // Check workout table
        $usedInWorkouts = DB::table('workout')
            ->where('workout_focus_area_id', $focus_areas_id)
            ->exists();

        // Check injuries table
        $usedInInjuries = DB::table('injuries')
            ->where('focus_area_id', $focus_areas_id)
            ->exists();

        // Check entity_focus_areas table
        $usedInEntityFocus = DB::table('entity_focus_areas')
            ->where('focus_area_id', $focus_areas_id)
            ->exists();

        // If used anywhere → block delete
        if ($usedInWorkouts || $usedInInjuries || $usedInEntityFocus) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete focus area. It is already used in workouts, injuries, or entity focus areas.'
            ], 409);
        }

        // Safe delete
        $focusArea->delete();

        return response()->json([
            'success' => true,
            'message' => 'Focus area deleted successfully'
        ], 200);
    }



}
