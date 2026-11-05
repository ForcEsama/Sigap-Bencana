
<?php

use App\Http\Controllers\Api\AdminOperationsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DisasterReportController;
use App\Http\Controllers\Api\EarlyWarningController;
use App\Http\Controllers\Api\EmergencyContactController;
use App\Http\Controllers\Api\EvacuationPostController;
use App\Http\Controllers\Api\FieldTaskController;
use App\Http\Controllers\Api\MobileContentController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SosRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('/mobile/news', [MobileContentController::class, 'news'])->name('mobile.news.index');
    Route::get('/mobile/news/{source}/{item}', [MobileContentController::class, 'newsDetail'])
        ->where('source', 'bnpb|bmkg|local')
        ->where('item', '[A-Za-z0-9-]+')
        ->name('mobile.news.show');
    Route::get('/mobile/warnings', [MobileContentController::class, 'warnings'])->name('mobile.warnings.index');
    Route::get('/news', [NewsController::class, 'index'])->name('news.index');
    Route::get('/news/{slug}', [NewsController::class, 'show'])
        ->where('slug', '[A-Za-z0-9-]+')
        ->name('news.show');
    Route::get('/warnings', [EarlyWarningController::class, 'index'])->name('warnings.index');
    Route::get('/warnings/{id}', [EarlyWarningController::class, 'show'])
        ->whereNumber('id')
        ->name('warnings.show');
    Route::get('/emergency-contacts', [EmergencyContactController::class, 'index'])
        ->name('emergency-contacts.index');
    Route::get('/evacuation-posts', [EvacuationPostController::class, 'index'])
        ->name('evacuation-posts.index');
});

// Autentikasi publik
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Route yang membutuhkan autentikasi
Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('officer')->group(function () {
        Route::patch(
            '/reports/{id}/verification',
            [DisasterReportController::class, 'verify']
        );

        Route::patch(
            '/reports/{id}/handling',
            [DisasterReportController::class, 'updateHandlingStatus']
        );
    });
    // Profil pengguna yang sedang login
    Route::get('/user', function (Request $request) {
        return response()->json($request->user());
    });
    Route::patch('/user', [ProfileController::class, 'update']);

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Notifikasi hanya dapat dibaca dan diperbarui oleh penerimanya.
    Route::get('/v1/me/notifications', [NotificationController::class, 'index']);
    Route::patch('/v1/me/notifications/{id}/read', [NotificationController::class, 'markRead'])
        ->whereNumber('id');

    Route::prefix('v1')->name('v1.')->group(function (): void {
        Route::get('/operations/queue', [AdminOperationsController::class, 'queue'])
            ->middleware('admin')
            ->name('operations.queue');
        Route::get('/field/tasks', [FieldTaskController::class, 'index'])
            ->middleware('officer')
            ->name('field.tasks.index');
        Route::patch('/operations/reports/{id}/assignment', [DisasterReportController::class, 'assign'])
            ->middleware('admin')
            ->whereNumber('id')
            ->name('operations.reports.assignment');
        Route::post('/sos', [SosRequestController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('sos.store');
        Route::get('/me/sos', [SosRequestController::class, 'index'])
            ->name('me.sos.index');
        Route::get('/me/sos/{id}', [SosRequestController::class, 'show'])
            ->whereNumber('id')
            ->name('me.sos.show');
        Route::patch('/operations/sos/{id}/status', [SosRequestController::class, 'updateStatus'])
            ->middleware('officer')
            ->whereNumber('id')
            ->name('operations.sos.status');
    });

    // Laporan bencana
    Route::get('/reports', [DisasterReportController::class, 'index']);
    Route::post('/reports', [DisasterReportController::class, 'store']);
    Route::get('/reports/{id}', [DisasterReportController::class, 'show']);

});
