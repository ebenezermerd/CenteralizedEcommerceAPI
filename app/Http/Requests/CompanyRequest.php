<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'email' => 'required|email|unique:companies,email',
            'phone' => 'required|string|unique:companies,phone',
            'country' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'address' => 'required|string',
            'owner_id' => 'required|exists:users,id',
            'agreement' => 'required|boolean',
        ];

        // Only admin can update status
        if ($this->user() && $this->user()->hasRole('admin')) {
            $rules['status'] = 'sometimes|string|in:active,inactive,pending,blocked';
        }

        return $rules;
    }
}
