import { useEffect, useRef, useState, type FormEvent, type JSX } from 'react';
import { router } from '@inertiajs/react';
import { useTheme } from '@/components/ThemeProvider';
import { normalizeAppearance, PRESET_COLORS } from '@/lib/theme';
import type {
    AccentStyle,
    AppearanceSettings,
    Preset,
    ThemeMode,
} from '@/types/settings';

type SaveStatus = 'idle' | 'saving' | 'saved' | 'error';

const MODES: readonly ThemeMode[] = ['light', 'dark', 'system'];
const ACCENT_STYLES: readonly AccentStyle[] = ['single', 'dual', 'gradient'];
const PRESETS: readonly Preset[] = [
    'violet',
    'ember',
    'forest',
    'ocean',
    'rose',
    'custom',
];

const MODE_LABELS: Record<ThemeMode, string> = {
    light: 'Terang',
    dark: 'Gelap',
    system: 'Sistem',
};

const ACCENT_LABELS: Record<AccentStyle, string> = {
    single: 'Warna tunggal',
    dual: 'Dua warna',
    gradient: 'Gradien',
};

const PRESET_LABELS: Record<Preset, string> = {
    violet: 'Ungu',
    ember: 'Ember',
    forest: 'Hutan',
    ocean: 'Samudra',
    rose: 'Mawar',
    custom: 'Warna sendiri',
};

const SAVE_LABELS: Record<SaveStatus, string> = {
    idle: '',
    saving: 'Menyimpan',
    saved: 'Tersimpan',
    error: 'Gagal',
};

const OPTION_ACTIVE =
    'min-h-11 rounded-xl border border-primary bg-primary-container px-4 text-sm font-medium text-on-primary-container focus-visible:ring-2 focus-visible:ring-primary/40';
const OPTION_INACTIVE =
    'min-h-11 rounded-xl border border-outline-variant bg-surface px-4 text-sm font-medium text-on-surface hover:bg-surface-container focus-visible:ring-2 focus-visible:ring-primary/40';
const SAVE_BUTTON =
    'btn-primary disabled:cursor-not-allowed disabled:opacity-50';
const SECONDARY_BUTTON =
    'btn-secondary disabled:cursor-not-allowed disabled:opacity-50';

