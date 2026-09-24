import { Head, Link, useForm } from '@inertiajs/react';
import PublicLayout from '@/layouts/PublicLayout';
import moderation from '@/routes/moderation';
import type {
    ModerationDecision,
    ModerationSubmissionItem,
    ModerationSubmissionProps,
} from '@/types';

function formatDate(iso: string | null): string {
    if (!iso) return '-';

    return new Date(iso).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

function itemDescription(item: ModerationSubmissionItem): string {
    if (item.itemType === 'chapter') {
        return `Bab ${item.chapterNumber ?? '-'} · ${item.title ?? 'Tanpa judul'}`;
    }

    const snapshot = item.snapshot ?? {};
    const value = snapshot.synopsis ?? snapshot.cover_path ?? snapshot.title;

    return typeof value === 'string' && value !== ''
        ? value
        : 'Data review tersimpan.';
}

export default function ModerationSubmission({
    submission,
}: ModerationSubmissionProps) {
    const form = useForm<{
        decision: ModerationDecision;
        notes: string;
    }>({
        decision: 'approved',
        notes: '',
    });

    const submitDecision = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (!window.confirm('Finalisasi keputusan review ini?')) return;

        form.post(moderation.submissions.decide.url(submission.id), {
            preserveScroll: true,
        });
    };

    return (
        <PublicLayout title="Review Pengajuan">
            <Head title={`Review ${submission.novel.title}`} />
            <section className="space-y-6">
                <Link
                    href={moderation.queue.url()}
                    className="text-sm text-on-surface-variant hover:text-primary"
                >
                    &larr; Kembali ke antrean
                </Link>

                <header className="space-y-2">
                    <p className="text-sm text-on-surface-variant">
                        Review oleh {submission.author.name}
                    </p>
                    <h1 className="text-2xl font-bold text-on-surface">
                        {submission.novel.title}
                    </h1>
                    <div className="flex flex-wrap items-center gap-2 text-sm text-on-surface-variant">
                        <span className="rounded-full bg-primary-container px-3 py-1 text-xs text-on-primary-container">
                            {submission.statusLabel}
                        </span>
                        <span>
                            Dikirim {formatDate(submission.submittedAt)}
                        </span>
                        {submission.decidedAt ? (
                            <span>
                                · Diputuskan {formatDate(submission.decidedAt)}
                            </span>
                        ) : null}
                    </div>
                </header>

                <div className="grid gap-4 sm:grid-cols-[10rem_1fr]">
                    <div className="h-40 w-40 overflow-hidden rounded-2xl bg-surface-container-high">
                        {submission.novel.coverUrl ? (
                            <img
                                src={submission.novel.coverUrl}
                                alt={`Sampul ${submission.novel.title}`}
                                className="h-full w-full object-cover"
                            />
                        ) : (
                            <span className="flex h-full w-full items-center justify-center text-center text-xs text-on-surface-variant">
                                Tanpa
                                <br />
                                Sampul
                            </span>
                        )}
                    </div>
                    <div className="space-y-3">
                        <div>
                            <h2 className="font-semibold text-on-surface">
                                Metadata
                            </h2>
                            <p className="mt-1 text-sm text-on-surface-variant">
                                Slug: {submission.novel.slug}
                            </p>
                            <div className="mt-2 flex flex-wrap gap-1.5">
                                {submission.novel.genres.map((genre) => (
                                    <span
                                        key={genre.id}
                                        className="rounded-full bg-primary-container px-2 py-1 text-xs text-on-primary-container"
                                    >
                                        {genre.name}
                                    </span>
                                ))}
                            </div>
                        </div>
                        <div>
                            <h2 className="font-semibold text-on-surface">
                                Sinopsis
                            </h2>
                            <p className="mt-1 text-sm leading-6 whitespace-pre-wrap text-on-surface-variant">
                                {submission.novel.synopsis ||
                                    'Sinopsis belum tersedia.'}
                            </p>
                        </div>
                    </div>
                </div>

                <section className="space-y-3">
                    <h2 className="text-lg font-semibold text-on-surface">
                        Item pengajuan
                    </h2>
                    <ol className="space-y-2">
                        {submission.items.map((item) => (
                            <li
                                key={item.id}
                                className="rounded-2xl border border-outline-variant bg-surface-container p-4"
                            >
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <span className="font-medium text-on-surface">
                                        {item.label}
                                    </span>
                                    {item.chapterNumber !== null ? (
                                        <span className="text-xs text-on-surface-variant">
                                            Bab {item.chapterNumber}
                                        </span>
                                    ) : null}
                                </div>
                                <p className="mt-2 text-sm text-on-surface-variant">
                                    {itemDescription(item)}
                                </p>
                            </li>
                        ))}
                    </ol>
                </section>

                {submission.isDecided ? (
                    <section className="rounded-2xl border border-outline-variant bg-surface-container p-4">
                        <h2 className="font-semibold text-on-surface">
                            Keputusan tersimpan
                        </h2>
                        <p className="mt-2 text-sm text-on-surface-variant">
                            {submission.reviewNotes ||
                                'Tidak ada catatan reviewer.'}
                        </p>
                    </section>
                ) : (
                    <form
                        onSubmit={submitDecision}
                        className="space-y-4 rounded-2xl border border-outline-variant bg-surface-container p-4"
                    >
                        <div>
                            <h2 className="font-semibold text-on-surface">
                                Finalisasi keputusan
                            </h2>
                            <p className="mt-1 text-sm text-on-surface-variant">
                                Catatan wajib untuk revisi atau penolakan.
                            </p>
                        </div>
                        <fieldset className="space-y-2">
                            <legend className="field-label">Keputusan</legend>
                            <label className="flex min-h-11 items-center gap-2 rounded-xl px-3 hover:bg-surface-container-high">
                                <input
                                    type="radio"
                                    name="decision"
                                    value="approved"
                                    checked={form.data.decision === 'approved'}
                                    onChange={() =>
                                        form.setData('decision', 'approved')
                                    }
                                />
                                Setujui dan terbitkan
                            </label>
                            <label className="flex min-h-11 items-center gap-2 rounded-xl px-3 hover:bg-surface-container-high">
                                <input
                                    type="radio"
                                    name="decision"
                                    value="revision_required"
                                    checked={
                                        form.data.decision ===
                                        'revision_required'
                                    }
                                    onChange={() =>
                                        form.setData(
                                            'decision',
                                            'revision_required',
                                        )
                                    }
                                />
                                Minta revisi
                            </label>
                            <label className="flex min-h-11 items-center gap-2 rounded-xl px-3 hover:bg-surface-container-high">
                                <input
                                    type="radio"
                                    name="decision"
                                    value="rejected"
                                    checked={form.data.decision === 'rejected'}
                                    onChange={() =>
                                        form.setData('decision', 'rejected')
                                    }
                                />
                                Tolak
                            </label>
                        </fieldset>
                        {form.data.decision !== 'approved' ? (
                            <label
                                className="field-label"
                                htmlFor="review-notes"
                            >
                                Catatan reviewer
                                <textarea
                                    id="review-notes"
                                    className="field-input min-h-28"
                                    value={form.data.notes}
                                    onChange={(event) =>
                                        form.setData(
                                            'notes',
                                            event.target.value,
                                        )
                                    }
                                    aria-invalid={Boolean(form.errors.notes)}
                                    aria-describedby={
                                        form.errors.notes
                                            ? 'review-notes-error'
                                            : undefined
                                    }
                                />
                                {form.errors.notes ? (
                                    <span
                                        id="review-notes-error"
                                        className="text-error"
                                    >
                                        {form.errors.notes}
                                    </span>
                                ) : null}
                            </label>
                        ) : null}
                        {form.errors.decision ? (
                            <p className="text-error">{form.errors.decision}</p>
                        ) : null}
                        <button
                            type="submit"
                            className="btn-primary"
                            disabled={form.processing}
                        >
                            {form.processing
                                ? 'Menyimpan...'
                                : 'Simpan Keputusan'}
                        </button>
                    </form>
                )}
            </section>
        </PublicLayout>
    );
}
