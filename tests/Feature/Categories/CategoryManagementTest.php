<?php

use App\Livewire\Categories\CategoryManagement;
use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));
});

test('category management component can render', function () {
    Livewire::test(CategoryManagement::class)->assertStatus(200);
});

test('categories are listed', function () {
    Category::factory()->create(['name' => 'Cooking']);

    Livewire::test(CategoryManagement::class)->assertSee('Cooking');
});

test('categories can be searched by name', function () {
    Category::factory()->create(['name' => 'Cooking']);
    Category::factory()->create(['name' => 'Astronomy']);

    Livewire::test(CategoryManagement::class)
        ->set('search', 'Astro')
        ->assertSee('Astronomy')
        ->assertDontSee('Cooking');
});

test('a category can be added successfully', function () {
    Livewire::test(CategoryManagement::class)
        ->call('openAddModal')
        ->set('name', 'Poetry')
        ->call('saveCategory')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('categories', ['name' => 'Poetry']);
});

test('category name is required', function () {
    Livewire::test(CategoryManagement::class)
        ->set('name', '')
        ->call('saveCategory')
        ->assertHasErrors(['name' => 'required']);
});

test('duplicate category name is not allowed', function () {
    Category::factory()->create(['name' => 'Poetry']);

    Livewire::test(CategoryManagement::class)
        ->set('name', 'Poetry')
        ->call('saveCategory')
        ->assertHasErrors(['name' => 'unique']);
});

test('a category can be edited successfully', function () {
    $category = Category::factory()->create(['name' => 'Old Name']);

    Livewire::test(CategoryManagement::class)
        ->call('editCategory', $category->id)
        ->assertSet('name', 'Old Name')
        ->set('name', 'New Name')
        ->call('updateCategory')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'New Name']);
});

test('a category can keep its own name when edited', function () {
    $category = Category::factory()->create(['name' => 'Same Name']);

    Livewire::test(CategoryManagement::class)
        ->call('editCategory', $category->id)
        ->call('updateCategory')
        ->assertHasNoErrors();
});

test('an empty category can be deleted', function () {
    $category = Category::factory()->create();

    Livewire::test(CategoryManagement::class)->call('deleteCategory', $category->id);

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

test('a category with books cannot be deleted', function () {
    $category = Category::factory()->create();
    Book::factory()->create(['category_id' => $category->id]);

    Livewire::test(CategoryManagement::class)->call('deleteCategory', $category->id);

    $this->assertDatabaseHas('categories', ['id' => $category->id]);
});
