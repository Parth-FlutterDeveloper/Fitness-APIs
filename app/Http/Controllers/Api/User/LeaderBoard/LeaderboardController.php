<?php

namespace App\Http\Controllers\Api\User\LeaderBoard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class LeaderboardController extends Controller
{
    
    // All - Time LeaderBoard
    // ----------------------
    public function allTimeLeaderboard()
    {
        $users = User::orderByDesc('user_xp_points')->get();

        return response()->json([
            'success' => true,
            'user_ranks' => $users
        ]);
    }
    

}
