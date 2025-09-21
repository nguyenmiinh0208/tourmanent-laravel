<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\PairingController;

// API for external systems
Route::prefix('external')->group(function () {
    // Update match results from external system
    Route::post('/matches/{id}/result', [MatchController::class, 'updateResult']);
    Route::get('/matches/{id}', [MatchController::class, 'show']);
    Route::get('/matches/schedule/today', [MatchController::class, 'todaySchedule']);
});

// Admin API routes
Route::prefix('admin')->group(function () {
    // Tournament management
    Route::apiResource('tournaments', TournamentController::class);
    Route::post('tournaments/{id}/import-players', [TournamentController::class, 'importPlayers']);
    Route::post('tournaments/{id}/generate-pairs', [TournamentController::class, 'generatePairs']);
    Route::post('tournaments/{id}/schedule-matches', [TournamentController::class, 'scheduleMatches']);
    Route::patch('tournaments/{id}/status', [TournamentController::class, 'updateStatus']);

    // Match management
    Route::get('matches', [MatchController::class, 'index']);
    Route::get('matches/today-schedule', [MatchController::class, 'todaySchedule']);
    Route::get('matches/{id}', [MatchController::class, 'show']);
    Route::post('matches/{id}/start', [MatchController::class, 'start']);
    Route::post('matches/{id}/cancel', [MatchController::class, 'cancel']);
    Route::post('matches/{id}/result', [MatchController::class, 'updateResult']);

    // Leaderboard
    Route::get('leaderboard', [LeaderboardController::class, 'index']);
    Route::get('leaderboard/players/{id}/stats', [LeaderboardController::class, 'playerStats']);
    Route::get('leaderboard/top-performers', [LeaderboardController::class, 'topPerformers']);
    Route::get('leaderboard/summary', [LeaderboardController::class, 'summary']);
    Route::get('leaderboard/export', [LeaderboardController::class, 'export']);

    // Pairing and scheduling
    Route::get('pairing/phases', [PairingController::class, 'getPhases']);
    Route::post('pairing/generate-pairs', [PairingController::class, 'generatePairs']);
    Route::post('pairing/schedule-matches', [PairingController::class, 'scheduleMatches']);
    Route::get('pairing/pairs', [PairingController::class, 'getPairs']);
    Route::get('pairing/scheduled-matches', [PairingController::class, 'getScheduledMatches']);
});

// Public API routes
Route::get('leaderboard', [LeaderboardController::class, 'index']);
Route::get('matches/today', [MatchController::class, 'todaySchedule']);
Route::get('tournaments/summary', [LeaderboardController::class, 'summary']);
