import { Head, Link } from '@inertiajs/react';
import { EmptyState } from '@/components/ui/EmptyState';
import PublicLayout from '@/layouts/PublicLayout';
import moderation from '@/routes/moderation';
import type { ModerationQueueProps } from '@/types';

function formatDate(iso: string | null): string {
    if (!iso) return '-';

    return new Date(iso).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

export default function ModerationQueue({ submissions }: ModerationQueueProps) {
    return (
        <PublicLayout title="Antrean Moderasi">
            <Head title="Antrean Moderasi" />
            <section className="space-y-6">
                <header className="space-y-2">
                    <h1 className="text-2xl font-bold text-on-surface">
                        Antrean Moderasi
                    </h1>
                    <p className="text-sm text-on-surface-variant">
                        Tinjau pengajuan awal novel yang dikirim penulis.
                    </p>
                </header>

                {submissions.data.length === 0 ? (
                    <EmptyState
                        title="Antrean kosong"
                        description="Belum ada pengajuan awal yang menunggu review."
                    />
                ) : (
                    <ul className="space-y-3">
                        {submissions.data.map((submission) => (
                            <li key={submission.id}>
                                <Link
                                    href={moderation.submissions.show.url(
                                        submission.id,
                                    )}
                                    className="block rounded-2xl border border-outline-variant bg-surface-container p-4 hover:bg-surface-container-high"
                                >
                                    <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div className="min-w-0">
                                            <h2 className="font-semibold text-on-surface">
                                                {submission.novel.title}
                                            </h2>
                                            <p className="mt-1 text-sm text-on-surface-variant">
                                                Oleh{' '}
                                                {submission.submittedBy.name}
                                            </p>
                                        </div>
                                        <span className="w-fit rounded-full bg-primary-container px-3 py-1 text-xs text-on-primary-container">
                                            {submission.statusLabel}
                                        </span>
                                    </div>
                                    <div className="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-on-surface-variant">
                                        <span>
                                            Dikirim{' '}
                                            {formatDate(submission.submittedAt)}
                                        </span>
                                        {submission.reviewNotes ? (
                                            <span>Catatan tersedia</span>
                                        ) : null}
                                    </div>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}

                {submissions.links.length > 0 ? (
                    <nav
                        aria-label="Halaman antrean moderasi"
                        className="flex flex-wrap gap-2"
                    >
                        {submissions.links.map((link, index) =>
                            link.url ? (
                                <Link
                                    key={`${link.label}-${index}`}
                                    href={link.url}
                                    className={`btn-secondary ${link.active ? 'ring-2 ring-primary' : ''}`}
                                >
                                    {link.label}
                                </Link>
                            ) : (
                                <span
                                    key={`${link.label}-${index}`}
                                    className="btn-secondary opacity-50"
                                    aria-disabled="true"
                                >
                                    {link.label}
                                </span>
                            ),
                        )}
                    </nav>
                ) : null}
            </section>
        </PublicLayout>
    );
}
