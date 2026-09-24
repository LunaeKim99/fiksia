import { useCallback, useEffect, useRef, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/Button';
import PublicLayout from '@/layouts/PublicLayout';
import type { StudioChapterEditProps } from '../types';

function wordCount(text: string): number {
    return text.trim().split(/\s+/).filter(Boolean).length;
}

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
    const latestRef = useRef({ title, content });
    latestRef.current = { title, content };
    const initialRef = useRef({
        title: chapter.title,
        content: chapter.content ?? '',
    });

    const autosave = useCallback(() => {
        const { title: nextTitle, content: nextContent } = latestRef.current;

        if (
            nextTitle === initialRef.current.title &&
            nextContent === initialRef.current.content
        ) {
            setIsDirty(false);
            return;
        }

        setSaveError(null);
        setSaving(true);
        router.post(
            `/studio/chapters/${chapter.id}`,
            { title: nextTitle, content: nextContent },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setIsDirty(false);
                    initialRef.current = {
                        title: nextTitle,
                        content: nextContent,
                    };
                    setLastSavedAt(
                        new Date().toLocaleTimeString('id-ID', {
                            hour: '2-digit',
                            minute: '2-digit',
                        }),
                    );
                    setSaving(false);
                },
                onError: () => {
                    setSaveError('Gagal menyimpan');
                    setSaving(false);
                },
            },
        );
    }, [chapter.id]);

    const scheduleAutosave = useCallback(() => {
        if (timerRef.current !== null) {
            clearTimeout(timerRef.current);
        }
        timerRef.current = setTimeout(autosave, 2000);
    }, [autosave]);

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
        return () => {
            window.removeEventListener('beforeunload', warn);
            if (timerRef.current !== null) clearTimeout(timerRef.current);
        };
    }, [isDirty]);

    const saveDraft = () => {
        if (timerRef.current !== null) clearTimeout(timerRef.current);
        setSaveError(null);
        setSaving(true);
        router.post(
            `/studio/chapters/${chapter.id}`,
            { title, content },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setIsDirty(false);
                    initialRef.current = { title, content };
                    setLastSavedAt(
                        new Date().toLocaleTimeString('id-ID', {
                            hour: '2-digit',
                            minute: '2-digit',
                        }),
                    );
                    setSaving(false);
                },
                onError: () => {
                    setSaveError('Gagal menyimpan');
                    setSaving(false);
                },
            },
        );
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
