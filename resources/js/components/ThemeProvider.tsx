import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useState,
} from 'react';
import type { ReactNode } from 'react';
import { usePage } from '@inertiajs/react';
import {
    applyAppearance,
    normalizeAppearance,
    readGuestAppearance,
    saveGuestAppearance,
} from '@/lib/theme';
import type {
    AppearanceSettings,
    ResolvedTheme,
    SettingsSnapshot,
} from '@/types/settings';

export type ThemeContextValue = {
    appearance: AppearanceSettings;
    resolvedTheme: ResolvedTheme;
    setPreview: (value: AppearanceSettings) => void;
    commit: (value: AppearanceSettings) => void;
};

type ThemeProviderProps = {
    children: ReactNode;
};

const ThemeContext = createContext<ThemeContextValue | null>(null);

function initialAppearance(
    settings: SettingsSnapshot | null,
    authenticated: boolean,
): AppearanceSettings {
    if (authenticated && settings !== null) {
        return normalizeAppearance(settings.appearance);
    }

    return readGuestAppearance();
}

export function ThemeProvider({
    children,
}: ThemeProviderProps): React.JSX.Element {
    const page = usePage();
    const authenticated = page.props.auth.user !== null;
    const [appearance, setAppearance] = useState(() =>
        initialAppearance(page.props.settings, authenticated),
    );
    const [resolvedTheme, setResolvedTheme] = useState<ResolvedTheme>('light');

    useEffect(() => {
        const next = initialAppearance(page.props.settings, authenticated);
        setAppearance(next);
        applyAppearance(next);
        setResolvedTheme(
            next.mode === 'dark' ||
                (next.mode === 'system' &&
                    document.documentElement.classList.contains('dark'))
                ? 'dark'
                : 'light',
        );
    }, [authenticated, page.props.settings]);

    useEffect(() => {
        applyAppearance(appearance);
        const root = document.documentElement;
        setResolvedTheme(root.classList.contains('dark') ? 'dark' : 'light');
    }, [appearance]);

    useEffect(() => {
        if (
            appearance.mode !== 'system' ||
            typeof window === 'undefined' ||
            !window.matchMedia
        )
            return;

        let mediaQuery: MediaQueryList;
        try {
            mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        } catch {
            return;
        }

        const update = (): void => {
            applyAppearance(appearance);
            setResolvedTheme(mediaQuery.matches ? 'dark' : 'light');
        };

        update();
        mediaQuery.addEventListener('change', update);
        return () => mediaQuery.removeEventListener('change', update);
    }, [appearance]);

    const setPreview = useCallback((value: AppearanceSettings): void => {
        const next = normalizeAppearance(value);
        setAppearance(next);
        applyAppearance(next);
    }, []);

    const commit = useCallback(
        (value: AppearanceSettings): void => {
            const next = normalizeAppearance(value);
            setAppearance(next);
            applyAppearance(next);
            if (!authenticated) saveGuestAppearance(next);
        },
        [authenticated],
    );

    const value = useMemo<ThemeContextValue>(
        () => ({
            appearance,
            resolvedTheme,
            setPreview,
            commit,
        }),
        [appearance, commit, resolvedTheme, setPreview],
    );

    return (
        <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>
    );
}

export function useTheme(): ThemeContextValue {
    const value = useContext(ThemeContext);
    if (value === null)
        throw new Error('useTheme must be used inside ThemeProvider');
    return value;
}
