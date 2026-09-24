<?php

namespace App\Http\Requests\Studio;

use App\Domain\Novel\Enums\StoryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNovelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $title = $this->input('title');
        $synopsis = $this->input('synopsis');

        $this->merge([
            'title' => is_string($title) ? trim($title) : $title,
            'synopsis' => is_string($synopsis) ? trim($synopsis) : $synopsis,
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'synopsis' => ['nullable', 'string', 'max:5000'],
            'story_status' => ['required', Rule::enum(StoryStatus::class)],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
            'genreIds' => ['nullable', 'array'],
            'genreIds.*' => ['integer', 'exists:genres,id'],
            'tagIds' => ['nullable', 'array'],
            'tagIds.*' => ['integer', 'exists:tags,id'],
        ];
    }
}
