<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ImportContentXmlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // No mimes rule on purpose: browsers report XML inconsistently, the parse decides
        return [
            'content_xml' => ['required', 'file', 'max:' . config('msfs_scenery.max_upload_kb')],
            'include_native' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'content_xml.required' => 'Choose your Content.xml file first.',
            'content_xml.max' => 'The file is too large to be a Content.xml.',
        ];
    }

    /** Same shape as the parser's own failures, so the form has one error format to show */
    protected function failedValidation(Validator $validator): void
    {
        $tooLarge = isset($validator->failed()['content_xml']['Max']);

        throw new HttpResponseException(response()->json([
            'message' => $validator->errors()->first(),
            'reason' => $tooLarge ? 'too_large' : 'invalid_request',
            'errors' => $validator->errors(),
        ], 422));
    }
}
