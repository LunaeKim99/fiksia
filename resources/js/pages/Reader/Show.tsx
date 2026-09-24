import { useEffect, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import novels from '@/routes/novels';
import chapters from '@/routes/novels/chapters';
import reader from '@/routes/reader';
import type { ReaderShowProps } from '@/types';

type ReaderPrefs = {
    fontSize: 'small' | 'normal' | 'large';
    lineHeight: '1.5' | '1.7' | '2.0';
    width: 'narrow' | 'normal';
};

const DEFAULT_PREFS: ReaderPrefs = {
    fontSize: 'normal',
    lineHeight: '1.7',
    width: 'normal',
};

const FONT_SIZE_CLASSES: Record<ReaderPrefs['fontSize'], string> = {
    small: 'text-sm',
    normal: 'text-base',
    large: 'text-lg',
};

const WIDTH_CLASSES: Record<ReaderPrefs['width'], string> = {
    narrow: 'max-w-2xl',
    normal: 'max-w-3xl',
};

function readPrefs(): ReaderPrefs {
    if (typeof window === 'undefined') return DEFAULT_PREFS;

    try {
        const stored = window.localStorage.getItem('fiksia-reader-prefs');

        if (!stored) return DEFAULT_PREFS;

        const parsed: unknown = JSON.parse(stored);

        if (typeof parsed !== 'object' || parsed === null) {
            return DEFAULT_PREFS;
        }

        const value = parsed as Record<string, unknown>;
        const fontSize = value.fontSize;

        return {
            fontSize:
                fontSize === 'small' || fontSize === 'large'
                    ? fontSize
                    : 'normal',
            lineHeight:
                value.lineHeight === '1.5' || value.lineHeight === '2.0'
                    ? value.lineHeight
                    : '1.7',
            width: value.width === 'narrow' ? 'narrow' : 'normal',
        };
    } catch {
        return DEFAULT_PREFS;
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
}: ReaderShowProps) {
    const [prefs, setPrefs] = useState<ReaderPrefs>(readPrefs);
    const [settingsOpen, setSettingsOpen] = useState(false);
    const [chaptersOpen, setChaptersOpen] = useState(false);
    const [bookmarkPending, setBookmarkPending] = useState(false);

    useEffect(() => {
        try {
            window.localStorage.setItem(
                'fiksia-reader-prefs',
                JSON.stringify(prefs),
            );
        } catch {
            return;
        }
    }, [prefs]);

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

    return (
        <>
            <Head title={`${novel.title} — Bab ${chapter.chapter_number}`} />
            <div className="min-h-screen bg-surface text-on-surface">
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
                            className="mx-auto mt-3 grid max-w-5xl gap-3 rounded-2xl border border-outline-variant bg-surface-container p-3 sm:grid-cols-3"
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
                                        setPrefs((current) => ({
                                            ...current,
                                            fontSize: event.target
                                                .value as ReaderPrefs['fontSize'],
                                        }))
                                    }
                                >
                                    <option value="small">Kecil</option>
                                    <option value="normal">Normal</option>
                                    <option value="large">Besar</option>
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
                                        setPrefs((current) => ({
                                            ...current,
                                            lineHeight: event.target
                                                .value as ReaderPrefs['lineHeight'],
                                        }))
                                    }
                                >
                                    <option value="1.5">Rapat</option>
                                    <option value="1.7">Sedang</option>
                                    <option value="2.0">Luang</option>
                                </select>
                            </label>
                            <label
                                className="field-label"
                                htmlFor="reader-width"
                            >
                                Lebar konten
                                <select
                                    id="reader-width"
                                    className="field-input"
                                    value={prefs.width}
                                    onChange={(event) =>
                                        setPrefs((current) => ({
                                            ...current,
                                            width: event.target
                                                .value as ReaderPrefs['width'],
                                        }))
                                    }
                                >
                                    <option value="narrow">Sempit</option>
                                    <option value="normal">Normal</option>
                                </select>
                            </label>
                        </div>
                    ) : null}
                </header>

                <main className="mx-auto w-full px-4 py-10 sm:px-6">
                    <article
                        className={`mx-auto ${WIDTH_CLASSES[prefs.width]} ${FONT_SIZE_CLASSES[prefs.fontSize]}`}
                        style={{ lineHeight: Number(prefs.lineHeight) }}
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
