<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Unique;

class TypeDocumentRequest extends FormRequest
{
    public function rules(): array
    {
        // Validate Update for unique value
        if ($this->isMethod('PUT')) {
            return [
                'description_typedoc' => [
                    'required',
                    (new Unique('type_documents', 'description_typedoc'))->ignore($this->route('type_document')),
                ],
            ];
        }

        return [
            'description_typedoc' => ['required', 'unique:type_documents,description_typedoc'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
