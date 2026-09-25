<?php

namespace App\Application\Settings;

final class SettingsNormalizer
{
    public const APPEARANCE_MODES = ['light', 'dark', 'system'];

    public const APPEARANCE_ACCENT_STYLES = ['single', 'dual', 'gradient'];

    public const APPEARANCE_PRESETS = ['violet', 'ember', 'forest', 'ocean', 'rose', 'custom'];

    public const READING_FONT_SIZES = ['small', 'normal', 'large'];

    public const READING_FONT_FAMILIES = ['serif', 'sans'];

    public const READING_LINE_HEIGHTS = ['1.5', '1.7', '2.0'];

    public const READING_COLUMN_WIDTHS = ['narrow', 'normal', 'wide'];

    public const APPEARANCE_KEYS = ['mode', 'accentStyle', 'preset', 'primaryColor', 'secondaryColor'];

    public const READING_KEYS = ['fontSize', 'fontFamily', 'lineHeight', 'columnWidth'];

    private const PRESET_COLORS = [
        'violet' => ['primaryColor' => '#7C3AED', 'secondaryColor' => '#A855F7'],
        'ember' => ['primaryColor' => '#C2410C', 'secondaryColor' => '#F59E0B'],
        'forest' => ['primaryColor' => '#166534', 'secondaryColor' => '#0F766E'],
        'ocean' => ['primaryColor' => '#0E7490', 'secondaryColor' => '#2563EB'],
        'rose' => ['primaryColor' => '#BE123C', 'secondaryColor' => '#DB2777'],
    ];

    public function defaults(): array
    {
        return [
            'appearance' => [
                'mode' => 'system',
                'accentStyle' => 'single',
                'preset' => 'violet',
                'primaryColor' => '#7C3AED',
                'secondaryColor' => null,
            ],
            'reading' => [
                'fontSize' => 'normal',
                'fontFamily' => 'sans',
                'lineHeight' => '1.7',
                'columnWidth' => 'normal',
            ],
            'privacy' => [
                'publicReadingActivity' => null,
                'publicBookmarks' => null,
            ],
            'notifications' => [
                'email' => null,
                'push' => null,
            ],
        ];
    }

    public function isHexColor(mixed $value): bool
    {
        return is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1;
    }

    public function appearance(array $current, array $patch): array
    {
        $next = array_replace(
            $this->defaults()['appearance'],
            $this->knownAppearanceValues($current),
        );
        $next = $this->repairAppearanceStored($next);

        if (array_key_exists('mode', $patch) && in_array($patch['mode'], self::APPEARANCE_MODES, true)) {
            $next['mode'] = $patch['mode'];
        }
        if (array_key_exists('accentStyle', $patch) && in_array($patch['accentStyle'], self::APPEARANCE_ACCENT_STYLES, true)) {
            $next['accentStyle'] = $patch['accentStyle'];
        }
        if (array_key_exists('preset', $patch) && in_array($patch['preset'], self::APPEARANCE_PRESETS, true)) {
            $next['preset'] = $patch['preset'];
        }
        if (array_key_exists('primaryColor', $patch) && $this->isHexColor($patch['primaryColor'])) {
            $next['primaryColor'] = $patch['primaryColor'];
        }
        if (array_key_exists('secondaryColor', $patch) && $this->isHexColor($patch['secondaryColor'])) {
            $next['secondaryColor'] = $patch['secondaryColor'];
        }

        return $this->resolveAppearance($next);
    }

