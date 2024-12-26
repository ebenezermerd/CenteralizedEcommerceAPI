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
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:255',
            'sex' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'password' => 'required|string|min:6|confirmed',
            'verified' => 'boolean',
            'recaptchaToken' => 'required|captcha', // Validate reCAPTCHA
        ];

        if ($this->role === 'supplier') {
            $rules = array_merge($rules, [
                'companyName' => 'required|string|max:255',
                'description' => 'required|string|max:1000',
                'companyEmail' => 'required|email|max:255',
                'companyPhone' => 'required|string|max:255',
                'country' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'companyAddress' => 'required|string|max:255',
                'agreement' => 'required|boolean',
            ]);
        }

        return $rules;
    }
}
