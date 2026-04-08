<?php

namespace App\Http\Controllers\Api\User\FocusArea;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FocusArea;

class FocusareaController extends Controller
{

    // -----------------------
    // Get All Focus Areas
    // -----------------------
    public function getAllFocusAreas()
    {
        $focusAreas = FocusArea::orderByDesc('focus_areas_id')->get();

        if ($focusAreas->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No focus areas found',
                'data'    => []
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Focus areas fetched successfully',
            'total'   => $focusAreas->count(),
            'data'    => $focusAreas
        ], 200);
    }

}
