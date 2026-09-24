import { Head, Link } from '@inertiajs/react';
import { EmptyState } from '@/components/ui/EmptyState';
import PublicLayout from '@/layouts/PublicLayout';
import type { StudioIndexProps, StudioNovel } from './types';

const STATUS_LABELS: Record<string, string> = {
    draft: 'Draft',
    initial_review: 'Pengajuan',
    published: 'Terbit',
    revision_required: 'Revisi Diperlukan',
    rejected: 'Ditolak',
    suspended: 'Ditangguhkan',
    archived: 'Diarsipkan',
};

const STATUS_STYLES: Record<string, string> = {
    draft: 'bg-surface-container-high text-on-surface-variant',
    initial_review: 'bg-primary-container text-on-primary-container',
    published: 'bg-success/20 text-success',
    revision_required: 'bg-warning/20 text-warning',
    rejected: 'bg-error/20 text-error',
    suspended: 'bg-error/20 text-error',
    archived: 'bg-surface-container-high text-on-surface-variant',
};

const SUBMISSION_LABELS: Record<string, string> = {
    submitted: 'Menunggu Review',
    reviewing: 'Sedang Direview',
    approved: 'Disetujui',
    revision_required: 'Revisi Diperlukan',
    rejected: 'Ditolak',
};

function StatusBadge({ status }: { status: string }) {
    return (
        <span
            className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ${STATUS_STYLES[status] ?? STATUS_STYLES.draft}`}
        >
            {STATUS_LABELS[status] ?? status}
        </span>
    );
}

function formatDate(iso: string | null): string {
    if (!iso) return '-';
    return new Date(iso).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function NovelCard({ novel }: { novel: StudioNovel }) {
    const coverUrl = novel.cover_path ? `/novels/${novel.id}/cover` : null;
    const latest = novel.latest_submission;

    return (
        <article className="flex gap-4 rounded-2xl border border-outline-variant bg-surface-container p-4">
            <div className="h-28 w-20 shrink-0 overflow-hidden rounded-lg bg-surface-container-high">
                {coverUrl ? (
                    <img
                        src={coverUrl}
                        alt={`Sampul ${novel.title}`}
                        className="h-full w-full object-cover"
                    />
                ) : (
                    <div className="flex h-full w-full items-center justify-center text-xs text-on-surface-variant">
                        Tanpa
                        <br />
                        Sampul
                    </div>
                )}
            </div>

            <div className="flex min-w-0 flex-1 flex-col gap-2">
                <div className="flex items-start justify-between gap-2">
                    <Link
                        href={`/studio/novels/${novel.id}`}
                        className="truncate text-base font-semibold text-on-surface hover:text-primary"
                    >
                        {novel.title}
                    </Link>
                    <StatusBadge status={novel.status} />
                </div>

                {novel.genres.length > 0 && (
                    <p className="truncate text-xs text-on-surface-variant">
                        {novel.genres.join(' · ')}
                    </p>
                )}

                <p className="text-sm text-on-surface-variant">
                    {novel.chapter_count} Bab · {novel.draft_count} Draft
                </p>

                {latest && (
                    <div className="mt-auto space-y-0.5 text-xs text-on-surface-variant">
                        <p className="font-medium text-on-surface">
                            {SUBMISSION_LABELS[latest.status] ?? latest.status}
                        </p>
                        {latest.review_notes && (
                            <p className="line-clamp-2 italic">
                                &ldquo;{latest.review_notes}&rdquo;
                            </p>
                        )}
                        <p>Diputuskan: {formatDate(latest.decided_at)}</p>
                    </div>
                )}
            </div>
        </article>
    );
}

export default function StudioIndex({ novels, isGuest }: StudioIndexProps) {
    return (
        <PublicLayout title="Studio">
            <Head title="Studio" />

            <section className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-bold text-on-surface">
                            Studio Penulis
                        </h1>
                        <p className="text-sm text-on-surface-variant">
                            Tulis novel, kelola draft, dan kirim pengajuan awal.
                        </p>
                    </div>

                    {!isGuest && (
                        <Link
                            href="/studio/novels/create"
                            className="btn-primary"
                        >
                            Buat Novel
                        </Link>
                    )}
                </div>

                {isGuest ? (
                    <EmptyState
                        title="Masuk untuk mulai menulis"
                        description="Studio Penulis tersedia setelah Anda masuk ke akun. Buat novel pertama dan kelola draft bab di sini."
                        action={
                            <>
                                <Link href="/login" className="btn-primary">
                                    Masuk
                                </Link>
                                <Link
                                    href="/register"
                                    className="btn-secondary"
                                >
                                    Daftar
                                </Link>
                            </>
                        }
                    />
                ) : novels.length === 0 ? (
                    <EmptyState
                        title="Belum ada novel"
                        description="Buat novel pertama untuk memulai. Tulis bab, kelola draft, dan kirim pengajuan awal untuk direview."
                        action={
                            <Link
                                href="/studio/novels/create"
                                className="btn-primary"
                            >
                                Buat Novel
                            </Link>
                        }
                    />
                ) : (
                    <div className="space-y-4">
                        {novels.map((novel) => (
                            <NovelCard key={novel.id} novel={novel} />
                        ))}
                    </div>
                )}
            </section>
        </PublicLayout>
    );
}
