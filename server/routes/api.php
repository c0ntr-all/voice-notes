<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\RecordingController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])
        ->middleware('throttle:auth')
        ->name('auth.register');
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:auth')
        ->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('notes', [NoteController::class, 'index'])->name('notes.index');
    Route::post('notes', [NoteController::class, 'store'])->name('notes.store');
    Route::post('recordings', [RecordingController::class, 'store'])->name('recordings.store');
    Route::get('notes/{note}', [NoteController::class, 'show'])->whereUuid('note')->name('notes.show');
    Route::match(['put', 'patch'], 'notes/{note}', [NoteController::class, 'update'])->whereUuid('note')->name('notes.update');
    Route::delete('notes/{note}', [NoteController::class, 'destroy'])->whereUuid('note')->name('notes.destroy');
});
