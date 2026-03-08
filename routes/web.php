<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Authentication Routes
Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('welcome');
})->name('login');

Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login']);
Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

// Catch-all route for Vue
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
