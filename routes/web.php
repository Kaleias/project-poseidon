<?php

use App\Http\Middleware\EnsureTeamMembership;
use App\Livewire\Books\BookManagement;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {

    $slug = auth()->user()->currentTeam?->slug ?? auth()->user()->teams()->first()?->slug;
    

    if (!$slug) {
        return redirect()->route('dashboard', ['current_team' => 'default']);
    }

    return redirect()->route('dashboard', ['current_team' => $slug]);
})->middleware('auth')->name('home');

// 2. Keep the rest of the routes as they are
Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::view('dashboard', 'dashboard')->name('dashboard');
        Route::view('members', 'members')->name('members');
        Route::get('books', BookManagement::class)->name('books');
        Route::view('categories', 'categories')->name('categories');
    });

require __DIR__.'/settings.php';