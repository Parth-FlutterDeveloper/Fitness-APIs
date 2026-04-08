<?php

namespace App\Http\Controllers\Api\User\UserReport;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\GeminiService;


class UserReportController extends Controller
{

    // ---- User Weekly Report ----
    // ----------------------------
    public function weeklyReport(Request $request)
    {
        $userId = auth()->id();

        // ---------------- DATE RANGES ----------------
        $currentStart = now()->subDays(7);
        $previousStart = now()->subDays(14);
        $previousEnd = now()->subDays(7);

        // ---------------- FETCH DATA ----------------
        $current = DB::table('user_progress')
            ->where('user_id', $userId)
            ->whereBetween('workout_date', [$currentStart, now()])
            ->get();

        $previous = DB::table('user_progress')
            ->where('user_id', $userId)
            ->whereBetween('workout_date', [$previousStart, $previousEnd])
            ->get();

        // ---------------- CURRENT WEEK ----------------
        $currentCalories = $current->sum('calories_burned');
        $currentDuration = $current->sum('exercise_duration_sec');
        $currentXP = $current->sum('xp_earned');

        $currentWorkouts = $current->where('is_completed', 1)
            ->pluck('workout_date')
            ->unique()
            ->count();

        // ---------------- PREVIOUS WEEK ----------------
        $previousCalories = $previous->sum('calories_burned');
        $previousDuration = $previous->sum('exercise_duration_sec');
        $previousXP = $previous->sum('xp_earned');

        $previousWorkouts = $previous->where('is_completed', 1)
            ->pluck('workout_date')
            ->unique()
            ->count();

        // ---------------- CONSISTENCY ----------------
        $consistency = ($currentWorkouts / 7) * 100;
        $previousConsistency = ($previousWorkouts / 7) * 100;

        // ---------------- FITNESS SCORE ----------------
        $fitnessScore =
            ($currentCalories * 0.4) +
            ($consistency * 10 * 0.3) +
            ($currentDuration * 0.2 / 60) +
            ($currentXP * 0.1);

        $previousFitnessScore =
            ($previousCalories * 0.4) +
            ($previousConsistency * 10 * 0.3) +
            ($previousDuration * 0.2 / 60) +
            ($previousXP * 0.1);

        // ---------------- LEVEL ----------------
        if ($fitnessScore < 500) {
            $level = "Beginner";
        } elseif ($fitnessScore < 1200) {
            $level = "Intermediate";
        } else {
            $level = "Advanced";
        }

        // ---------------- CHANGES ----------------
        $scoreChange = $fitnessScore - $previousFitnessScore;
        $calorieChange = $currentCalories - $previousCalories;
        $durationChange = $currentDuration - $previousDuration;
        $xpChange = $currentXP - $previousXP;

        // ---------------- MESSAGE ----------------
        if ($scoreChange > 0) {
            $scoreMessage = "Great job! Your fitness improved this week 🚀";
        } elseif ($scoreChange < 0) {
            $scoreMessage = "You need to improve consistency ⚠️";
        } else {
            $scoreMessage = "No change this week. Stay consistent 💪";
        }

        // ---------------- FAT LOSS ESTIMATION ----------------
        $estimatedFatLoss = $currentCalories / 7700;

        // ---------------- EXPLANATION ----------------
        $scoreExplanation = "Fitness score is calculated based on calories burned, workout consistency, duration, and activity level.";

        return response()->json([
            "current_week" => [
                "calories" => round($currentCalories, 2),
                "duration_sec" => $currentDuration,
                "xp" => $currentXP,
                "workouts" => $currentWorkouts,
                "consistency" => round($consistency, 2),
                "fitness_score" => round($fitnessScore, 2),
                "fitness_level" => $level,
                "estimated_fat_loss_kg" => round($estimatedFatLoss, 3)
            ],

            "previous_week" => [
                "calories" => round($previousCalories, 2),
                "duration_sec" => $previousDuration,
                "xp" => $previousXP,
                "workouts" => $previousWorkouts,
                "consistency" => round($previousConsistency, 2),
                "fitness_score" => round($previousFitnessScore, 2),
                "fitness_level" => (
                    $previousFitnessScore < 500 ? "Beginner" :
                    ($previousFitnessScore < 1200 ? "Intermediate" : "Advanced")
                )
            ],

            "comparison" => [
                "score_change" => round($scoreChange, 2),
                "calorie_change" => round($calorieChange, 2),
                "duration_change_sec" => $durationChange,
                "xp_change" => $xpChange,
                "workout_change" => $currentWorkouts - $previousWorkouts,
                "consistency_change" => round($consistency - $previousConsistency, 2),
                "message" => $scoreMessage
            ],

            "info" => [
                "fitness_score_meaning" => $scoreExplanation
            ]
        ]);
    }

    // ---- GRAPH API ----
    // -------------------
    public function weeklyGraph()
    {
        $userId = auth()->id();

        $start = now()->subDays(7);

        $data = DB::table('user_progress')
            ->select('workout_date', DB::raw('SUM(calories_burned) as calories'))
            ->where('user_id', $userId)
            ->whereBetween('workout_date', [$start, now()])
            ->groupBy('workout_date')
            ->orderBy('workout_date')
            ->get();

        return response()->json($data);
    }

    // ---- AI REPORT ----
    // -------------------
    public function aiReport()
    {
        // Get weekly report
        $response = $this->weeklyReport(new Request());
        $reportData = $response->getData(true); 

        // Create prompt
        $prompt = "
        User Weekly Fitness Summary:
        Fitness Score: {$reportData['current_week']['fitness_score']}
        Level: {$reportData['current_week']['fitness_level']}
        Workouts: {$reportData['current_week']['workouts']}
        Consistency: {$reportData['current_week']['consistency']}%
        Estimated Fat Loss: {$reportData['current_week']['estimated_fat_loss_kg']} kg

        Give short, motivational fitness feedback.
        ";

        // Call Gemini
        $aiResponse = app(GeminiService::class)
            ->generateWorkout($prompt);

        // ✅ Extract ONLY text
        $aiText = data_get($aiResponse, 'candidates.0.content.parts.0.text', 'No feedback available.');

        // Final response
        return response()->json([
            "ai_feedback" => $aiText
        ]);
    }

}
