<?php

use App\Models\Loan;
use App\Models\User;
use App\Policies\LoanPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->policy = new LoanPolicy;
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->librarian = User::factory()->create(['role' => 'librarian']);
    $this->student = User::factory()->create(['role' => 'student']);
    $this->activeLoan = Loan::factory()->create();
    $this->returnedLoan = Loan::factory()->returned()->create();
});

test('admins and librarians can view and create loans', function () {
    foreach ([$this->admin, $this->librarian] as $user) {
        expect($this->policy->viewAny($user))->toBeTrue()
            ->and($this->policy->view($user, $this->activeLoan))->toBeTrue()
            ->and($this->policy->create($user))->toBeTrue();
    }
});

test('students cannot view, create, update, return or delete loans', function () {
    expect($this->policy->viewAny($this->student))->toBeFalse()
        ->and($this->policy->view($this->student, $this->activeLoan))->toBeFalse()
        ->and($this->policy->create($this->student))->toBeFalse()
        ->and($this->policy->update($this->student, $this->activeLoan))->toBeFalse()
        ->and($this->policy->markReturned($this->student, $this->activeLoan))->toBeFalse()
        ->and($this->policy->delete($this->student, $this->activeLoan))->toBeFalse();
});

test('staff can update and return an active loan', function () {
    foreach ([$this->admin, $this->librarian] as $user) {
        expect($this->policy->update($user, $this->activeLoan))->toBeTrue()
            ->and($this->policy->markReturned($user, $this->activeLoan))->toBeTrue();
    }
});

test('nobody can update or return a loan that is already returned', function () {
    foreach ([$this->admin, $this->librarian] as $user) {
        expect($this->policy->update($user, $this->returnedLoan))->toBeFalse()
            ->and($this->policy->markReturned($user, $this->returnedLoan))->toBeFalse();
    }
});

test('only admins can delete loans', function () {
    expect($this->policy->delete($this->admin, $this->activeLoan))->toBeTrue()
        ->and($this->policy->delete($this->librarian, $this->activeLoan))->toBeFalse();
});
