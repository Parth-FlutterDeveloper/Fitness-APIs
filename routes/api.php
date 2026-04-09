<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/admin.php';
require __DIR__.'/user.php';

Route::get('/test', function () {
    return response()->json([
        'status' => true,
        'message' => 'API is working 🚀'
    ]);
}); 

Route::get('/db-check', function () {
    try {
        DB::connection()->getPdo();

        return response()->json([
            'status' => true,
            'message' => 'Database connected successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
});