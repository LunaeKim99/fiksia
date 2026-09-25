<?php

namespace App\Http\Requests\Settings;

use App\Application\Settings\SettingsNormalizer;
use App\Domain\Settings\Enums\SettingsSection;
use App\Http\Support\SafeInternalRedirect;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

final class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function section(): SettingsSection
    {
        return SettingsSection::from((string) $this->route('section'));
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $section = $this->route('section');
        $allowed = $section === 'appearance'
            ? SettingsNormalizer::APPEARANCE_KEYS
            : ($section === 'reading'
                ? SettingsNormalizer::READING_KEYS
                : []);

        $rules = [];
        foreach ($allowed as $key) {
            $rules[$key] = ['sometimes'];
        }

        if ($section === 'appearance') {
            $rules['mode'] = ['sometimes', 'in:'.implode(',', SettingsNormalizer::APPEARANCE_MODES)];
            $rules['accentStyle'] = ['sometimes', 'in:'.implode(',', SettingsNormalizer::APPEARANCE_ACCENT_STYLES)];
            $rules['preset'] = ['sometimes', 'in:'.implode(',', SettingsNormalizer::APPEARANCE_PRESETS)];
            $rules['primaryColor'] = ['sometimes', 'regex:/^#[0-9A-Fa-f]{6}$/'];
            $rules['secondaryColor'] = ['sometimes', 'nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'];
        }

        if ($section === 'reading') {
            $rules['fontSize'] = ['sometimes', 'in:'.implode(',', SettingsNormalizer::READING_FONT_SIZES)];
            $rules['fontFamily'] = ['sometimes', 'in:'.implode(',', SettingsNormalizer::READING_FONT_FAMILIES)];
            $rules['lineHeight'] = ['sometimes', 'in:'.implode(',', SettingsNormalizer::READING_LINE_HEIGHTS)];
            $rules['columnWidth'] = ['sometimes', 'in:'.implode(',', SettingsNormalizer::READING_COLUMN_WIDTHS)];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $sectionValue = (string) $this->route('section');
            $section = SettingsSection::tryFrom($sectionValue);
            if ($section === null) {
                return;
            }

            $input = $this->all();

            if ($section === SettingsSection::Privacy || $section === SettingsSection::Notifications) {
                foreach (array_keys($input) as $key) {
                    $v->errors()->add($key, 'Unavailable setting.');
                }

                return;
            }

            if (array_key_exists('user_id', $input)) {
                $v->errors()->add('user_id', 'The user_id field is prohibited.');
            }

            $allowed = match ($section) {
                SettingsSection::Appearance => SettingsNormalizer::APPEARANCE_KEYS,
                SettingsSection::Reading => SettingsNormalizer::READING_KEYS,
            };

            foreach (array_keys($input) as $key) {
                if (! in_array($key, $allowed, true)) {
                    $v->errors()->add($key, 'Unknown key for '.$section->value.'.');
                }
            }

            if ($section === SettingsSection::Appearance) {
                $normalizer = new SettingsNormalizer;
                $current = $this->user()?->settings()->first()?->appearance ?? [];
                $current = array_replace(
                    $normalizer->defaults()['appearance'],
                    is_array($current) ? $current : [],
                );
                $effective = array_replace(
                    $current,
                    array_intersect_key($input, array_flip(SettingsNormalizer::APPEARANCE_KEYS)),
                );

                if (array_key_exists('primaryColor', $input)
                    && ! $normalizer->isHexColor($input['primaryColor'])
                    && ! $v->errors()->has('primaryColor')) {
                    $v->errors()->add('primaryColor', 'Color must be in #RRGGBB format.');
                }
                if (array_key_exists('secondaryColor', $input)
                    && $input['secondaryColor'] !== null
                    && ! $normalizer->isHexColor($input['secondaryColor'])
                    && ! $v->errors()->has('secondaryColor')) {
                    $v->errors()->add('secondaryColor', 'Color must be in #RRGGBB format.');
                }

                if (($input['preset'] ?? null) === 'custom'
                    && (! array_key_exists('primaryColor', $input) || ! $normalizer->isHexColor($input['primaryColor']))
                    && ! $v->errors()->has('primaryColor')) {
                    $v->errors()->add('primaryColor', 'Custom preset requires a valid primary color in #RRGGBB format.');
                }

                if (in_array($effective['accentStyle'], ['dual', 'gradient'], true)
                    && ! $normalizer->isHexColor($effective['secondaryColor'] ?? null)
                    && ! $v->errors()->has('secondaryColor')) {
                    $v->errors()->add('secondaryColor', 'Secondary color is required for dual or gradient custom appearance.');
                }
            }
        });
    }

    protected function failedValidation(Validator $validator): never
    {
        if ($this->expectsJson()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        throw new HttpResponseException(
            redirect()
                ->to(SafeInternalRedirect::resolve($this))
                ->withErrors($validator)
                ->with(
                    'error',
                    'Pengaturan gagal diperbarui. Periksa kembali isian Anda.',
                ),
        );
    }
}