    public function reading(array $current, array $patch): array
    {
        $defaults = $this->defaults()['reading'];
        $next = array_replace($defaults, $this->knownReadingValues($current));

        $next['fontSize'] = in_array($next['fontSize'] ?? null, self::READING_FONT_SIZES, true)
            ? $next['fontSize']
            : $defaults['fontSize'];
        $next['fontFamily'] = in_array($next['fontFamily'] ?? null, self::READING_FONT_FAMILIES, true)
            ? $next['fontFamily']
            : $defaults['fontFamily'];
        $next['lineHeight'] = $this->normalizeLineHeight($next['lineHeight'] ?? null) ?? $defaults['lineHeight'];
        $next['columnWidth'] = in_array($next['columnWidth'] ?? null, self::READING_COLUMN_WIDTHS, true)
            ? $next['columnWidth']
            : $defaults['columnWidth'];

        if (array_key_exists('fontSize', $patch) && in_array($patch['fontSize'], self::READING_FONT_SIZES, true)) {
            $next['fontSize'] = $patch['fontSize'];
        }
        if (array_key_exists('fontFamily', $patch) && in_array($patch['fontFamily'], self::READING_FONT_FAMILIES, true)) {
            $next['fontFamily'] = $patch['fontFamily'];
        }
        if (array_key_exists('lineHeight', $patch)) {
            $lineHeight = $this->normalizeLineHeight($patch['lineHeight']);
            if ($lineHeight !== null) {
                $next['lineHeight'] = $lineHeight;
            }
        }
        if (array_key_exists('columnWidth', $patch) && in_array($patch['columnWidth'], self::READING_COLUMN_WIDTHS, true)) {
            $next['columnWidth'] = $patch['columnWidth'];
        }

        return $next;
    }

    public function privacy(array $current, array $patch): array
    {
        return $this->defaults()['privacy'];
    }

    public function notifications(array $current, array $patch): array
    {
        return $this->defaults()['notifications'];
    }

    private function repairAppearanceStored(array $stored): array
    {
        $defaults = $this->defaults()['appearance'];
        $stored = $this->knownAppearanceValues($stored);
        $stored['mode'] = in_array($stored['mode'] ?? null, self::APPEARANCE_MODES, true)
            ? $stored['mode']
            : $defaults['mode'];
        $stored['accentStyle'] = in_array($stored['accentStyle'] ?? null, self::APPEARANCE_ACCENT_STYLES, true)
            ? $stored['accentStyle']
            : $defaults['accentStyle'];
        $stored['preset'] = in_array($stored['preset'] ?? null, self::APPEARANCE_PRESETS, true)
            ? $stored['preset']
            : $defaults['preset'];

        if ($stored['preset'] !== 'custom') {
            return $this->resolveAppearance($stored);
        }

        if (! $this->isHexColor($stored['primaryColor'] ?? null)) {
            $stored['primaryColor'] = $defaults['primaryColor'];
        }
        if ($stored['accentStyle'] === 'single') {
            $stored['secondaryColor'] = null;
        } elseif (! $this->isHexColor($stored['secondaryColor'] ?? null)) {
            return $defaults;
        }

        return $stored;
    }

    private function resolveAppearance(array $stored): array
    {
        if ($stored['preset'] !== 'custom') {
            $canonical = self::PRESET_COLORS[$stored['preset']];
            $stored['primaryColor'] = $canonical['primaryColor'];
            $stored['secondaryColor'] = $stored['accentStyle'] === 'single'
                ? null
                : $canonical['secondaryColor'];

            return $stored;
        }

        if (! $this->isHexColor($stored['primaryColor'] ?? null)) {
            return $this->defaults()['appearance'];
        }
        if ($stored['accentStyle'] === 'single') {
            $stored['secondaryColor'] = null;

            return $stored;
        }
        if (! $this->isHexColor($stored['secondaryColor'] ?? null)) {
            return $this->defaults()['appearance'];
        }

        return $stored;
    }

    private function knownAppearanceValues(array $values): array
    {
        return array_intersect_key($values, array_flip(self::APPEARANCE_KEYS));
    }

    private function knownReadingValues(array $values): array
    {
        return array_intersect_key($values, array_flip(self::READING_KEYS));
    }

    private function normalizeLineHeight(mixed $value): ?string
    {
        if (is_int($value) || is_float($value)) {
            $value = number_format((float) $value, 1, '.', '');
        }
        if (is_string($value) && is_numeric($value)) {
            $value = number_format((float) $value, 1, '.', '');
        }

        return in_array($value, self::READING_LINE_HEIGHTS, true) ? $value : null;
    }
}
