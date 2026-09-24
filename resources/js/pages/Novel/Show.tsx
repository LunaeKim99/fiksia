import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { EmptyState } from '@/components/ui/EmptyState';
import novels from '@/routes/novels';
import chapters from '@/routes/novels/chapters';
import PublicLayout from '@/layouts/PublicLayout';
import type { NovelShowProps } from '@/types';

function formatDate(iso: string | null): string {
    if (!iso) return '-';

    return new Date(iso).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

export default function NovelShow({
    novel,
    isOwner,
    isFollowing,
    isSaved,
    readingProgress,
    latestChapterId,
}: NovelShowProps) {
    const [pending, setPending] = useState<'follow' | 'save' | null>(null);
    const coverUrl =
        novel.coverUrl ??
        (novel.coverAvailable ? novels.cover.url(novel.id) : null);
    const firstChapter = novel.chapters[0] ?? null;
    const readerUrl = (chapterNumber: number) =>
        chapters.show.url({ novel: novel.slug, chapter: chapterNumber });

    const submitInteraction = (type: 'follow' | 'save', url: string) => {
        setPending(type);
        router.post(
            url,
            {},
            {
                preserveScroll: true,
                onFinish: () => setPending(null),
            },
        );
    };

    return (
        <PublicLayout title={novel.title}>
            <section className="space-y-8">
                <Link
                    href="/explore"
                    className="text-sm text-on-surface-variant hover:text-primary"
                >
                    &larr; Kembali ke Jelajahi
                </Link>

                <header className="flex flex-col gap-6 sm:flex-row">
                    <div className="h-56 w-40 shrink-0 overflow-hidden rounded-2xl bg-surface-container-high">
                        {coverUrl ? (
                            <img
                                src={coverUrl}
                                alt={`Sampul ${novel.title}`}
                                className="h-full w-full object-cover"
                            />
                        ) : (
                            <div className="flex h-full w-full items-center justify-center text-center text-sm text-on-surface-variant">
                                Tanpa
                                <br />
                                Sampul
                            </div>
                        )}
                    </div>

                    <div className="flex min-w-0 flex-1 flex-col gap-4">
                        <div className="space-y-2">
                            <span className="inline-flex w-fit rounded-full bg-success/15 px-3 py-1 text-xs font-medium text-success">
                                {novel.story_status_label}
                            </span>
                            <h1 className="text-3xl font-bold text-on-surface">
                                {novel.title}
                            </h1>
                            <p className="text-sm text-on-surface-variant">
                                Oleh{' '}
                                <span className="font-medium text-on-surface">
                                    {novel.author.name}
                                </span>{' '}
                                · @{novel.author.username}
                            </p>
                            <p className="text-xs text-on-surface-variant">
                                Terbit {formatDate(novel.published_at)} ·{' '}
                                {novel.chapterCount} Bab
                            </p>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            {novel.genres.map((genre) => (
                                <Link
                                    key={genre.id}
                                    href={`/explore?genre=${encodeURIComponent(genre.slug)}`}
                                    className="rounded-full bg-primary-container px-3 py-1.5 text-sm text-on-primary-container hover:opacity-80"
                                >
                                    {genre.name}
                                </Link>
                            ))}
                        </div>

                        <div className="flex flex-wrap items-center gap-3">
                            {readingProgress ? (
                                <Link
                                    href={readerUrl(
                                        readingProgress.chapterNumber,
                                    )}
                                    className="btn-primary"
                                >
                                    Lanjut Baca
                                </Link>
                            ) : firstChapter ? (
                                <Link
                                    href={readerUrl(
                                        firstChapter.chapter_number,
                                    )}
                                    className="btn-primary"
                                >
                                    Mulai Baca
                                </Link>
                            ) : (
                                <span className="btn-primary opacity-50">
                                    Mulai Baca
                                </span>
                            )}
                            {readingProgress && firstChapter ? (
                                <Link
                                    href={readerUrl(
                                        firstChapter.chapter_number,
                                    )}
                                    className="btn-secondary"
                                >
                                    Mulai Baca
                                </Link>
                            ) : null}
                            <button
                                type="button"
                                className="btn-secondary"
                                aria-pressed={isFollowing}
                                disabled={pending !== null}
                                onClick={() =>
                                    submitInteraction(
                                        'follow',
                                        isFollowing
                                            ? novels.unfollow.url(novel.id)
                                            : novels.follow.url(novel.id),
                                    )
                                }
                            >
                                {pending === 'follow'
                                    ? 'Memproses...'
                                    : isFollowing
                                      ? 'Berhenti Ikuti'
                                      : 'Ikuti'}
                            </button>
                            <button
                                type="button"
                                className="btn-secondary"
                                aria-pressed={isSaved}
                                disabled={pending !== null}
                                onClick={() =>
                                    submitInteraction(
                                        'save',
                                        isSaved
                                            ? novels.unsave.url(novel.id)
                                            : novels.save.url(novel.id),
                                    )
                                }
                            >
                                {pending === 'save'
                                    ? 'Memproses...'
                                    : isSaved
                                      ? 'Batal Simpan'
                                      : 'Simpan'}
                            </button>
                            {isOwner ? (
                                <Link
                                    href={`/studio/novels/${novel.id}`}
                                    className="btn-secondary"
                                >
                                    Kelola di Studio
                                </Link>
                            ) : null}
                        </div>
                    </div>
                </header>

                <section className="space-y-3">
                    <h2 className="text-lg font-semibold text-on-surface">
                        Sinopsis
                    </h2>
                    <p className="max-w-3xl text-sm leading-7 whitespace-pre-wrap text-on-surface-variant">
                        {novel.synopsis || 'Sinopsis belum tersedia.'}
                    </p>
                </section>

                <section className="space-y-3">
                    <div className="flex items-center justify-between gap-3">
                        <h2 className="text-lg font-semibold text-on-surface">
                            Daftar Bab
                        </h2>
                        <span className="text-sm text-on-surface-variant">
                            {novel.chapterCount} bab terbit
                        </span>
                    </div>

                    {novel.chapters.length === 0 ? (
                        <EmptyState
                            title="Belum ada bab terbit"
                            description="Bab yang sudah terbit akan muncul di sini."
                        />
                    ) : (
                        <ol className="space-y-2">
                            {novel.chapters.map((chapter) => (
                                <li key={chapter.id}>
                                    <Link
                                        href={readerUrl(chapter.chapter_number)}
                                        className="flex min-h-16 items-center gap-3 rounded-2xl border border-outline-variant bg-surface-container px-4 py-3 hover:bg-surface-container-high"
                                    >
                                        <span className="w-10 shrink-0 text-sm font-bold text-on-surface-variant">
                                            {chapter.chapter_number}
                                        </span>
                                        <span className="min-w-0 flex-1">
                                            <span className="block truncate text-sm font-medium text-on-surface">
                                                {chapter.title}
                                            </span>
                                            <span className="mt-1 block text-xs text-on-surface-variant">
                                                Gratis ·{' '}
                                                {formatDate(
                                                    chapter.published_at,
                                                )}
                                            </span>
                                        </span>
                                        {chapter.id === latestChapterId ? (
                                            <span className="shrink-0 rounded-full bg-primary-container px-2 py-1 text-xs text-on-primary-container">
                                                Terbaru
                                            </span>
                                        ) : null}
                                    </Link>
                                </li>
                            ))}
                        </ol>
                    )}
                </section>
            </section>
        </PublicLayout>
    );
}
