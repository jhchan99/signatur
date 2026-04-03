<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AuthorIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<int, string|string[]>|string>
     */
    public function rules(): array
    {
        return [
            'letter' => ['nullable', 'string', Rule::in(array_merge(range('A', 'Z'), ['#']))],
        ];
    }

    protected function prepareForValidation(): void
    {
        $raw = $this->query('letter');

        if (! is_string($raw)) {
            $this->merge(['letter' => null]);

            return;
        }

        $trimmed = trim($raw);

        if ($trimmed === '') {
            $this->merge(['letter' => null]);

            return;
        }

        $upper = strtoupper($trimmed);

        if ($upper === 'ALL') {
            $this->merge(['letter' => null]);

            return;
        }

        if ($upper === '#') {
            $this->merge(['letter' => '#']);

            return;
        }

        if (strlen($upper) === 1 && $upper >= 'A' && $upper <= 'Z') {
            $this->merge(['letter' => $upper]);

            return;
        }

        $this->merge(['letter' => null]);
    }
}
