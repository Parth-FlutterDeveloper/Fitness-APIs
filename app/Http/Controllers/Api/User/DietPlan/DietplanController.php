<?php

namespace App\Http\Controllers\Api\User\DietPlan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DietPlans;

class DietplanController extends Controller
{

    // -----------------------
    // Get All Diet Plans
    // -----------------------
    public function getAllDietPlans()
    {
        $dietPlans = DietPlans::orderByDesc('diet_plan_id')->get();

        return response()->json([
            'success' => true,
            'message' => 'Diet plans fetched successfully',
            'total'   => $dietPlans->count(),
            'data'    => $dietPlans
        ]);
    }


}
