import { useEffect, useRef, useState, type FormEvent, type JSX } from 'react';
import { router } from '@inertiajs/react';
import { UnavailableSection } from '@/components/settings/UnavailableSection';
import { normalizeReading } from '@/lib/theme';
import type { ReadingSettings } from '@/types/settings';

type SaveStatus = 'idle' | 'saving' | 'saved' | 'error';

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

const FONT_SIZE_LABELS: Record<ReadingSettings['fontSize'], string> = {
    small: 'Kecil',
    normal: 'Normal',
    large: 'Besar',
};

const FONT_FAMILY_LABELS: Record<ReadingSettings['fontFamily'], string> = {
    serif: 'Serif',
    sans: 'Sans',
};

const LINE_HEIGHT_LABELS: Record<ReadingSettings['lineHeight'], string> = {
    '1.5': 'Rapat',
    '1.7': 'Sedang',
    '2.0': 'Luas',
};

const COLUMN_WIDTH_LABELS: Record<ReadingSettings['columnWidth'], string> = {
    narrow: 'Sempit',
    normal: 'Normal',
    wide: 'Luas',
};

const SAVE_LABELS: Record<SaveStatus, string> = {
    idle: '',
    saving: 'Menyimpan',
    saved: 'Tersimpan',
    error: 'Gagal',
};

const SAVE_BUTTON =
    'btn-primary disabled:cursor-not-allowed disabled:opacity-50';
const SECONDARY_BUTTON =
    'btn-secondary disabled:cursor-not-allowed disabled:opacity-50';

export function ReadingSection({
    initialValue,
}: {
    initialValue: ReadingSettings;
}): JSX.Element {
    const [draft, setDraft] = useState<ReadingSettings>(initialValue);
    const [saved, setSaved] = useState<ReadingSettings>(initialValue);
    const [status, setStatus] = useState<SaveStatus>('idle');
    const [errorMessage, setErrorMessage] = useState<string | null>(null);
    const preserveDraftOnError = useRef(false);
    const draftRef = useRef(draft);
    draftRef.current = draft;

    useEffect(() => {
        if (preserveDraftOnError.current) {
            preserveDraftOnError.current = false;
            setDraft(draftRef.current);
            return;
        }

        const next = normalizeReading(initialValue);
        setDraft(next);
        setSaved(next);
    }, [initialValue]);

    const updateDraft = (value: ReadingSettings): void => {
        preserveDraftOnError.current = false;
        setDraft(normalizeReading(value));
        setStatus('idle');
        setErrorMessage(null);
    };

    const save = (): void => {
        if (status === 'saving') return;

        preserveDraftOnError.current = false;
        setStatus('saving');
        setErrorMessage(null);
        router.patch('/settings/reading', draft, {
            preserveScroll: true,
            onSuccess: () => {
                preserveDraftOnError.current = false;
                setSaved(draft);
                setStatus('saved');
            },
            onError: () => {
                preserveDraftOnError.current = true;
                setErrorMessage('Pengaturan bacaan gagal disimpan. Coba lagi.');
                setStatus('error');
            },
            onHttpException: () => {
                preserveDraftOnError.current = false;
                setErrorMessage('Pengaturan bacaan gagal disimpan. Coba lagi.');
                setStatus('error');
            },
            onNetworkError: () => {
                preserveDraftOnError.current = false;
                setErrorMessage('Tidak dapat terhubung. Coba lagi.');
                setStatus('error');
            },
            onCancel: () => {
                preserveDraftOnError.current = false;
                setErrorMessage('Penyimpanan bacaan dibatalkan.');
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
        setStatus('idle');
        setErrorMessage(null);
    };

    return (
        <section aria-labelledby="reading-heading" className="space-y-6">
            <header className="space-y-1">
                <h2
                    id="reading-heading"
                    className="text-xl font-bold text-on-surface"
                >
                    Membaca
                </h2>
                <p
                    id="reading-hint"
                    className="text-sm text-on-surface-variant"
                >
                    Atur tampilan baca agar lebih nyaman. Nilai baru digunakan
                    pada kunjungan berikutnya ke Reader.
                </p>
            </header>

            <form
                onSubmit={submit}
                aria-describedby="reading-hint"
                className="space-y-5"
                noValidate
            >
                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label
                            htmlFor="reading-font-size"
                            className="field-label"
                        >
                            Ukuran teks
                        </label>
                        <select
                            id="reading-font-size"
                            className="field-input"
                            value={draft.fontSize}
                            onChange={(event) =>
                                updateDraft({
                                    ...draft,
                                    fontSize: event.target
                                        .value as ReadingSettings['fontSize'],
                                })
                            }
                        >
                            {FONT_SIZES.map((value) => (
                                <option key={value} value={value}>
                                    {FONT_SIZE_LABELS[value]}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label
                            htmlFor="reading-font-family"
                            className="field-label"
                        >
                            Font
                        </label>
                        <select
                            id="reading-font-family"
                            className="field-input"
                            value={draft.fontFamily}
                            onChange={(event) =>
                                updateDraft({
                                    ...draft,
                                    fontFamily: event.target
                                        .value as ReadingSettings['fontFamily'],
                                })
                            }
                        >
                            {FONT_FAMILIES.map((value) => (
                                <option key={value} value={value}>
                                    {FONT_FAMILY_LABELS[value]}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label
                            htmlFor="reading-line-height"
                            className="field-label"
                        >
                            Spasi baris
                        </label>
                        <select
                            id="reading-line-height"
                            className="field-input"
                            value={draft.lineHeight}
                            onChange={(event) =>
                                updateDraft({
                                    ...draft,
                                    lineHeight: event.target
                                        .value as ReadingSettings['lineHeight'],
                                })
                            }
                        >
                            {LINE_HEIGHTS.map((value) => (
                                <option key={value} value={value}>
                                    {LINE_HEIGHT_LABELS[value]}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label
                            htmlFor="reading-column-width"
                            className="field-label"
                        >
                            Lebar konten
                        </label>
                        <select
                            id="reading-column-width"
                            className="field-input"
                            value={draft.columnWidth}
                            onChange={(event) =>
                                updateDraft({
                                    ...draft,
                                    columnWidth: event.target
                                        .value as ReadingSettings['columnWidth'],
                                })
                            }
                        >
                            {COLUMN_WIDTHS.map((value) => (
                                <option key={value} value={value}>
                                    {COLUMN_WIDTH_LABELS[value]}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

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
                    <p role="alert" className="text-sm text-error">
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

            <UnavailableSection
                title="Bahasa"
                description="Pilihan bahasa dan terjemahan direncanakan untuk pembaruan berikutnya."
            />
        </section>
    );
}
