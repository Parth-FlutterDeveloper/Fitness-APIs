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