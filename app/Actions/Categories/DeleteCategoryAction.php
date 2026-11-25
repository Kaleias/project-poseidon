<?php

namespace App\Actions\Categories;

use App\Models\Category;
use DomainException;

class DeleteCategoryAction
{
    /**
     * Business rule: a category that still has books cannot be deleted.
     *
     * @throws DomainException
     */
    public function execute(Category $category): bool
    {
        if ($category->books()->exists()) {
            throw new DomainException('This category still has books. Move or delete them first.');
        }

        return (bool) $category->delete();
    }
}
