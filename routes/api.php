<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TranslationController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::apiResource('users', UserController::class);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);

        Route::get('translations/search', [TranslationController::class, 'search'])
            ->name('translations.search');

        Route::get('translations/export/{locale}', [TranslationController::class, 'export'])
            ->name('translations.export');

        Route::apiResource('translations', TranslationController::class);
    });
});
