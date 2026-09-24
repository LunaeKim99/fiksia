<?php

namespace App\Http\Requests\Studio;

use App\Models\Chapter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateChapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $chapter = $this->route('chapter');

        return $chapter instanceof Chapter
            && Gate::allows('update', $chapter);
    }

    protected function prepareForValidation(): void
    {
        $title = $this->input('title');
        $this->merge([
            'title' => is_string($title) ? trim($title) : $title,
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ];
    }
}
