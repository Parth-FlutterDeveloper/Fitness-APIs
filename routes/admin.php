<?php

// Common URL : http://127.0.0.1:8000/api

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\Auth\AuthController;
use App\Http\Controllers\Api\Admin\User\UserController;
use App\Http\Controllers\Api\Admin\Workout\WorkoutController;
use App\Http\Controllers\Api\Admin\Exercise\ExerciseController;
use App\Http\Controllers\Api\Admin\Goal\GoalController;
use App\Http\Controllers\Api\Admin\FocusArea\FocusAreaController;
use App\Http\Controllers\Api\Admin\DietPlan\DietController;
use App\Http\Controllers\Api\Admin\DietMeal\MealController;
use App\Http\Controllers\Api\Admin\Injury\InjuryController;
use App\Http\Controllers\Api\Admin\AdminCon\AdminController;
use App\Http\Controllers\Api\Admin\Feedback\FeedbackController;
use App\Http\Controllers\Api\Admin\LeaderBoard\LeaderboardController;
use App\Http\Controllers\Api\Admin\DashBoard\AdminDashboardController;

// ------- Admin -------

// Login
Route::post('/admin/login', [AuthController::class, 'login']);  

// Forgot Password (NO AUTH)
Route::post('/admin/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/admin/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/admin/reset-password', [AuthController::class, 'resetPassword']);

// Admin Protected APIs
Route::middleware('auth:sanctum')->group(function () {


    // ----- User -----

    // Get All Users (ADMIN ONLY)
    Route::get('/admin/users', [UserController::class, 'getAllUsers']);
    // Delete user by id
    Route::delete('/admin/users/{id}', [UserController::class, 'deleteUser']);


    // ----- Feedback -----
    
    // Get All Feedback
    Route::get('/admin/feedback', [FeedbackController::class, 'getAllFeedback']);
    // Reply Feedback
    Route::post('/admin/feedback/reply/{id}', [FeedbackController::class, 'replyFeedback']);


    // ----- LeaderBoard -----

    // Get All Time Leader Board Data
    Route::get('/leaderboard', [LeaderboardController::class, 'allTimeLeaderboard']);


    // ----- Admin Dash Board -----

    // Dashboard Count
    Route::get('/dashboard/counts', [AdminDashboardController::class, 'dashboardCounts']);
    // MONTHLY USERS (LINE CHART)
    Route::get('/dashboard/monthlyUsers', [AdminDashboardController::class, 'monthlyUsers']);
    // CITY-WISE USERS (BAR CHART)
    Route::get('/dashboard/cityUsers', [AdminDashboardController::class, 'cityWiseUsers']);


    // ----- Admin -----

    // Get Admin By ID
    Route::get('/admin/{id}', [AdminController::class, 'getAdminById']);
    // Admin Change Password
    Route::post('/admin/changePassword', [AdminController::class, 'changePassword']);
    // Update Admin Details
    Route::post('/admin/updateProfile', [AdminController::class, 'updateProfile']);


    // ----- Workout -----

    // Get all Workout with Filter using Goal
    Route::get('/workouts', [WorkoutController::class, 'workouts']);
    // Get Single Workout
    Route::get('/workouts/{id}', [WorkoutController::class, 'getWorkout']);
    // Get Workout Exercises
    Route::get('/workouts/{id}/exercises', [WorkoutController::class, 'workoutExercises']);
    // Add Workout
    Route::post('/addWorkout', [WorkoutController::class, 'addWorkout']);
    
    // Update Workout + Goal
    Route::post('/updateWorkout/{workout_id}', [WorkoutController::class, 'updateWorkout']);
    // Add Excercise in Workout
    Route::post('/addWorkoutExercise/{workout_id}', [WorkoutController::class, 'addExerciseToWorkout']);
    // Remove Exercise from Workout
    Route::post('/removeWorkoutExercise/{workout_id}', [WorkoutController::class, 'removeExerciseFromWorkout']);
    // Change Order of Exercise in Workout
    Route::post('/changeExerciseOrder/{workout_id}', [WorkoutController::class, 'reorderWorkoutExercises']);

    // Delete Workout
    Route::delete('/deleteWorkout/{id}', [WorkoutController::class, 'deleteWorkout']);


    // ----- Focus Area -----

    // Get All Focus Area
    Route::get('/focusAreas', [FocusAreaController::class, 'focusAreas']);
    // Insert Focus Area
    Route::post('/insert/focusArea', [FocusAreaController::class, 'insertFocusArea']);
    // Update Focus Area
    Route::post('/update/focusArea/{id}', [FocusAreaController::class, 'updateFocusArea']);
    // Delete Focus Area
    Route::delete('/delete/focusArea/{id}', [FocusAreaController::class, 'deleteFocusArea']);


    // ----- Goal -----
    
    // Get All Goals
    Route::get('/goals', [GoalController::class, 'goals']);
    // Insert Goal
    Route::post('/insertGoal', [GoalController::class, 'insertGoal']);
    // Update Goal
    Route::put('/updateGoal/{goal_id}', [GoalController::class, 'updateGoal']);
    // Delete Goal
    Route::delete('/deleteGoal/{goal_id}', [GoalController::class, 'deleteGoal']);


    // ----- Exercise -----

    // Get all exercises
    Route::get('/exercises', [ExerciseController::class, 'exercises']);
    // Get exercise by ID
    Route::get('/exercises/{id}', [ExerciseController::class, 'getExercise']);
    // Add new Exercise
    Route::post('/addExercise', [ExerciseController::class, 'addExercise']);
    // Update Exercise
    Route::post('/updateExercise/{id}', [ExerciseController::class, 'updateExercise']);
    // Delete Exercise
    Route::delete('/deleteExercise/{id}', [ExerciseController::class, 'deleteExercise']);


    // ----- Diet Plans -----

    // Get All Diet Plans
    Route::get('/dietplans', [DietController::class, 'dietplans']);
    // Insert Diet Plan
    Route::post('/insert/dietplan', [DietController::class, 'insertDietPlan']);
    // Update Diet Plan
    Route::post('/updateDietPlan/{id}', [DietController::class, 'updateDietPlan']);
    // Delete Diet Plan
    Route::delete('/deleteDietPlan/{id}', [DietController::class, 'deleteDietPlan']);


    // ----- Diet Meals -----

    // Get All Meals
    Route::get('/meals', [MealController::class, 'getAllMeals']);
    // Insert Meal
    Route::post('/insert/meal', [MealController::class, 'insertMeal']);
    // Update Meal
    Route::post('/update/meal/{id}', [MealController::class, 'updateMeal']);
    // Delete Meal
    Route::delete('/delete/meal/{id}', [MealController::class, 'deleteMeal']);


    // ----- Injury -----

    // Get All Injuries
    Route::get('/injuries', [InjuryController::class, 'getInjuries']);
    // Get Injury by ID
    Route::get('/injuries/{id}', [InjuryController::class, 'getInjuryById']);
    // Insert Injury
    Route::post('/insert/injury', [InjuryController::class, 'insertInjury']);
    // Update Injury
    Route::post('/update/injury/{id}', [InjuryController::class, 'updateInjury']);
    // Delete Injury
    Route::delete('/delete/injury/{id}', [InjuryController::class, 'deleteInjury']);


});