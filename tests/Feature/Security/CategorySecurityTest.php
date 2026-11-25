<?php

use App\Livewire\Categories\CategoryManagement;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('students cannot access the category management component', function () {
    $student = User::factory()->create(['role' => 'student']);

    Livewire::actingAs($student)
        ->test(CategoryManagement::class)
        ->assertForbidden();
});

test('librarians can open the page and add categories', function () {
    $librarian = User::factory()->create(['role' => 'librarian']);

    Livewire::actingAs($librarian)
        ->test(CategoryManagement::class)
        ->set('name', 'Biography')
        ->call('saveCategory')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('categories', ['name' => 'Biography']);
});

test('librarians cannot delete categories', function () {
    $librarian = User::factory()->create(['role' => 'librarian']);
    $category = Category::factory()->create();

    Livewire::actingAs($librarian)
        ->test(CategoryManagement::class)
        ->call('deleteCategory', $category->id)
        ->assertForbidden();

    $this->assertDatabaseHas('categories', ['id' => $category->id]);
});

test('admins can delete categories', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $category = Category::factory()->create();

    Livewire::actingAs($admin)
        ->test(CategoryManagement::class)
        ->call('deleteCategory', $category->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

test('category names are escaped to prevent XSS', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Category::factory()->create(['name' => '<script>alert(1)</script>']);

    Livewire::actingAs($admin)
        ->test(CategoryManagement::class)
        ->assertDontSeeHtml('<script>alert(1)</script>');
});
