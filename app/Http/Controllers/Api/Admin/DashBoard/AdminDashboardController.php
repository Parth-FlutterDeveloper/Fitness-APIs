<?php

namespace App\Http\Controllers\Api\Admin\DashBoard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Workout;
use App\Models\DietPlans;
use App\Models\Injury;

class AdminDashboardController extends Controller
{

    // Dashboard Count 
    // ---------------
    public function dashboardCounts()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_members' => User::count(),
                'total_workouts' => Workout::count(),
                'total_diet_plans' => DietPlans::count(),
                'total_injuries' => Injury::count(),
            ]
        ]);
    }


    // MONTHLY USERS (LINE CHART)
    // --------------------------
    public function monthlyUsers()
    {
        $users = DB::table('user')
            ->select(
                DB::raw("MONTH(created_at) as month"),
                DB::raw("COUNT(*) as total")
            )
            ->groupBy(DB::raw("MONTH(created_at)"))
            ->orderBy('month')
            ->get();

        // Month labels
        $labels = [];
        $data   = [];

        foreach ($users as $user) {
            $labels[] = date('M', mktime(0, 0, 0, $user->month, 1));
            $data[]   = $user->total;
        }

        return response()->json([
            'success' => true,
            'labels'  => $labels,
            'data'    => $data
        ]);
    }


    // CITY-WISE USERS (BAR CHART)
    // --------------------------
    public function cityWiseUsers()
    {
        $users = DB::table('user')
            ->select(
                'user_city',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('user_city')
            ->orderByDesc('total')
            ->limit(5) // ONLY TOP 5 CITIES
            ->get();

        return response()->json([
            'success' => true,
            'labels'  => $users->pluck('user_city'),
            'data'    => $users->pluck('total')
        ]);
    }


}
