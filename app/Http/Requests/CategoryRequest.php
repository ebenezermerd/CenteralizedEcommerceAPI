<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:unique:categories,name,' . $this->category?->id,
            'parentId' => 'nullable|exists:categories,id',
            'group' => 'required|string|max:255',
            'description' => 'nullable|string',
            'isActive' => 'boolean'
        ];
    }
}
