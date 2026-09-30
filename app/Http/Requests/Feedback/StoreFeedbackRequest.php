<?php

namespace App\Http\Requests\Feedback;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = Event::find($this->event_id);

        if (! $event) {
            return false;
        }

        return $this->user()->can('create', [EventFeedback::class, $event]);
    }

    public function rules(): array
    {
        return [
            'event_id' => ['required', 'exists:events,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'event_id.required' => 'Kegiatan wajib dipilih.',
            'event_id.exists' => 'Kegiatan yang dipilih tidak ditemukan.',
            'rating.required' => 'Rating bintang wajib diberikan.',
            'rating.min' => 'Rating minimal 1 bintang.',
            'rating.max' => 'Rating maksimal 5 bintang.',
            'comment.required' => 'Komentar ulasan wajib diisi.',
            'comment.min' => 'Komentar ulasan minimal 5 karakter.',
            'comment.max' => 'Komentar ulasan maksimal 1000 karakter.',
        ];
    }
}
