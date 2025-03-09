<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        Log::info('Checking authorization for AddressRequest');
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        Log::info('Getting validation rules for AddressRequest', [
            'rules' => [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'company' => 'nullable|string|max:255',
                'primary' => 'required|boolean',
                'fullAddress' => 'required|string',
                'phoneNumber' => 'required|string',
                'addressType' => 'required|string|in:home,office',
            ]
        ]);

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'company' => 'nullable|string|max:255',
            'primary' => 'required|boolean',
            'fullAddress' => 'required|string',
            'phoneNumber' => 'required|string',
            'addressType' => 'required|string|in:home,office,Home,Office',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required',
            'name.string' => 'The name must be a string',
            'name.max' => 'The name cannot exceed 255 characters',
            'email.required' => 'The email field is required',
            'email.email' => 'Please provide a valid email address',
            'email.max' => 'The email cannot exceed 255 characters',
            'company.string' => 'The company name must be a string',
            'company.max' => 'The company name cannot exceed 255 characters',
            'primary.required' => 'Please specify if this is a primary address',
            'primary.boolean' => 'The isPrimary field must be true or false',
            'fullAddress.required' => 'The full address is required',
            'fullAddress.string' => 'The full address must be a string',
            'phoneNumber.required' => 'The phone number is required',
            'phoneNumber.string' => 'The phone number must be a string',
            'addressType.required' => 'The address type is required',
            'addressType.string' => 'The address type must be a string',
            'addressType.in' => 'The address type must be either home or office',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        Log::error('Address validation failed', [
            'errors' => $validator->errors()->toArray(),
            'request_data' => $this->all()
        ]);

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'errors' => $validator->errors()
        ], 422));
    }
}
