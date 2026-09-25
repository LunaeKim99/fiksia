import type {
    AccentStyle,
    AppearanceSettings,
    Preset,
    ReadingSettings,
    ResolvedTheme,
    ThemeMode,
} from '@/types/settings';

export const THEME_STORAGE_KEY = 'fiksia-theme';
export const LEGACY_THEME_STORAGE_KEY = 'appearance';
export const DARK_QUERY = '(prefers-color-scheme: dark)';

export const DEFAULT_APPEARANCE: AppearanceSettings = {
    mode: 'system',
    accentStyle: 'single',
    preset: 'violet',
    primaryColor: '#7C3AED',
    secondaryColor: null,
};

export const DEFAULT_READING: ReadingSettings = {
    fontSize: 'normal',
    fontFamily: 'sans',
    lineHeight: '1.7',
    columnWidth: 'normal',
};

export const PRESET_COLORS: Record<
    Exclude<Preset, 'custom'>,
    readonly [string, string]
> = {
    violet: ['#7C3AED', '#A855F7'],
    ember: ['#C2410C', '#F59E0B'],
    forest: ['#166534', '#0F766E'],
    ocean: ['#0E7490', '#2563EB'],
    rose: ['#BE123C', '#DB2777'],
};

const THEME_MODES: readonly ThemeMode[] = ['light', 'dark', 'system'];
const ACCENT_STYLES: readonly AccentStyle[] = ['single', 'dual', 'gradient'];
const PRESETS: readonly Preset[] = [
    'violet',
    'ember',
    'forest',
    'ocean',
    'rose',
    'custom',
];
const FONT_SIZES: readonly ReadingSettings['fontSize'][] = [
    'small',
    'normal',
    'large',
];
const FONT_FAMILIES: readonly ReadingSettings['fontFamily'][] = [
    'serif',
    'sans',
];
const LINE_HEIGHTS: readonly ReadingSettings['lineHeight'][] = [
    '1.5',
    '1.7',
    '2.0',
];
const COLUMN_WIDTHS: readonly ReadingSettings['columnWidth'][] = [
    'narrow',
    'normal',
    'wide',
];

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function isOneOf<T extends string>(
    value: unknown,
    values: readonly T[],
): value is T {
    return typeof value === 'string' && values.includes(value as T);
}

function isAppearanceMode(value: unknown): value is ThemeMode {
    return isOneOf(value, THEME_MODES);
}

function isAccentStyle(value: unknown): value is AccentStyle {
    return isOneOf(value, ACCENT_STYLES);
}

function isPreset(value: unknown): value is Preset {
    return isOneOf(value, PRESETS);
}

export function isHexColor(value: unknown): value is string {
    return typeof value === 'string' && /^#[0-9A-Fa-f]{6}$/.test(value);
}

function canonicalAppearance(value: unknown): AppearanceSettings {
    if (!isRecord(value)) return DEFAULT_APPEARANCE;

    const mode = isAppearanceMode(value.mode)
        ? value.mode
        : DEFAULT_APPEARANCE.mode;
    const accentStyle = isAccentStyle(value.accentStyle)
        ? value.accentStyle
        : DEFAULT_APPEARANCE.accentStyle;
    const preset = isPreset(value.preset)
        ? value.preset
        : DEFAULT_APPEARANCE.preset;

    if (preset !== 'custom') {
        const [primaryColor, secondaryColor] = PRESET_COLORS[preset];
        return {
            mode,
            accentStyle,
            preset,
            primaryColor,
            secondaryColor: accentStyle === 'single' ? null : secondaryColor,
        };
    }

    const primaryColor = isHexColor(value.primaryColor)
        ? value.primaryColor
        : DEFAULT_APPEARANCE.primaryColor;
    const secondaryColor =
        accentStyle === 'single'
            ? null
            : isHexColor(value.secondaryColor)
              ? value.secondaryColor
              : null;

    if (accentStyle !== 'single' && secondaryColor === null) {
        return DEFAULT_APPEARANCE;
    }

    return { mode, accentStyle, preset, primaryColor, secondaryColor };
}

export function normalizeAppearance(value: unknown): AppearanceSettings {
    return canonicalAppearance(value);
}

