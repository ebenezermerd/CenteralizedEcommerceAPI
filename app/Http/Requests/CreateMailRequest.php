<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator as ValidationValidator;

class CreateMailRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        Log::info('Validating mail creation request', [
            'user_id' => $this->user()?->id,
            'to' => $this->input('to'),
            'subject' => $this->input('subject'),
            'has_attachments' => $this->hasFile('attachments')
        ]);

        return [
            'to' => 'required|array|min:1',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'folder' => 'sometimes|string|in:inbox,sent,draft,trash',
            'labelIds' => 'nullable|array',
            'labelIds.*' => 'exists:mail_labels,id',
            'attachments' => 'nullable|array',
            'attachments.*' => [
                'file',
                'max:2048', // 2MB
                'mimes:jpeg,jpg,png,pdf,doc,docx,xls,xlsx,zip,rar'
            ],
        ];
    }

    protected function prepareForValidation()
    {
        $to = $this->input('to');

        // If to is a JSON string, decode it
        if (is_string($to) && is_array(json_decode($to, true))) {
            $to = json_decode($to, true);
        }

        // If to is a single string (email), convert to array
        if (is_string($to)) {
            $to = [$to];
        }

        $this->merge(['to' => $to]);
    }

    public function after(): array
    {
        return [
            function (ValidationValidator $validator) {
                $emails = $this->input('to');

                if (!is_array($emails)) {
                    $validator->errors()->add('to', 'Recipients must be provided as a list');
                    return;
                }

                foreach ($emails as $email) {
                    // Skip if not a string
                    if (!is_string($email)) {
                        $validator->errors()->add('to', 'Each recipient must be a valid email address');
                        continue;
                    }

                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $validator->errors()->add('to', "The email address '{$email}' is invalid.");
                        continue;
                    }

                    if (!DB::table('users')->where('email', $email)->exists()) {
                        $validator->errors()->add('to', "The email address '{$email}' is not registered in our system.");
                    }
                }
            }
        ];
    }

    public function messages()
    {
        return [
            'to.required' => 'At least one recipient is required',
            'to.array' => 'Recipients must be provided as a list',
            'to.min' => 'At least one recipient is required',
            'subject.required' => 'Email subject is required',
            'subject.string' => 'Subject must be text',
            'subject.max' => 'Subject cannot exceed 255 characters',
            'message.required' => 'Email message is required',
            'message.string' => 'Message must be text',
            'labelIds.array' => 'Labels must be provided as a list',
            'labelIds.*.exists' => 'One or more selected labels are invalid',
            'attachments.array' => 'Attachments must be provided as a list',
            'attachments.*.file' => 'Invalid file attachment',
            'attachments.*.max' => 'Each attachment must not exceed 2MB',
            'attachments.*.mimes' => 'Invalid file type for attachment'
        ];
    }

    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        Log::warning('Mail creation validation failed', [
            'user_id' => $this->user()?->id,
            'errors' => $validator->errors()->toArray()
        ]);

        throw new ValidationException($validator, response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors()
        ], 422));
    }
}
