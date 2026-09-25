import { useEffect, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import novels from '@/routes/novels';
import chapters from '@/routes/novels/chapters';
import reader from '@/routes/reader';
import { useTheme } from '@/components/ThemeProvider';
import { DEFAULT_READING, normalizeReading } from '@/lib/theme';
import type { ReaderShowProps } from '@/types';
import type { ReadingSettings } from '@/types/settings';

const FONT_SIZE_CLASSES: Record<ReadingSettings['fontSize'], string> = {
    small: 'text-sm',
    normal: 'text-base',
    large: 'text-lg',
};

const FONT_FAMILY_CLASSES: Record<ReadingSettings['fontFamily'], string> = {
    serif: 'font-serif',
    sans: 'font-sans',
};

const LINE_HEIGHT_CLASSES: Record<ReadingSettings['lineHeight'], string> = {
    '1.5': 'leading-[1.5]',
    '1.7': 'leading-[1.7]',
    '2.0': 'leading-[2]',
};

const COLUMN_WIDTH_CLASSES: Record<ReadingSettings['columnWidth'], string> = {
    narrow: 'max-w-2xl',
    normal: 'max-w-3xl',
    wide: 'max-w-5xl',
};

function readGuestReading(): ReadingSettings {
    if (typeof window === 'undefined') return DEFAULT_READING;

    try {
        const stored = window.localStorage.getItem('fiksia-reader-prefs');
        if (stored === null) return DEFAULT_READING;
        return normalizeReading(JSON.parse(stored) as unknown);
    } catch {
        return DEFAULT_READING;
    }
}

function saveGuestReading(value: ReadingSettings): void {
    if (typeof window === 'undefined') return;

    try {
        window.localStorage.setItem(
            'fiksia-reader-prefs',
            JSON.stringify(value),
        );
    } catch {
        return;
    }
}

export default function ReaderShow({
    novel,
    chapter,
    prevChapter,
    nextChapter,
    chapterList,
    isOwner,
    isGuest,
    isBookmarked,
    readingSettings,
}: ReaderShowProps) {
    const { appearance } = useTheme();
    const [prefs, setPrefs] = useState<ReadingSettings>(
        () => readingSettings ?? readGuestReading(),
    );
    const [settingsOpen, setSettingsOpen] = useState(false);
    const [chaptersOpen, setChaptersOpen] = useState(false);
    const [bookmarkPending, setBookmarkPending] = useState(false);

    useEffect(() => {
        if (readingSettings !== null) {
            setPrefs(readingSettings);
        } else {
            setPrefs(readGuestReading());
        }
    }, [readingSettings]);

    useEffect(() => {
        if (isGuest) saveGuestReading(prefs);
    }, [isGuest, prefs]);

    useEffect(() => {
        if (isGuest) return;

        const options = {
            preserveScroll: true,
            keepAlive: true,
            onError: () => {},
            onHttpException: () => false,
        };

        router.post(
            reader.progress.url(),
            {
                novel_id: novel.id,
                chapter_id: chapter.id,
            },
            options,
        );
    }, [chapter.id, isGuest, novel.id]);

    const readerUrl = (chapterNumber: number) =>
        chapters.show.url({ novel: novel.slug, chapter: chapterNumber });
    const detailUrl = novels.show.url(novel.slug);
    const setReading = (next: ReadingSettings): void =>
        setPrefs(normalizeReading(next));

    return (
        <>
            <Head title={`${novel.title} — Bab ${chapter.chapter_number}`} />
            <div
                className="min-h-screen bg-surface text-on-surface"
                data-theme-mode={appearance.mode}
            >
                <header className="sticky top-0 z-30 border-b border-outline-variant bg-surface/95 px-4 py-3 backdrop-blur">
                    <div className="mx-auto flex max-w-5xl items-center gap-3">
                        <Link
                            href={detailUrl}
                            className="flex min-h-11 min-w-11 items-center justify-center rounded-full text-xl text-on-surface-variant hover:bg-surface-container"
                            aria-label="Kembali ke novel"
                        >
                            &larr;
                        </Link>
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-semibold text-on-surface">
                                {novel.title}
                            </p>
                            <p className="truncate text-xs text-on-surface-variant">
                                Bab {chapter.chapter_number} · {chapter.title}
                            </p>
                        </div>
                        {!isGuest ? (
                            <button
                                type="button"
                                className="min-h-11 rounded-full border border-outline px-3 text-sm text-on-surface hover:bg-surface-container"
                                aria-pressed={isBookmarked}
                                disabled={bookmarkPending}
                                onClick={() => {
                                    setBookmarkPending(true);
                                    router.post(
                                        isBookmarked
                                            ? reader.unbookmark.url()
                                            : reader.bookmark.url(),
                                        { chapter_id: chapter.id },
                                        {
                                            preserveScroll: true,
                                            onFinish: () =>
                                                setBookmarkPending(false),
                                        },
                                    );
                                }}
                            >
                                {bookmarkPending
                                    ? 'Memproses...'
                                    : isBookmarked
                                      ? 'Hapus Bookmark'
                                      : 'Bookmark'}
                            </button>
                        ) : null}
                        {isOwner ? (
                            <Link
                                href={`/studio/chapters/${chapter.id}/edit`}
                                className="hidden min-h-11 items-center rounded-full border border-outline px-3 text-sm text-on-surface hover:bg-surface-container sm:inline-flex"
                            >
                                Edit
                            </Link>
                        ) : null}
                        <button
                            type="button"
                            className="min-h-11 rounded-full border border-outline px-3 text-sm text-on-surface hover:bg-surface-container"
                            aria-expanded={chaptersOpen}
                            aria-controls="reader-chapter-list"
                            onClick={() => setChaptersOpen((open) => !open)}
                        >
                            Daftar Bab
                        </button>
                        <button
                            type="button"
                            className="min-h-11 rounded-full border border-outline px-3 text-sm text-on-surface hover:bg-surface-container"
                            aria-expanded={settingsOpen}
                            aria-controls="reader-settings"
                            onClick={() => setSettingsOpen((open) => !open)}
                        >
                            Atur
                        </button>
                    </div>

                    {chaptersOpen ? (
                        <aside
                            id="reader-chapter-list"
                            aria-label="Daftar bab"
                            className="mx-auto mt-3 max-h-64 max-w-5xl overflow-y-auto rounded-2xl border border-outline-variant bg-surface-container p-2"
                        >
                            {chapterList.map((item) => (
                                <Link
                                    key={item.id}
                                    href={readerUrl(item.chapter_number)}
                                    className={`flex min-h-11 items-center gap-3 rounded-xl px-3 py-2 text-sm ${
                                        item.chapter_number ===
                                        chapter.chapter_number
                                            ? 'bg-primary-container text-on-primary-container'
                                            : 'text-on-surface hover:bg-surface-container-high'
                                    }`}
                                    aria-current={
                                        item.chapter_number ===
                                        chapter.chapter_number
                                            ? 'page'
                                            : undefined
                                    }
                                >
                                    <span className="w-7 shrink-0 text-right text-xs text-on-surface-variant">
                                        {item.chapter_number}
                                    </span>
                                    <span className="truncate">
                                        {item.title}
                                    </span>
                                </Link>
                            ))}
                        </aside>
                    ) : null}

                    {settingsOpen ? (
                        <div
                            id="reader-settings"
                            role="region"
                            aria-label="Pengaturan baca"
                            className="mx-auto mt-3 grid max-w-5xl gap-3 rounded-2xl border border-outline-variant bg-surface-container p-3 sm:grid-cols-4"
                        >
                            <label
                                className="field-label"
                                htmlFor="reader-font-size"
                            >
                                Ukuran teks
                                <select
                                    id="reader-font-size"
                                    className="field-input"
                                    value={prefs.fontSize}
                                    onChange={(event) =>
                                        setReading({
                                            ...prefs,
                                            fontSize: event.target
                                                .value as ReadingSettings['fontSize'],
                                        })
                                    }
                                >
                                    <option value="small">Kecil</option>
                                    <option value="normal">Normal</option>
                                    <option value="large">Besar</option>
                                </select>
                            </label>
                            <label
                                className="field-label"
                                htmlFor="reader-font-family"
                            >
                                Font
                                <select
                                    id="reader-font-family"
                                    className="field-input"
                                    value={prefs.fontFamily}
                                    onChange={(event) =>
                                        setReading({
                                            ...prefs,
                                            fontFamily: event.target
                                                .value as ReadingSettings['fontFamily'],
                                        })
                                    }
                                >
                                    <option value="sans">Sans</option>
                                    <option value="serif">Serif</option>
                                </select>
                            </label>
                            <label
                                className="field-label"
                                htmlFor="reader-line-height"
                            >
                                Spasi baris
                                <select
                                    id="reader-line-height"
                                    className="field-input"
                                    value={prefs.lineHeight}
                                    onChange={(event) =>
                                        setReading({
                                            ...prefs,
                                            lineHeight: event.target
                                                .value as ReadingSettings['lineHeight'],
                                        })
                                    }
                                >
                                    <option value="1.5">Rapat</option>
                                    <option value="1.7">Sedang</option>
                                    <option value="2.0">Luang</option>
                                </select>
                            </label>
                            <label
                                className="field-label"
                                htmlFor="reader-column-width"
                            >
                                Lebar konten
                                <select
                                    id="reader-column-width"
                                    className="field-input"
                                    value={prefs.columnWidth}
                                    onChange={(event) =>
                                        setReading({
                                            ...prefs,
                                            columnWidth: event.target
                                                .value as ReadingSettings['columnWidth'],
                                        })
                                    }
                                >
                                    <option value="narrow">Sempit</option>
                                    <option value="normal">Normal</option>
                                    <option value="wide">Luas</option>
                                </select>
                            </label>
                        </div>
                    ) : null}
                </header>

                <main className="mx-auto w-full px-4 py-10 sm:px-6">
                    <article
                        className={`mx-auto ${COLUMN_WIDTH_CLASSES[prefs.columnWidth]} ${FONT_SIZE_CLASSES[prefs.fontSize]} ${FONT_FAMILY_CLASSES[prefs.fontFamily]} ${LINE_HEIGHT_CLASSES[prefs.lineHeight]}`}
                    >
                        <p className="text-sm font-medium text-on-surface-variant">
                            Bab {chapter.chapter_number}
                        </p>
                        <h1 className="mt-2 text-2xl font-bold text-on-surface sm:text-3xl">
                            {chapter.title}
                        </h1>
                        <p className="mt-8 whitespace-pre-wrap text-on-surface">
                            {chapter.content}
                        </p>
                    </article>

                    <nav
                        aria-label="Navigasi bab"
                        className="mx-auto mt-12 flex max-w-3xl items-center justify-between gap-3 border-t border-outline-variant pt-5"
                    >
                        {prevChapter !== null ? (
                            <Link
                                href={readerUrl(prevChapter)}
                                className="btn-secondary"
                            >
                                &larr; Bab sebelumnya
                            </Link>
                        ) : (
                            <span
                                className="btn-secondary opacity-50"
                                aria-disabled="true"
                            >
                                &larr; Bab sebelumnya
                            </span>
                        )}
                        {nextChapter !== null ? (
                            <Link
                                href={readerUrl(nextChapter)}
                                className="btn-primary"
                            >
                                Bab berikutnya &rarr;
                            </Link>
                        ) : (
                            <span
                                className="btn-primary opacity-50"
                                aria-disabled="true"
                            >
                                Selesai
                            </span>
                        )}
                    </nav>
                </main>
            </div>
        </>
    );
}
