<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event && ($this->user()?->can('manageStatus', $event) ?? false);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:draft,pending,active,completed,cancelled'],
        ];
    }
}
