<?php

namespace App\Http\Requests\Moderation;

use App\Models\InitialSubmission;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ModerateSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $submission = $this->route('submission');

        return $submission instanceof InitialSubmission
            && Gate::allows('review', $submission);
    }

    protected function prepareForValidation(): void
    {
        $notes = $this->input('notes');
        $this->merge([
            'notes' => is_string($notes) ? trim($notes) : $notes,
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approved', 'revision_required', 'rejected'])],
            'notes' => [
                'nullable',
                'string',
                'max:5000',
                Rule::requiredIf(fn (): bool => $this->input('decision') !== 'approved'),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'decision.required' => 'Keputusan review wajib dipilih.',
            'decision.in' => 'Keputusan review tidak valid.',
            'notes.required' => 'Catatan review wajib diisi untuk revisi atau penolakan.',
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        if ($validator->errors()->has('decision')) {
            throw new HttpResponseException(response()->json([
                'message' => 'Keputusan review tidak valid.',
                'errors' => [
                    'decision' => ['Keputusan review tidak valid.'],
                ],
            ], 422));
        }

        throw new HttpResponseException(
            back()->withErrors($validator)->with(
                'error',
                'Catatan review wajib diisi untuk revisi atau penolakan.',
            ),
        );
    }
}
