import { useCallback, useEffect, useState } from 'react';

export type Appearance = 'light' | 'dark' | 'system';

const STORAGE_KEY = 'appearance';
const DARK_QUERY = '(prefers-color-scheme: dark)';

function isAppearance(value: unknown): value is Appearance {
    return value === 'light' || value === 'dark' || value === 'system';
}

function systemPrefersDark(): boolean {
    if (typeof window === 'undefined' || !window.matchMedia) return false;
    try {
        return window.matchMedia(DARK_QUERY).matches;
    } catch {
        return false;
    }
}

function readStoredAppearance(): Appearance {
    if (typeof window === 'undefined') return 'system';
    try {
        const stored = window.localStorage.getItem(STORAGE_KEY);
        return isAppearance(stored) ? stored : 'system';
    } catch {
        return 'system';
    }
}

function applyAppearance(value: Appearance): void {
    if (typeof document === 'undefined') return;
    const isDark =
        value === 'dark' || (value === 'system' && systemPrefersDark());
    document.documentElement.classList.toggle('dark', isDark);
}

export function useTheme(): {
    appearance: Appearance;
    setAppearance: (value: Appearance) => void;
} {
    const [appearance, setAppearanceState] =
        useState<Appearance>(readStoredAppearance);

    const setAppearance = useCallback((value: Appearance) => {
        if (typeof window !== 'undefined') {
            try {
                window.localStorage.setItem(STORAGE_KEY, value);
            } catch {
                void 0;
            }
        }
        setAppearanceState(value);
        applyAppearance(value);
    }, []);

    useEffect(() => {
        applyAppearance(appearance);
    }, [appearance]);

    useEffect(() => {
        if (appearance !== 'system') return;
        if (typeof window === 'undefined' || !window.matchMedia) return;
        let mq: MediaQueryList;
        try {
            mq = window.matchMedia(DARK_QUERY);
        } catch {
            return;
        }
        const handler = () => applyAppearance('system');
        mq.addEventListener('change', handler);
        return () => mq.removeEventListener('change', handler);
    }, [appearance]);

    return { appearance, setAppearance };
}
