<?php

use App\Tabulator\UserTabulatorTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/users/data', function (UserTabulatorTable $table, Request $request) {
    return $table->toResponse($request);
})->name('users.data');
