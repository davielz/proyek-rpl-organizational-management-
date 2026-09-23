<?php

namespace App\Http\Requests\Document;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Document::class);
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
            'event_id.exists' => 'Kegiatan yang dipilih tidak valid.',
            'type.required' => 'Jenis dokumen (Proposal / LPJ) wajib dipilih.',
            'type.in' => 'Jenis dokumen tidak valid.',
            'title.required' => 'Judul dokumen wajib diisi.',
            'background.required' => 'Latar belakang wajib diisi.',
            'background.min' => 'Latar belakang minimal 10 karakter.',
            'objectives.required' => 'Tujuan kegiatan wajib diisi.',
            'objectives.min' => 'Tujuan kegiatan minimal 10 karakter.',
        ];
    }
}
