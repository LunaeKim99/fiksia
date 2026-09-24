<?php

namespace App\Http\Requests\Reader;

use Illuminate\Foundation\Http\FormRequest;

class RemoveBookmarkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'chapter_id' => ['required', 'integer', 'exists:chapters,id'],
        ];
    }
}
