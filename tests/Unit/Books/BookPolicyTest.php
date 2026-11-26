<?php

use App\Models\Book;
use App\Models\User;
use App\Policies\BookPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->policy = new BookPolicy;
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->librarian = User::factory()->create(['role' => 'librarian']);
    $this->student = User::factory()->create(['role' => 'student']);
    $this->book = Book::factory()->create();
});

test('every role can view the catalogue', function () {
    foreach ([$this->admin, $this->librarian, $this->student] as $user) {
        expect($this->policy->viewAny($user))->toBeTrue()
            ->and($this->policy->view($user, $this->book))->toBeTrue();
    }
});

test('admins and librarians can create and update books', function () {
    foreach ([$this->admin, $this->librarian] as $user) {
        expect($this->policy->create($user))->toBeTrue()
            ->and($this->policy->update($user, $this->book))->toBeTrue();
    }
});

test('students cannot create, update or delete books', function () {
    expect($this->policy->create($this->student))->toBeFalse()
        ->and($this->policy->update($this->student, $this->book))->toBeFalse()
        ->and($this->policy->delete($this->student, $this->book))->toBeFalse();
});

test('only admins can delete books', function () {
    expect($this->policy->delete($this->admin, $this->book))->toBeTrue()
        ->and($this->policy->delete($this->librarian, $this->book))->toBeFalse();
});

test('an unknown role gets no access at all', function () {
    $stranger = User::factory()->create(['role' => 'visitor']);

    expect($this->policy->viewAny($stranger))->toBeFalse()
        ->and($this->policy->create($stranger))->toBeFalse();
});
