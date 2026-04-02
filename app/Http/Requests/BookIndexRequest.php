<?php

namespace App\Http\Requests;

use App\Enums\BookSearchMode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mode' => $this->input('mode', BookSearchMode::Books->value),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1000', 'max:2100'],
            'mode' => ['required', Rule::enum(BookSearchMode::class)],
        ];
    }
}
