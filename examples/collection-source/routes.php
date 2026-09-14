<?php

use App\Http\Controllers\LogFileController;
use Illuminate\Support\Facades\Route;

Route::get('/logs', [LogFileController::class, 'index'])->name('logs.index');
Route::get('/logs/data', [LogFileController::class, 'data'])->name('logs.data');
