import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { useTheme } from '@/components/ThemeProvider';
import type { AppearanceSettings, ThemeMode } from '@/types/settings';

const ORDER: readonly ThemeMode[] = ['light', 'dark', 'system'];

const LABELS: Record<ThemeMode, string> = {
    light: 'Terang',
    dark: 'Gelap',
    system: 'Sistem',
};

export function ThemeToggle() {
    const page = usePage();
    const auth = page.props.auth;
    const { appearance, setPreview, commit } = useTheme();
    const [pending, setPending] = useState(false);
    const nextIndex = (ORDER.indexOf(appearance.mode) + 1) % ORDER.length;
    const nextMode = ORDER[nextIndex];
    const nextAppearance: AppearanceSettings = {
        ...appearance,
        mode: nextMode,
    };

    const changeTheme = (): void => {
        if (pending) return;

        setPending(true);
        setPreview(nextAppearance);

        if (auth.user === null) {
            commit(nextAppearance);
            setPending(false);
            return;
        }

        const restore = (): void => {
            setPreview(appearance);
            setPending(false);
        };

        router.patch('/settings/appearance', nextAppearance, {
            preserveScroll: true,
            onSuccess: () => {
                commit(nextAppearance);
                setPending(false);
            },
            onError: restore,
            onHttpException: restore,
            onNetworkError: restore,
            onCancel: restore,
            onFinish: () => setPending(false),
        });
    };

    return (
        <button
            type="button"
            onClick={changeTheme}
            disabled={pending}
            aria-label={`Ubah tema. Tema saat ini: ${LABELS[appearance.mode]}. Berikutnya: ${LABELS[nextMode]}`}
            className="btn-secondary rounded-full"
        >
            <span className="flex items-center gap-2">
                <ThemeIcon appearance={appearance.mode} />
                Tema: {LABELS[appearance.mode]}
            </span>
        </button>
    );
}

function ThemeIcon({ appearance }: { appearance: ThemeMode }) {
    const className = 'h-5 w-5';
    if (appearance === 'light') {
        return (
            <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth={1.8}
                strokeLinecap="round"
                aria-hidden="true"
                className={className}
            >
                <circle cx="12" cy="12" r="4" />
                <path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32 1.41-1.41" />
            </svg>
        );
    }
    if (appearance === 'dark') {
        return (
            <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth={1.8}
                strokeLinecap="round"
                strokeLinejoin="round"
                aria-hidden="true"
                className={className}
            >
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" />
            </svg>
        );
    }
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.8}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            className={className}
        >
            <rect x="2" y="3" width="20" height="14" rx="2" />
            <path d="M8 21h8m-4-4v4" />
        </svg>
    );
}
