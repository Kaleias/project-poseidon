<?php

use App\Models\Category;
use App\Models\User;
use App\Policies\CategoryPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->policy = new CategoryPolicy();
    $this->category = Category::factory()->create();
});

test('admin and librarian can view categories', function () {
    foreach (['admin', 'librarian'] as $role) {
        $user = User::factory()->create(['role' => $role]);

        expect($this->policy->viewAny($user))->toBeTrue()
            ->and($this->policy->view($user, $this->category))->toBeTrue();
    }
});

test('students cannot view, create or update categories', function () {
    $student = User::factory()->create(['role' => 'student']);

    expect($this->policy->viewAny($student))->toBeFalse()
        ->and($this->policy->view($student, $this->category))->toBeFalse()
        ->and($this->policy->create($student))->toBeFalse()
        ->and($this->policy->update($student, $this->category))->toBeFalse();
});

test('admin and librarian can create and update categories', function () {
    foreach (['admin', 'librarian'] as $role) {
        $user = User::factory()->create(['role' => $role]);

        expect($this->policy->create($user))->toBeTrue()
            ->and($this->policy->update($user, $this->category))->toBeTrue();
    }
});

test('only admin can delete categories', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $librarian = User::factory()->create(['role' => 'librarian']);
    $student = User::factory()->create(['role' => 'student']);

    expect($this->policy->delete($admin, $this->category))->toBeTrue()
        ->and($this->policy->delete($librarian, $this->category))->toBeFalse()
        ->and($this->policy->delete($student, $this->category))->toBeFalse();
});
