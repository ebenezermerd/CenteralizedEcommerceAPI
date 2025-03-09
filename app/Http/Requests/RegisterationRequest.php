<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'role' => 'required|string|in:customer,supplier',
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users',
            'phone' => 'required|string|unique:users,phone',
            'sex' => 'required|string|in:male,female, Male, Female',
            'address' => 'required|string',
            'password' => 'required|string|min:8',
            'confirmPassword' => 'required|same:password',
            'verified' => 'boolean',
            // 'recaptchaToken' => 'required|captcha', // Validate reCAPTCHA
        ];

        if ($this->role === 'supplier') {
            $rules = array_merge($rules, [
                'companyName' => 'required|string|max:255',
                'description' => 'required|string|max:1000',
                'companyEmail' => 'required|email|unique:companies,email',
                'companyPhone' => 'required|string|unique:companies,phone',
                'country' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'companyAddress' => 'required|string|max:255',
                'agreement' => 'required|boolean',
            ]);
        }

        return $rules;
    }
}
