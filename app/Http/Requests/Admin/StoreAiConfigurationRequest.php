<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAiConfigurationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'provider' => ['required', 'string', 'in:nvidia,gemini,openai,custom'],
            'api_key' => ['nullable', 'string'],
            'model' => ['required', 'string', 'max:150'],
            'base_url' => ['nullable', 'string', 'max:255'],
            'timeout' => ['nullable', 'integer', 'min:5', 'max:180'],
            'max_tokens' => ['nullable', 'integer', 'min:50', 'max:8192'],
            'temperature' => ['nullable', 'numeric', 'min:0', 'max:2.0'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
