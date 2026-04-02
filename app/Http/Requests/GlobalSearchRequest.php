<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GlobalSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array{q: string|null}
     */
    public function validatedQuery(): array
    {
        /** @var array{q?: string|null} $data */
        $data = $this->validated();

        $raw = $data['q'] ?? null;
        $trimmed = is_string($raw) ? trim($raw) : null;

        return [
            'q' => ($trimmed !== null && $trimmed !== '') ? $trimmed : null,
        ];
    }
}
