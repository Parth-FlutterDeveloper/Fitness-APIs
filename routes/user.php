<?php

// Common URL : http://127.0.0.1:8000/api

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\User\Auth\AuthController;
use App\Http\Controllers\Api\User\UserCon\UserController;
use App\Http\Controllers\Api\User\LeaderBoard\LeaderboardController;
use App\Http\Controllers\Api\User\DietPlan\DietplanController;
use App\Http\Controllers\Api\User\DietMeal\DietmealController;
use App\Http\Controllers\Api\User\Workout\WorkoutController;
use App\Http\Controllers\Api\User\FocusArea\FocusareaController;
use App\Http\Controllers\Api\User\Feedback\FeedbackController;
use App\Http\Controllers\Api\User\Exercise\ExerciseController;
use App\Http\Controllers\Api\User\Injury\InjuryController;
use App\Http\Controllers\Api\User\UserProgress\ProgressController;
use App\Http\Controllers\Api\User\WorkoutSession\WorkoutSessionController;
use App\Http\Controllers\Api\AI\AIWorkout\AIWorkoutController;
use App\Http\Controllers\Api\User\AiGenWorkout\AiGenWorkoutController;
use App\Http\Controllers\Api\User\AiGenDiet\AiGenDietController;
use App\Http\Controllers\Api\User\Goal\GoalController;
use App\Http\Controllers\Api\AI\AIDiet\AIDietController;
use App\Http\Controllers\Api\User\UserReport\UserReportController;

// ------- User -------

// Register
Route::post('/user/register', [AuthController::class, 'register']);

// Login
Route::post('/user/login', [AuthController::class, 'login']);

// Forgot Password (NO AUTH)
Route::post('/user/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/user/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/user/reset-password', [AuthController::class, 'resetPassword']);

// -------- Protected User Routes --------
Route::middleware('auth:sanctum')->group(function () {

    // ---- User ----

    // Get logged-in user profile
    Route::get('/user/profile', [UserController::class, 'getProfile']);
    // Update User Profile
    Route::post('/user/updateProfile', [UserController::class, 'updateProfile']);

    // ---- Leader Board ----
    Route::get('/user/leaderBoard', [LeaderboardController::class, 'allTimeLeaderboard']);

    // ---- Diet Plan ----
    Route::get('/user/dietPlans', [DietplanController::class, 'getAllDietPlans']);

    // ---- Diet Meal ----
    Route::get('/user/dietMeals', [DietmealController::class, 'getAllMeals']);
    Route::get('/user/meal/{id}', [DietmealController::class, 'getMealById']);
    Route::get('/user/dietMeals/{id}', [DietmealController::class, 'getMealsByDietId']);
    
    // ---- Workout ----
    Route::get('/user/quickWorkouts', [WorkoutController::class, 'getQuickWorkouts']); 
    Route::get('/user/workouts/focus/{id}', [WorkoutController::class, 'getWorkoutsByFocusArea']);
    Route::get('/user/workouts', [WorkoutController::class, 'getAllWorkouts']);
    Route::get('/user/workoutExe/{id}', [WorkoutController::class, 'getWorkoutExercises']);

    // ---- Workout ----
    Route::get('/user/exercise/{id}', [ExerciseController::class, 'getExerciseDetail']);

    // ---- Focus Area ----
    Route::get('/user/focusAreas', [FocusAreaController::class, 'getAllFocusAreas']);

    // ---- Injury ----
    Route::get('/user/injuries/{focus_area_id}', [InjuryController::class, 'getInjuriesByFocusArea']);
    Route::get('/user/injury/{id}', [InjuryController::class, 'getInjuryDetail']);
    Route::get('/user/injuries', [InjuryController::class, 'getAllInjuries']);

    // ---- Workout Session ----
    Route::post('/user/startWorkout', [WorkoutSessionController::class, 'startWorkout']);
    Route::get('/user/getExercises', [WorkoutSessionController::class, 'getExercises']);
    Route::post('/user/updateExeProgress', [WorkoutSessionController::class, 'updateExerciseProgress']);
    Route::get('/user/resumeWorkout/{id}', [WorkoutSessionController::class, 'sessionProgress']);
    Route::post('/user/finishWorkout', [WorkoutSessionController::class, 'finishWorkout']);

    // ---- User Progress ----
    Route::get('/user/progress', [ProgressController::class, 'progressSummary']);
    Route::get('/user/history', [ProgressController::class, 'workoutHistory']);
    Route::get('/user/weeklyStatus', [ProgressController::class, 'weeklyWorkoutStatus']);
    Route::get('/user/todayWorkouts', [ProgressController::class, 'todayWorkouts']);

    // ---- Feedback ----
    Route::get('/user/feedback', [FeedbackController::class, 'getUserFeedback']);
    Route::post('/user/insertFeedback', [FeedbackController::class, 'insertFeedback']);

    // ---- Goals ----
    Route::get('/user/goals', [GoalController::class, 'goals']);

    // ---- AI Workout ----
    Route::post('/user/generate/AIWorkout', [AIWorkoutController::class, 'generateAIWorkout']);
    // ----
    Route::get('/user/aiWorkout', [AiGenWorkoutController::class, 'getUserWorkouts']);
    Route::get('/user/getAiWorkoutDetail/{id}', [AiGenWorkoutController::class, 'getAiWorkoutFullDetails']);
    Route::get('/user/aiWorkoutExercises/{id}', [AiGenWorkoutController::class, 'getWorkoutExercises']);

    // ---- AI Diet ----
    Route::post('/user/generate/AIDiet', [AIDietController::class, 'generateDiet']);
    // ----
    Route::get('/user/aiDiet', [AiGenDietController::class, 'getUserDietPlans']);
    Route::get('/user/aiDietPlan/{id}', [AiGenDietController::class, 'getDietPlanById']);
    
    // ---- User Report ----
    Route::get('/user/weeklyReport', [UserReportController::class, 'weeklyReport']);
    Route::get('/user/weeklyGraph', [UserReportController::class, 'weeklyGraph']);
    Route::get('/user/aiReport', [UserReportController::class, 'aiReport']);


}); 