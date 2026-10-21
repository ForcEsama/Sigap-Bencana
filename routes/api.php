
<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DisasterReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Autentikasi publik
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

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

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Laporan bencana
    Route::get('/reports', [DisasterReportController::class, 'index']);
    Route::post('/reports', [DisasterReportController::class, 'store']);
    Route::get('/reports/{id}', [DisasterReportController::class, 'show']);

});
