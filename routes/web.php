<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\PairingController;

Route::get('/', function () {
    return view('welcome');
});

// Admin routes
Route::prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/', function () {
        return view('admin.dashboard');
    })->name('dashboard');
    
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');
    
    // Tournament management
    Route::prefix('tournaments')->name('tournaments.')->group(function () {
        Route::get('/', [TournamentController::class, 'webIndex'])->name('index');
        Route::get('/create', [TournamentController::class, 'create'])->name('create');
        Route::get('/{id}', [TournamentController::class, 'webShow'])->name('show');
        Route::get('/{id}/edit', [TournamentController::class, 'edit'])->name('edit');
    });
    
    // Player management
    Route::prefix('players')->name('players.')->group(function () {
        Route::get('/', function () {
            return view('admin.players.index');
        })->name('index');
        Route::get('/import', function () {
            return view('admin.players.import');
        })->name('import');
    });
    
            // Pairing and scheduling
            Route::prefix('pairing')->name('pairing.')->group(function () {
                Route::get('/', [PairingController::class, 'index'])->name('index');
                Route::get('/generate', [PairingController::class, 'generate'])->name('generate');
                Route::get('/schedule', [PairingController::class, 'schedule'])->name('schedule');
            });
    
    // Match management
    Route::prefix('matches')->name('matches.')->group(function () {
        Route::get('/', [MatchController::class, 'webIndex'])->name('index');
        Route::get('/today', [MatchController::class, 'today'])->name('today');
        Route::get('/{id}', [MatchController::class, 'webShow'])->name('show');
    });
    
    // Leaderboard
    Route::prefix('leaderboard')->name('leaderboard.')->group(function () {
        Route::get('/', [LeaderboardController::class, 'webIndex'])->name('index');
    });
    
    // Settings
    Route::get('/settings', function () {
        return view('admin.settings');
    })->name('settings');
});
