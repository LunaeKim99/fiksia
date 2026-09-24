import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import PublicLayout from '@/layouts/PublicLayout';
import type { StudioNovelShowProps } from '../types';

const CHAPTER_LABELS: Record<string, string> = {
    draft: 'Draft',
    published: 'Terbit',
    hidden: 'Sembunyi',
    archived: 'Diarsipkan',
};

function formatDate(iso: string | null): string {
    if (!iso) return '-';
    return new Date(iso).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

export default function StudioNovelShow({
    novel,
    chapters,
}: StudioNovelShowProps) {
    const draftIds = chapters
        .filter((chapter) => chapter.status === 'draft')
        .map((chapter) => chapter.id);

    const canSubmit =
        (novel.status === 'draft' || novel.status === 'revision_required') &&
        draftIds.length > 0;

    const latestSubmission = novel.submissions[0] ?? null;
    const showReviewNotes =
        latestSubmission &&
        (novel.status === 'revision_required' || novel.status === 'rejected') &&
        latestSubmission.review_notes;

    const submit = () => {
        if (
            window.confirm(
                `Kirim pengajuan awal dengan ${draftIds.length} draft bab?`,
            )
        ) {
            router.post(`/studio/novels/${novel.id}/submit-review`, {
                chapter_ids: draftIds,
            });
        }
    };

    const publish = (chapterId: number) => {
        if (window.confirm('Terbitkan bab ini?')) {
            router.post(`/studio/chapters/${chapterId}/publish`);
        }
    };

    return (
        <PublicLayout title={novel.title}>
            <Head title={novel.title} />

            <section className="space-y-6">
                <div>
                    <Link
                        href="/studio"
                        className="text-sm text-on-surface-variant hover:text-primary"
                    >
                        &larr; Kembali ke Studio
                    </Link>

                    <div className="mt-4 flex gap-4">
                        <div className="h-36 w-24 shrink-0 overflow-hidden rounded-lg bg-surface-container-high">
                            {novel.cover_path ? (
                                <img
                                    src={`/novels/${novel.id}/cover`}
                                    alt={`Sampul ${novel.title}`}
                                    className="h-full w-full object-cover"
                                />
                            ) : (
                                <div className="flex h-full w-full items-center justify-center text-center text-xs text-on-surface-variant">
                                    Tanpa
                                    <br />
                                    Sampul
                                </div>
                            )}
                        </div>

                        <div className="flex min-w-0 flex-1 flex-col gap-1.5">
                            <h1 className="text-xl font-bold text-on-surface">
                                {novel.title}
                            </h1>
                            {novel.genres.length > 0 && (
                                <p className="text-sm text-on-surface-variant">
                                    {novel.genres.join(' · ')}
                                </p>
                            )}
                            {novel.synopsis && (
                                <p className="line-clamp-3 text-sm text-on-surface-variant">
                                    {novel.synopsis}
                                </p>
                            )}
                        </div>
                    </div>
                </div>

                {showReviewNotes && (
                    <div className="rounded-2xl border border-warning bg-warning/10 px-4 py-3">
                        <p className="text-sm font-medium text-on-surface">
                            Catatan reviewer
                        </p>
                        <p className="mt-1 text-sm text-on-surface">
                            {latestSubmission?.review_notes}
                        </p>
                    </div>
                )}

                <div className="flex flex-wrap items-center gap-3">
                    <Link
                        href={`/studio/novels/${novel.id}/chapters/create`}
                        className="btn-primary"
                    >
                        + Bab Baru
                    </Link>

                    <Button
                        type="button"
                        variant="secondary"
                        onClick={submit}
                        disabled={!canSubmit}
                    >
                        Kirim Pengajuan
                    </Button>
                </div>

                {chapters.length === 0 ? (
                    <EmptyState
                        title="Belum ada bab"
                        description="Tulis bab pertama untuk mulai. Draft tersimpan otomatis saat dibuat."
                        action={
                            <Link
                                href={`/studio/novels/${novel.id}/chapters/create`}
                                className="btn-primary"
                            >
                                Tulis Bab Pertama
                            </Link>
                        }
                    />
                ) : (
                    <ol className="space-y-2">
                        {chapters.map((chapter) => (
                            <li
                                key={chapter.id}
                                className="flex items-center gap-3 rounded-2xl border border-outline-variant bg-surface-container px-4 py-3"
                            >
                                <span className="w-10 shrink-0 text-sm font-bold text-on-surface-variant">
                                    {chapter.chapter_number}
                                </span>

                                <div className="flex min-w-0 flex-1 flex-col">
                                    <span className="truncate text-sm font-medium text-on-surface">
                                        {chapter.title}
                                    </span>
                                    <span className="text-xs text-on-surface-variant">
                                        {CHAPTER_LABELS[chapter.status] ??
                                            chapter.status}{' '}
                                        ·{' '}
                                        {chapter.published_at
                                            ? formatDate(chapter.published_at)
                                            : 'Belum terbit'}
                                    </span>
                                </div>

                                {chapter.status === 'draft' && (
                                    <Link
                                        href={`/studio/chapters/${chapter.id}/edit`}
                                        className="btn-secondary shrink-0"
                                    >
                                        Edit
                                    </Link>
                                )}

                                {chapter.status === 'draft' &&
                                    novel.status === 'published' && (
                                        <Button
                                            type="button"
                                            variant="primary"
                                            onClick={() => publish(chapter.id)}
                                            className="shrink-0"
                                        >
                                            Terbitkan
                                        </Button>
                                    )}
                            </li>
                        ))}
                    </ol>
                )}
            </section>
        </PublicLayout>
    );
}
