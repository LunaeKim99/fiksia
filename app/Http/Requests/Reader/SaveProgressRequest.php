<?php

namespace App\Http\Requests\Reader;

use Illuminate\Foundation\Http\FormRequest;

class SaveProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'novel_id' => ['required', 'integer', 'exists:novels,id'],
            'chapter_id' => ['required', 'integer', 'exists:chapters,id'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
