<?php

use App\Http\Controllers\Web\LoginController;
use App\Http\Controllers\Web\MyDisasterReportsController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\RiskMapController;
use App\Http\Controllers\Web\RegistrationController;
use App\Http\Controllers\Web\DisasterReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('landing');

Route::view('/home', 'home')
    ->middleware('auth')
    ->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/peta-risiko', [RiskMapController::class, 'index'])
        ->name('risk-map.index');

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::get('/reports', [MyDisasterReportsController::class, 'index'])
        ->name('reports.index');

    Route::get('/reports/create', [DisasterReportController::class, 'create'])
        ->name('reports.create');

    Route::get('/reports/{reportId}', [MyDisasterReportsController::class, 'show'])
        ->whereNumber('reportId')
        ->name('reports.show');

    Route::post('/reports', [DisasterReportController::class, 'store'])
        ->name('reports.store');
});

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