export function normalizeReading(value: unknown): ReadingSettings {
    if (!isRecord(value)) return DEFAULT_READING;

    return {
        fontSize: isOneOf(value.fontSize, FONT_SIZES)
            ? value.fontSize
            : DEFAULT_READING.fontSize,
        fontFamily: isOneOf(value.fontFamily, FONT_FAMILIES)
            ? value.fontFamily
            : DEFAULT_READING.fontFamily,
        lineHeight: isOneOf(value.lineHeight, LINE_HEIGHTS)
            ? value.lineHeight
            : DEFAULT_READING.lineHeight,
        columnWidth: isOneOf(value.columnWidth, COLUMN_WIDTHS)
            ? value.columnWidth
            : isOneOf(value.width, COLUMN_WIDTHS)
              ? value.width
              : DEFAULT_READING.columnWidth,
    };
}

export function resolveTheme(mode: ThemeMode): ResolvedTheme {
    if (
        mode !== 'system' ||
        typeof window === 'undefined' ||
        !window.matchMedia
    ) {
        return mode === 'dark' ? 'dark' : 'light';
    }

    try {
        return window.matchMedia(DARK_QUERY).matches ? 'dark' : 'light';
    } catch {
        return 'light';
    }
}

export function contrastRatio(
    foreground: readonly [number, number, number],
    background: readonly [number, number, number],
): number {
    const luminance = (color: readonly [number, number, number]): number => {
        const channels = color.map((channel) => {
            const normalized = channel / 255;
            return normalized <= 0.03928
                ? normalized / 12.92
                : ((normalized + 0.055) / 1.055) ** 2.4;
        });
        return (
            0.2126 * (channels[0] ?? 0) +
            0.7152 * (channels[1] ?? 0) +
            0.0722 * (channels[2] ?? 0)
        );
    };
    const foregroundLuminance = luminance(foreground);
    const backgroundLuminance = luminance(background);
    const lighter = Math.max(foregroundLuminance, backgroundLuminance);
    const darker = Math.min(foregroundLuminance, backgroundLuminance);
    return (lighter + 0.05) / (darker + 0.05);
}

function rgb(color: string): readonly [number, number, number] {
    return [
        Number.parseInt(color.slice(1, 3), 16),
        Number.parseInt(color.slice(3, 5), 16),
        Number.parseInt(color.slice(5, 7), 16),
    ];
}

function blend(
    color: readonly [number, number, number],
    overlay: readonly [number, number, number],
    alpha: number,
): readonly [number, number, number] {
    return [
        color[0] * (1 - alpha) + overlay[0] * alpha,
        color[1] * (1 - alpha) + overlay[1] * alpha,
        color[2] * (1 - alpha) + overlay[2] * alpha,
    ];
}

function foregroundFor(
    colors: readonly (readonly [number, number, number])[],
): string {
    const white: readonly [number, number, number] = [255, 255, 255];
    const dark: readonly [number, number, number] = [29, 27, 32];
    const whiteContrast = Math.min(
        ...colors.map((color) => contrastRatio(white, color)),
    );
    const darkContrast = Math.min(
        ...colors.map((color) => contrastRatio(dark, color)),
    );
    return whiteContrast >= darkContrast ? '#FFFFFF' : '#1D1B20';
}

function safeGradient(
    foreground: string,
    primary: readonly [number, number, number],
    secondary: readonly [number, number, number],
    primaryColor: string,
    secondaryColor: string,
): string {
    const overlay: readonly [number, number, number] =
        foreground === '#FFFFFF' ? [0, 0, 0] : [255, 255, 255];
    const samples = [primary, secondary, blend(primary, secondary, 0.5)];
    let low = 0;
    let high = 1;

    for (let index = 0; index < 30; index += 1) {
        const alpha = (low + high) / 2;
        const foregroundColor: readonly [number, number, number] =
            foreground === '#FFFFFF' ? [255, 255, 255] : [29, 27, 32];
        const readable = samples.every(
            (color) =>
                contrastRatio(foregroundColor, blend(color, overlay, alpha)) >=
                4.5,
        );
        if (readable) high = alpha;
        else low = alpha;
    }

    const base = `linear-gradient(135deg, ${primaryColor}, ${secondaryColor})`;
    if (high < 0.001) return base;

    const overlayColor = foreground === '#FFFFFF' ? '0 0 0' : '255 255 255';
    const alpha = high.toFixed(3);
    return `linear-gradient(rgb(${overlayColor} / ${alpha}), rgb(${overlayColor} / ${alpha})), ${base}`;
}

