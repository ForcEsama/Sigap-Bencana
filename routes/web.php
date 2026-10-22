<?php

use App\Http\Controllers\Web\LoginController;
use App\Http\Controllers\Web\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('landing');

Route::view('/home', 'home')
    ->middleware('auth')
    ->name('home');

Route::get('/register', [RegistrationController::class, 'create'])
    ->name('register');

Route::post('/register', [RegistrationController::class, 'store'])
    ->name('register.store');

Route::get('/login', [LoginController::class, 'create'])
    ->name('login');

Route::post('/login', [LoginController::class, 'store'])
    ->name('login.store');

Route::post('/logout', [LoginController::class, 'destroy'])
    ->name('logout');
