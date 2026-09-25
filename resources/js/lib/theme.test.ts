import { describe, expect, it } from 'vitest';
import {
    contrastRatio,
    getCtaForeground,
    getSafeGradient,
    PRESET_COLORS,
} from './theme';

function channels(color: string): readonly [number, number, number] {
    return [
        Number.parseInt(color.slice(1, 3), 16),
        Number.parseInt(color.slice(3, 5), 16),
        Number.parseInt(color.slice(5, 7), 16),
    ];
}

describe('theme contrast', () => {
    it.each(Object.entries(PRESET_COLORS))(
        'chooses readable foreground for %s gradient endpoints',
        (_name, [primary, secondary]) => {
            const foreground = getCtaForeground(primary, secondary);
            const foregroundColor = channels(foreground);
            const contrast = Math.min(
                contrastRatio(foregroundColor, channels(primary)),
                contrastRatio(foregroundColor, channels(secondary)),
            );

            expect(contrast).toBeGreaterThanOrEqual(3);
            expect(getSafeGradient(primary, secondary)).toContain(
                'linear-gradient',
            );
        },
    );

    it('uses primary contrast for a dark dual CTA', () => {
        expect(getCtaForeground('#888888', '#555555', false)).toBe('#1D1B20');
        expect(getCtaForeground('#888888', '#555555', true)).toBe('#FFFFFF');
    });

    it('adds a contrast overlay when preset endpoints need one', () => {
        expect(getSafeGradient('#7C3AED', '#A855F7')).toContain(
            'linear-gradient(rgb(0 0 0 /',
        );
        expect(getSafeGradient('#C2410C', '#F59E0B')).toContain(
            'linear-gradient(rgb(255 255 255 /',
        );
    });
});
