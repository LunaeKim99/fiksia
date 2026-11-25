import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/Button';
import PublicLayout from '@/layouts/PublicLayout';
import type { StudioChapterEditProps } from '../types';

const SAVE_FAILED_MESSAGE = 'Gagal menyimpan';
const MAX_FLUSH_ATTEMPTS = 3;

function wordCount(text: string): number {
    return text.trim().split(/\s+/).filter(Boolean).length;
}

function saveErrorMessage(status: number): string {
    if (status === 403) return 'Bab terkunci selama pengajuan direview.';
    if (status === 419) return 'Sesi berakhir. Muat ulang halaman.';

    return SAVE_FAILED_MESSAGE;
}

type ChapterDraft = {
    title: string;
    content: string;
};

export default function StudioChapterEdit({
    chapter,
    novel,
}: StudioChapterEditProps) {
    const [title, setTitle] = useState(chapter.title);
    const [content, setContent] = useState(chapter.content ?? '');
    const [isDirty, setIsDirty] = useState(false);
    const [lastSavedAt, setLastSavedAt] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);
    const [saveError, setSaveError] = useState<string | null>(null);

    const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const latestRef = useRef<ChapterDraft>({ title, content });
    latestRef.current = { title, content };
    const initialRef = useRef<ChapterDraft>({
        title: chapter.title,
        content: chapter.content ?? '',
    });
    const savePath = useMemo(
        () => `/studio/chapters/${chapter.id}`,
        [chapter.id],
    );
    const saveQueueRef = useRef<Promise<boolean>>(Promise.resolve(true));

    const clearTimer = useCallback(() => {
        if (timerRef.current !== null) {
            clearTimeout(timerRef.current);
            timerRef.current = null;
        }
    }, []);

    const hasUnsavedChanges = useCallback(
        () =>
            latestRef.current.title !== initialRef.current.title ||
            latestRef.current.content !== initialRef.current.content,
        [],
    );

    const markClean = useCallback(() => {
        setIsDirty(false);
    }, []);

    const doSave = useCallback(
        (values: ChapterDraft) =>
            new Promise<boolean>((resolve) => {
                let outcome: boolean | null = null;
                let settled = false;

                const settle = (saved: boolean) => {
                    if (settled) return;
                    settled = true;
                    resolve(saved);
                };

                const fail = (message: string) => {
                    setSaveError(message);
                    if (outcome === null) outcome = false;
                };

                setSaveError(null);
                setSaving(true);

                try {
                    router.post(
                        savePath,
                        { title: values.title, content: values.content },
                        {
                            preserveScroll: true,
                            preserveState: true,
                            onSuccess: (page) => {
                                if (outcome === false) return;

                                // Rejected drafts come back as a redirect with
                                // a flash error, so HTTP success alone is not
                                // enough to call this saved.
                                if (
                                    page.component !== 'Studio/Chapters/Edit' ||
                                    page.props.flash.error !== null
                                ) {
                                    fail(
                                        page.props.flash.error ??
                                            SAVE_FAILED_MESSAGE,
                                    );
                                    return;
                                }

                                initialRef.current = values;
                                setLastSavedAt(
                                    new Date().toLocaleTimeString('id-ID', {
                                        hour: '2-digit',
                                        minute: '2-digit',
                                    }),
                                );

                                if (
                                    latestRef.current.title === values.title &&
                                    latestRef.current.content === values.content
                                ) {
                                    markClean();
                                } else {
                                    setIsDirty(true);
                                }

                                outcome = true;
                            },
                            onError: () => fail(SAVE_FAILED_MESSAGE),
                            onHttpException: (response) => {
                                fail(saveErrorMessage(response.status));
                                return false;
                            },
                            onNetworkError: () => {
                                fail('Tidak dapat terhubung. Coba lagi.');
                                return false;
                            },
                            onCancel: () => fail(SAVE_FAILED_MESSAGE),
                            onFinish: () => {
                                setSaving(false);
                                settle(outcome === true);
                            },
                        },
                    );
                } catch {
                    fail(SAVE_FAILED_MESSAGE);
                    setSaving(false);
                    settle(false);
                }
            }),
        [markClean, savePath],
    );

    const enqueueSave = useCallback(
        (values: ChapterDraft): Promise<boolean> => {
            const run = () => doSave(values);
            const next = saveQueueRef.current.then(run, run).catch(() => false);
            saveQueueRef.current = next;
            return next;
        },
        [doSave],
    );

    const flushLatest = useCallback(async (): Promise<boolean> => {
        clearTimer();
        await saveQueueRef.current;

        // Retry a bounded number of times: a save may fail, or the author may
        // keep typing while it is in flight.
        for (let attempt = 0; attempt < MAX_FLUSH_ATTEMPTS; attempt += 1) {
            if (!hasUnsavedChanges()) {
                markClean();
                return true;
            }

            const saved = await enqueueSave({ ...latestRef.current });
            if (!saved) return false;
        }

        if (hasUnsavedChanges()) return false;

        markClean();
        return true;
    }, [clearTimer, enqueueSave, hasUnsavedChanges, markClean]);

    const autosave = useCallback(() => {
        timerRef.current = null;

        if (!hasUnsavedChanges()) {
            markClean();
            return;
        }

        // The queue is designed not to reject; this is defense in depth.
        void enqueueSave({ ...latestRef.current }).catch(() =>
            setSaveError(SAVE_FAILED_MESSAGE),
        );
    }, [enqueueSave, hasUnsavedChanges, markClean]);

    const scheduleAutosave = useCallback(() => {
        clearTimer();
        timerRef.current = setTimeout(autosave, 2000);
    }, [autosave, clearTimer]);

    const handleTitleChange = (value: string) => {
        setTitle(value);
        setIsDirty(true);
        scheduleAutosave();
    };

    const handleContentChange = (value: string) => {
        setContent(value);
        setIsDirty(true);
        scheduleAutosave();
    };

    useEffect(() => {
        const warn = (event: BeforeUnloadEvent) => {
            if (isDirty) event.preventDefault();
        };
        window.addEventListener('beforeunload', warn);
        return () => window.removeEventListener('beforeunload', warn);
    }, [isDirty]);

    // unmount-only: drop the pending debounce
    useEffect(() => clearTimer, [clearTimer]);

    useEffect(() => {
        const savePathNormalized = savePath.replace(/\/+$/, '');

        return router.on('before', (event) => {
            const visit = event.detail.visit;
            // Never intercept the save request itself: cancelling it before
            // Inertia creates the request would skip onFinish and wedge the
            // queue. Other POSTs (e.g. publish) are still flushed first.
            const isSaveRequest =
                visit.method.toLowerCase() === 'post' &&
                visit.url.pathname
                    .replace(/\/+$/, '')
                    .endsWith(savePathNormalized);

            if (isSaveRequest || visit.prefetch || !hasUnsavedChanges()) {
                return;
            }

            void flushLatest()
                .then((saved) => {
                    if (!saved) return;

                    router.visit(visit.url, {
                        method: visit.method,
                        data: visit.data,
                        preserveScroll: true,
                    });
                })
                // The queue is designed not to reject; guard the floating chain.
                .catch(() => setSaveError(SAVE_FAILED_MESSAGE));

            return false;
        });
    }, [flushLatest, hasUnsavedChanges, savePath]);

    const saveDraft = () => {
        void flushLatest().catch(() => setSaveError(SAVE_FAILED_MESSAGE));
    };

    const publish = () => {
        if (window.confirm('Terbitkan bab ini?')) {
            router.post(`/studio/chapters/${chapter.id}/publish`, undefined, {
                preserveScroll: true,
            });
        }
    };

    const canPublish =
        chapter.status === 'draft' && novel.status === 'published';

    return (
        <PublicLayout title={`Edit · ${chapter.title}`}>
            <Head title={`Edit · ${chapter.title}`} />

            <section className="space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <Link
                            href={`/studio/novels/${novel.id}`}
                            className="text-sm text-on-surface-variant hover:text-primary"
                        >
                            &larr; Kembali ke {novel.title}
                        </Link>
                        <h1 className="mt-2 text-xl font-bold text-on-surface">
                            Edit Bab
                        </h1>
                    </div>

                    <div className="flex items-center gap-3">
                        <span
                            className={`text-xs font-medium ${saveError ? 'text-error' : isDirty ? 'text-warning' : 'text-on-surface-variant'}`}
                            role={saveError ? 'alert' : 'status'}
                        >
                            {saveError
                                ? saveError
                                : saving
                                  ? 'Menyimpan…'
                                  : isDirty
                                    ? 'Perubahan belum tersimpan'
                                    : lastSavedAt
                                      ? `Tersimpan ${lastSavedAt}`
                                      : 'Semua tersimpan'}
                        </span>
                        {isDirty && (
                            <span className="rounded-full bg-warning/20 px-2.5 py-1 text-xs font-medium text-warning">
                                Perubahan belum tersimpan
                            </span>
                        )}
                    </div>
                </div>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        saveDraft();
                    }}
                    className="space-y-4"
                >
                    <div>
                        <label htmlFor="title" className="field-label">
                            Judul Bab
                        </label>
                        <input
                            id="title"
                            type="text"
                            value={title}
                            onChange={(event) =>
                                handleTitleChange(event.target.value)
                            }
                            className="field-input"
                            required
                        />
                    </div>

                    <div>
                        <label htmlFor="content" className="field-label">
                            Isi Bab
                        </label>
                        <textarea
                            id="content"
                            value={content}
                            onChange={(event) =>
                                handleContentChange(event.target.value)
                            }
                            rows={18}
                            className="field-input"
                        />
                        <p className="mt-1 text-xs text-on-surface-variant">
                            {wordCount(content)} kata
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-3">
                        <Button
                            type="submit"
                            variant="primary"
                            disabled={saving}
                        >
                            {saving ? 'Menyimpan…' : 'Simpan Draft'}
                        </Button>

                        {canPublish && (
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={publish}
                            >
                                Terbitkan
                            </Button>
                        )}
                    </div>
                </form>
            </section>
        </PublicLayout>
    );
}
