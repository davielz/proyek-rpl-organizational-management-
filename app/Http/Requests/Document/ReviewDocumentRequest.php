<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('document');

        return $this->user()->can('review', $document);
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['approve', 'request_revision', 'reject'])],
            'notes' => ['required_if:action,request_revision,reject', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'Aksi peninjauan wajib dipilih.',
            'action.in' => 'Aksi peninjauan tidak valid.',
            'notes.required_if' => 'Catatan revisi/penolakan wajib diisi untuk memberikan arahan kepada pengurus.',
        ];
    }
}
