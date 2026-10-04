<?php

use App\Http\Controllers\ImportController;
use App\Http\Controllers\ImportLogController;
use Illuminate\Support\Facades\Route;

Route::apiResource('imports', ImportController::class)->only(['index', 'store', 'show']);
Route::get('imports/{import}/logs', [ImportLogController::class, 'index'])->name('imports.logs.index');
