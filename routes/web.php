<?php

use App\Http\Controllers\Web\DisasterAlertController;
use App\Http\Controllers\Web\DisasterReportController;
use App\Http\Controllers\Web\EmergencyContactController;
use App\Http\Controllers\Web\EvacuationPostController;
use App\Http\Controllers\Web\LoginController;
use App\Http\Controllers\Web\MyDisasterReportsController;
use App\Http\Controllers\Web\NewsEducationController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\RegistrationController;
use App\Http\Controllers\Web\RiskMapController;
use App\Http\Controllers\Web\SosController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('landing');

Route::view('/bantuan', 'public-help')->name('help.index');
Route::view('/tentang', 'public-about')->name('about.index');

Route::view('/home', 'home')
    ->middleware('auth')
    ->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])
        ->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])
        ->whereNumber('notification')
        ->name('notifications.read');

    Route::get('/berita-edukasi', [NewsEducationController::class, 'index'])
        ->name('news-education.index');
    Route::get('/berita-edukasi/{source}/{item}', [NewsEducationController::class, 'show'])
        ->whereIn('source', ['bnpb', 'bmkg', 'local'])
        ->where('item', '[A-Za-z0-9._-]+')
        ->name('news-education.show');

    Route::get('/peringatan-bencana', [DisasterAlertController::class, 'index'])
        ->name('disaster-alerts.index');
    Route::get('/peringatan-bencana/berita/{slug}', [DisasterAlertController::class, 'showNews'])
        ->where('slug', '[A-Za-z0-9._-]+')
        ->name('disaster-alerts.news.show');

    Route::get('/sos', [SosController::class, 'create'])->name('sos.create');
    Route::post('/sos/confirm', [SosController::class, 'confirmSelection'])
        ->name('sos.confirm-selection');
    Route::get('/sos/confirm', [SosController::class, 'confirm'])
        ->name('sos.confirm');
    Route::post('/sos', [SosController::class, 'store'])->name('sos.store');
    Route::get('/sos/{sosRequestId}/sent', [SosController::class, 'sent'])
        ->whereNumber('sosRequestId')
        ->name('sos.sent');
    Route::get('/sos/{sosRequestId}/status', [SosController::class, 'status'])
        ->whereNumber('sosRequestId')
        ->name('sos.status');

    Route::get('/tempat-evakuasi', [EvacuationPostController::class, 'index'])
        ->name('evacuation-posts.index');
    Route::get('/tempat-evakuasi/{evacuationPost}', [EvacuationPostController::class, 'show'])
        ->whereNumber('evacuationPost')
        ->name('evacuation-posts.show');

    Route::get('/kontak-darurat', [EmergencyContactController::class, 'index'])
        ->name('emergency-contacts.index');

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
