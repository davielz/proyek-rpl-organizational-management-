<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('document');

        return $this->user()->can('update', $document);
    }

    public function rules(): array
    {
        return [
            'event_id' => ['required', 'exists:events,id'],
            'type' => ['required', Rule::in(['proposal', 'lpj'])],
            'title' => ['required', 'string', 'max:255'],
            'background' => ['required', 'string', 'min:10'],
            'objectives' => ['required', 'string', 'min:10'],
            'budget_plan' => ['nullable', 'string'],
            'summary_content' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'event_id.required' => 'Kegiatan wajib dipilih.',
            'type.required' => 'Jenis dokumen wajib dipilih.',
            'title.required' => 'Judul dokumen wajib diisi.',
            'background.required' => 'Latar belakang wajib diisi.',
            'objectives.required' => 'Tujuan kegiatan wajib diisi.',
        ];
    }
}
