<?php

use App\Livewire\Loans\LoanManagement;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ---------- Authentication / route access ----------

test('guests are redirected to the login page when opening loans', function () {
    $user = User::factory()->create();

    $this->get(route('loans', ['current_team' => $user->currentTeam->slug]))
        ->assertRedirect(route('login'));
});

test('staff can open the loans page', function () {
    $librarian = User::factory()->create(['role' => 'librarian']);

    $this->actingAs($librarian)
        ->get(route('loans', ['current_team' => $librarian->currentTeam->slug]))
        ->assertOk();
});

// ---------- Authorization (LoanPolicy inside the component) ----------

test('students cannot open the loan management component', function () {
    $student = User::factory()->create(['role' => 'student']);

    Livewire::actingAs($student)
        ->test(LoanManagement::class)
        ->assertForbidden();
});

test('a librarian cannot delete a loan', function () {
    $librarian = User::factory()->create(['role' => 'librarian']);
    $loan = Loan::factory()->create();

    Livewire::actingAs($librarian)
        ->test(LoanManagement::class)
        ->call('deleteLoan', $loan->id)
        ->assertForbidden();

    $this->assertDatabaseHas('loans', ['id' => $loan->id]);
});

test('the delete button is not rendered for a librarian but is for an admin', function () {
    Loan::factory()->create();

    Livewire::actingAs(User::factory()->create(['role' => 'librarian']))
        ->test(LoanManagement::class)
        ->assertDontSee('Delete');

    Livewire::actingAs(User::factory()->create(['role' => 'admin']))
        ->test(LoanManagement::class)
        ->assertSee('Delete');
});

// ---------- XSS ----------

test('member names and notes are escaped, never rendered as HTML', function () {
    $member = Member::factory()->create(['name' => '<script>alert("xss-name")</script>']);
    Loan::factory()->create([
        'member_id' => $member->id,
        'notes' => '<img src=x onerror=alert("xss-note")>',
    ]);

    Livewire::actingAs(User::factory()->create(['role' => 'admin']))
        ->test(LoanManagement::class)
        ->assertDontSeeHtml('<script>alert("xss-name")</script>')
        ->assertDontSeeHtml('<img src=x onerror=alert("xss-note")>')
        ->assertSee('&lt;script&gt;', false);
});

test('the loan notes field rejects input longer than 500 characters', function () {
    $librarian = User::factory()->create(['role' => 'librarian']);

    Livewire::actingAs($librarian)
        ->test(LoanManagement::class)
        ->set('notes', str_repeat('a', 501))
        ->call('saveLoan')
        ->assertHasErrors(['notes' => 'max']);
});
