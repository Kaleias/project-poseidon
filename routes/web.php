<?php

use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::view('dashboard', 'dashboard')->name('dashboard');

        // Member Management - Protected by Policy
        // Note: The Livewire component will also check via $this->authorize()
        Route::view('members', 'members')->name('members');
    });

require __DIR__.'/settings.php';
