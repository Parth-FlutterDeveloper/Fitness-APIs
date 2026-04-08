<?php

namespace App\Http\Controllers\Api\User\Goal;

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

}