export function getCtaForeground(
    primaryColor: string,
    secondaryColor: string,
    contrastBothStops = true,
): string {
    if (!isHexColor(primaryColor) || !isHexColor(secondaryColor)) {
        return '#FFFFFF';
    }
    return foregroundFor(
        contrastBothStops
            ? [rgb(primaryColor), rgb(secondaryColor)]
            : [rgb(primaryColor)],
    );
}

export function getSafeGradient(
    primaryColor: string,
    secondaryColor: string,
): string {
    if (!isHexColor(primaryColor) || !isHexColor(secondaryColor)) {
        return `linear-gradient(135deg, ${DEFAULT_APPEARANCE.primaryColor}, ${DEFAULT_APPEARANCE.primaryColor})`;
    }
    const primary = rgb(primaryColor);
    const secondary = rgb(secondaryColor);
    return safeGradient(
        foregroundFor([primary, secondary]),
        primary,
        secondary,
        primaryColor,
        secondaryColor,
    );
}

export function applyAppearance(value: AppearanceSettings): void {
    if (typeof document === 'undefined') return;

    const appearance = canonicalAppearance(value);
    if (!isHexColor(appearance.primaryColor)) return;
    if (
        appearance.accentStyle !== 'single' &&
        !isHexColor(appearance.secondaryColor)
    )
        return;

    const resolved = resolveTheme(appearance.mode);
    const secondaryColor = appearance.secondaryColor ?? appearance.primaryColor;
    const foreground = getCtaForeground(
        appearance.primaryColor,
        secondaryColor,
        appearance.accentStyle === 'gradient',
    );
    const gradient = getSafeGradient(appearance.primaryColor, secondaryColor);
    const root = document.documentElement;

    root.classList.toggle('dark', resolved === 'dark');
    root.dataset.themeMode = appearance.mode;
    root.dataset.themeAccent = appearance.accentStyle;
    root.style.setProperty('--theme-primary', appearance.primaryColor);
    root.style.setProperty('--theme-secondary', secondaryColor);
    root.style.setProperty('--theme-on-primary', foreground);
    root.style.setProperty('--theme-gradient', gradient);
}

export function readGuestAppearance(): AppearanceSettings {
    if (typeof window === 'undefined') return DEFAULT_APPEARANCE;

    try {
        const stored = window.localStorage.getItem(THEME_STORAGE_KEY);
        if (stored !== null) {
            try {
                const parsed: unknown = JSON.parse(stored);
                if (isRecord(parsed) && isAppearanceMode(parsed.mode)) {
                    return canonicalAppearance(parsed);
                }
            } catch {
                return readLegacyAppearance();
            }
        }

        return readLegacyAppearance();
    } catch {
        return DEFAULT_APPEARANCE;
    }
}

function readLegacyAppearance(): AppearanceSettings {
    if (typeof window === 'undefined') return DEFAULT_APPEARANCE;

    try {
        const legacy = window.localStorage.getItem(LEGACY_THEME_STORAGE_KEY);
        return isAppearanceMode(legacy)
            ? { ...DEFAULT_APPEARANCE, mode: legacy }
            : DEFAULT_APPEARANCE;
    } catch {
        return DEFAULT_APPEARANCE;
    }
}

export function saveGuestAppearance(value: AppearanceSettings): void {
    if (typeof window === 'undefined') return;

    try {
        window.localStorage.setItem(
            THEME_STORAGE_KEY,
            JSON.stringify(canonicalAppearance(value)),
        );
        window.localStorage.setItem(LEGACY_THEME_STORAGE_KEY, value.mode);
    } catch {
        return;
    }
}

export type {
    AccentStyle,
    AppearanceSettings,
    Preset,
    ReadingSettings,
    ResolvedTheme,
    ThemeMode,
};
