<?php

use App\Actions\Categories\CreateCategoryAction;
use App\Actions\Categories\DeleteCategoryAction;
use App\Actions\Categories\UpdateCategoryAction;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('create action stores a trimmed category name', function () {
    $category = (new CreateCategoryAction())->execute(['name' => '  Mystery  ']);

    expect($category->name)->toBe('Mystery');
    $this->assertDatabaseHas('categories', ['name' => 'Mystery']);
});

test('update action changes the category name', function () {
    $category = Category::factory()->create(['name' => 'Old Name']);

    $updated = (new UpdateCategoryAction())->execute($category, ['name' => 'New Name']);

    expect($updated->name)->toBe('New Name');
    $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'New Name']);
});

test('delete action removes an empty category', function () {
    $category = Category::factory()->create();

    $result = (new DeleteCategoryAction())->execute($category);

    expect($result)->toBeTrue();
    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

test('delete action refuses to delete a category that still has books', function () {
    $category = Category::factory()->create();
    Book::factory()->create(['category_id' => $category->id]);

    (new DeleteCategoryAction())->execute($category);
})->throws(DomainException::class);
