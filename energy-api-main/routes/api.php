<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceAnalysisController;
use App\Http\Controllers\KedsBillController;
use App\Http\Controllers\UserDeviceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Authentication Endpoints (Public)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected Authentication Endpoints
Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
});

// Device Analysis Endpoints (Protected - require authentication)
Route::middleware('auth:sanctum')->prefix('device')->group(function () {
    // Analyze device from image
    Route::post('/analyze', [DeviceAnalysisController::class, 'analyze']);

    // Get analysis by ID
    Route::get('/analysis/{id}', [DeviceAnalysisController::class, 'show']);

    // Get user's analysis history
    Route::get('/history', [DeviceAnalysisController::class, 'history']);
});

// Device Categories (Public - no auth required)
Route::get('/device/categories', [DeviceAnalysisController::class, 'categories']);

// User Saved Devices (Protected - require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Save analyzed device to user's collection
    Route::post('/devices', [UserDeviceController::class, 'store']);

    // Get all user's saved devices
    Route::get('/devices', [UserDeviceController::class, 'index']);

    // Get specific device details
    Route::get('/devices/{id}', [UserDeviceController::class, 'show']);

    // Update device information
    Route::put('/devices/{id}', [UserDeviceController::class, 'update']);

    // Remove device from collection
    Route::delete('/devices/{id}', [UserDeviceController::class, 'destroy']);

    // Get energy insights and statistics
    Route::get('/insights', [UserDeviceController::class, 'insights']);
});

// KEDS Bill Scanner (Protected - require authentication)
Route::middleware('auth:sanctum')->prefix('bills')->group(function () {
    // Upload and analyze KEDS bill
    Route::post('/scan', [KedsBillController::class, 'scan']);

    // Get all user's analyzed bills
    Route::get('/', [KedsBillController::class, 'index']);

    // Get specific bill details
    Route::get('/{id}', [KedsBillController::class, 'show']);

    // Get detailed breakdown of a bill
    Route::get('/{id}/breakdown', [KedsBillController::class, 'breakdown']);
});

// Dashboard Statistics (Protected - require authentication)
Route::middleware('auth:sanctum')->prefix('dashboard')->group(function () {
    // Get dashboard statistics
    Route::get('/stats', [DashboardController::class, 'stats']);
});
