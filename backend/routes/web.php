<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app' => 'ICA-CONTROL API',
        'status' => 'ok',
        'message' => 'Backend operativo',
    ]);
});