export function AppearanceSection({
    initialValue,
}: {
    initialValue: AppearanceSettings;
}): JSX.Element {
    const { setPreview, commit } = useTheme();
    const [draft, setDraft] = useState<AppearanceSettings>(initialValue);
    const [saved, setSaved] = useState<AppearanceSettings>(initialValue);
    const [status, setStatus] = useState<SaveStatus>('idle');
    const [errorMessage, setErrorMessage] = useState<string | null>(null);
    const preserveDraftOnError = useRef(false);
    const draftRef = useRef(draft);
    draftRef.current = draft;

    useEffect(() => {
        if (preserveDraftOnError.current) {
            preserveDraftOnError.current = false;
            setPreview(draftRef.current);
            return;
        }

        setDraft(initialValue);
        setSaved(initialValue);
        setPreview(initialValue);
    }, [initialValue, setPreview]);

    const preview = (value: AppearanceSettings): void => {
        const next = normalizeAppearance(value);
        preserveDraftOnError.current = false;
        setDraft(next);
        setPreview(next);
        setStatus('idle');
        setErrorMessage(null);
    };

    const save = (): void => {
        if (status === 'saving') return;

        preserveDraftOnError.current = false;
        setStatus('saving');
        setErrorMessage(null);
        router.patch('/settings/appearance', draft, {
            preserveScroll: true,
            onSuccess: () => {
                preserveDraftOnError.current = false;
                setSaved(draft);
                commit(draft);
                setStatus('saved');
            },
            onError: () => {
                preserveDraftOnError.current = true;
                setErrorMessage(
                    'Pengaturan tampilan gagal disimpan. Coba lagi.',
                );
                setStatus('error');
            },
            onHttpException: () => {
                preserveDraftOnError.current = false;
                setErrorMessage(
                    'Pengaturan tampilan gagal disimpan. Coba lagi.',
                );
                setStatus('error');
            },
            onNetworkError: () => {
                preserveDraftOnError.current = false;
                setErrorMessage('Tidak dapat terhubung. Coba lagi.');
                setStatus('error');
            },
            onCancel: () => {
                preserveDraftOnError.current = false;
                setErrorMessage('Penyimpanan tampilan dibatalkan.');
                setStatus('error');
            },
            onFinish: () => {
                setStatus((current) =>
                    current === 'saving' ? 'error' : current,
                );
            },
        });
    };

    const submit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        save();
    };

    const reset = (): void => {
        preserveDraftOnError.current = false;
        setDraft(saved);
        setPreview(saved);
        setStatus('idle');
        setErrorMessage(null);
    };

    const selectAccent = (accentStyle: AccentStyle): void => {
        preview({
            ...draft,
            accentStyle,
            secondaryColor:
                accentStyle === 'single'
                    ? null
                    : (draft.secondaryColor ?? draft.primaryColor),
        });
    };

    return (
        <section aria-labelledby="appearance-heading" className="space-y-6">
            <header className="space-y-1">
                <h2
                    id="appearance-heading"
                    className="text-xl font-bold text-on-surface"
                >
                    Tampilan
                </h2>
                <p className="text-sm text-on-surface-variant">
                    Pilih tampilan yang nyaman. Perubahan baru tersimpan setelah
                    kamu menekan Simpan.
                </p>
            </header>

            <form onSubmit={submit} className="space-y-6" noValidate>
                <fieldset className="space-y-3">
                    <legend className="text-sm font-semibold text-on-surface">
                        Mode
                    </legend>
                    <div className="grid gap-2 sm:grid-cols-3">
                        {MODES.map((mode) => (
                            <button
                                key={mode}
                                type="button"
                                aria-pressed={draft.mode === mode}
                                className={
                                    draft.mode === mode
                                        ? OPTION_ACTIVE
                                        : OPTION_INACTIVE
                                }
                                onClick={() => preview({ ...draft, mode })}
                            >
                                {MODE_LABELS[mode]}
                            </button>
                        ))}
                    </div>
                </fieldset>

                <fieldset className="space-y-3">
                    <legend className="text-sm font-semibold text-on-surface">
                        Gaya aksen
                    </legend>
                    <div className="grid gap-2 sm:grid-cols-3">
                        {ACCENT_STYLES.map((accentStyle) => (
                            <button
                                key={accentStyle}
                                type="button"
                                aria-pressed={draft.accentStyle === accentStyle}
                                className={
                                    draft.accentStyle === accentStyle
                                        ? OPTION_ACTIVE
                                        : OPTION_INACTIVE
                                }
                                onClick={() => selectAccent(accentStyle)}
                            >
                                {ACCENT_LABELS[accentStyle]}
                            </button>
                        ))}
                    </div>
                </fieldset>

                <fieldset className="space-y-3">
                    <legend className="text-sm font-semibold text-on-surface">
                        Palet warna
                    </legend>
                    <div className="grid gap-2 sm:grid-cols-3">
                        {PRESETS.map((preset) => (
                            <button
                                key={preset}
                                type="button"
                                aria-pressed={draft.preset === preset}
                                className={
                                    draft.preset === preset
                                        ? OPTION_ACTIVE
                                        : OPTION_INACTIVE
                                }
                                onClick={() => preview({ ...draft, preset })}
                            >
                                <span className="flex items-center gap-2">
                                    {preset === 'custom' ? null : (
                                        <span
                                            aria-hidden="true"
                                            className="h-5 w-5 rounded-full border border-outline"
                                            style={{
                                                backgroundColor:
                                                    PRESET_COLORS[preset][0],
                                            }}
                                        />
                                    )}
                                    {PRESET_LABELS[preset]}
                                </span>
                            </button>
                        ))}
                    </div>
                </fieldset>

                {draft.preset === 'custom' ? (
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                htmlFor="appearance-primary-color"
                                className="field-label"
                            >
                                Warna utama
                            </label>
                            <input
                                id="appearance-primary-color"
                                type="color"
                                value={draft.primaryColor}
                                aria-label="Warna utama"
                                aria-describedby="appearance-color-hint"
                                className="h-11 w-full cursor-pointer rounded-xl border border-outline bg-surface p-1"
                                onChange={(event) =>
                                    preview({
                                        ...draft,
                                        primaryColor: event.target.value,
                                    })
                                }
                            />
                        </div>
                        {draft.accentStyle !== 'single' ? (
                            <div>
                                <label
                                    htmlFor="appearance-secondary-color"
                                    className="field-label"
                                >
                                    Warna sekunder
                                </label>
                                <input
                                    id="appearance-secondary-color"
                                    type="color"
                                    value={
                                        draft.secondaryColor ??
                                        draft.primaryColor
                                    }
                                    aria-label="Warna sekunder"
                                    aria-describedby="appearance-color-hint"
                                    className="h-11 w-full cursor-pointer rounded-xl border border-outline bg-surface p-1"
                                    onChange={(event) =>
                                        preview({
                                            ...draft,
                                            secondaryColor: event.target.value,
                                        })
                                    }
                                />
                            </div>
                        ) : null}
                        <p
                            id="appearance-color-hint"
                            className="text-xs text-on-surface-variant sm:col-span-2"
                        >
                            Pilih warna utama dan, bila diperlukan, warna
                            sekunder untuk gaya dua warna atau gradien.
                        </p>
                    </div>
                ) : null}

                <div className="flex flex-wrap items-center gap-3 border-t border-outline-variant pt-4">
                    <button
                        type="submit"
                        disabled={status === 'saving'}
                        className={SAVE_BUTTON}
                    >
                        {status === 'saving' ? 'Menyimpan...' : 'Simpan'}
                    </button>
                    <button
                        type="button"
                        onClick={reset}
                        disabled={status === 'saving'}
                        className={SECONDARY_BUTTON}
                    >
                        Kembalikan ke tersimpan
                    </button>
                    <span
                        role="status"
                        aria-live="polite"
                        className="text-sm text-on-surface-variant"
                    >
                        {SAVE_LABELS[status]}
                    </span>
                </div>

                {errorMessage !== null ? (
                    <p
                        id="appearance-save-error"
                        role="alert"
                        className="text-sm text-error"
                    >
                        {errorMessage}
                    </p>
                ) : null}
                {status === 'error' ? (
                    <button
                        type="button"
                        onClick={save}
                        className={SECONDARY_BUTTON}
                    >
                        Coba lagi
                    </button>
                ) : null}
            </form>
        </section>
    );
}
