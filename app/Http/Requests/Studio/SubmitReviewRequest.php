<?php

namespace App\Http\Requests\Studio;

use App\Models\Novel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class SubmitReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $novel = $this->route('novel');

        return $novel instanceof Novel
            && Gate::allows('submitForReview', $novel);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'chapter_ids' => ['required', 'array', 'min:1'],
            'chapter_ids.*' => ['integer', 'exists:chapters,id'],
        ];
    }
}
