<?php

namespace App\Actions\Categories;

use App\Models\Category;

class CreateCategoryAction
{
    public function execute(array $data): Category
    {
        return Category::create([
            'name' => trim($data['name']),
        ]);
    }
}
