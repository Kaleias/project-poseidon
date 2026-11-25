<?php

namespace App\Actions\Categories;

use App\Models\Category;

class UpdateCategoryAction
{
    public function execute(Category $category, array $data): Category
    {
        $category->update([
            'name' => trim($data['name']),
        ]);

        return $category;
    }
}
