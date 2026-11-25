<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization is handled by CategoryPolicy
    }

    public function rules(): array
    {
        return self::rulesFor();
    }

    /**
     * Shared rules for create and update.
     * Pass the category id when updating so its own name is not flagged as a duplicate.
     */
    public static function rulesFor(?int $ignoreId = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                Rule::unique('categories', 'name')->ignore($ignoreId),
            ],
        ];
    }
}
