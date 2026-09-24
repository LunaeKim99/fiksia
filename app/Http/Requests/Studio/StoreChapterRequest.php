<?php

namespace App\Http\Requests\Studio;

use App\Models\Chapter;
use App\Models\Novel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreChapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $novel = $this->route('novel');

        return $novel instanceof Novel
            && Gate::allows('create', [Chapter::class, $novel]);
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
