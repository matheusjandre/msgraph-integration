<?php

use App\Http\Controllers\Api\Auth\AuthenticationController;
use App\Http\Controllers\Api\Microsoft\CalendarController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthenticationController::class, 'register']);
    Route::post('login', [AuthenticationController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {

    // Endpoints relacionados ao Microsoft Graph
    Route::prefix('microsoft')->group(function () {
        // Authoriza a utilizacao do Microsoft Graph
        Route::get('signin', [AuthenticationController::class, "signin"]);

        // Apaga o token gerado do Microsoft Graph
        Route::get('signout', [AuthenticationController::class, "signout"]);

        // Utilizacao do calendario do Microsoft Graph
        Route::get('calendar', [CalendarController::class, "calendar"]);
        Route::post('calendar/new', [CalendarController::class, "newEvent"]);
    });
});

// Microsoft callback handler
Route::group([], function () {
    Route::get('microsoft/callback', [AuthenticationController::class, "callback"]);
});
