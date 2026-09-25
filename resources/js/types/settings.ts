export type ThemeMode = 'light' | 'dark' | 'system';
export type AccentStyle = 'single' | 'dual' | 'gradient';
export type ResolvedTheme = 'light' | 'dark';
export type Preset =
    | 'violet'
    | 'ember'
    | 'forest'
    | 'ocean'
    | 'rose'
    | 'custom';

export type AppearanceSettings = {
    mode: ThemeMode;
    accentStyle: AccentStyle;
    preset: Preset;
    primaryColor: string;
    secondaryColor: string | null;
};

export type ReadingSettings = {
    fontSize: 'small' | 'normal' | 'large';
    fontFamily: 'serif' | 'sans';
    lineHeight: '1.5' | '1.7' | '2.0';
    columnWidth: 'narrow' | 'normal' | 'wide';
};

export type SettingsSnapshot = {
    appearance: AppearanceSettings;
    reading: ReadingSettings;
    privacy: {
        publicReadingActivity: null;
        publicBookmarks: null;
    };
    notifications: {
        email: null;
        push: null;
    };
};
