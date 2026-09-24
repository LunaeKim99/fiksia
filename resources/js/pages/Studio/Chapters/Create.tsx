import type { FormEvent } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/Button';
import PublicLayout from '@/layouts/PublicLayout';
import type { StudioChapterCreateProps } from '../types';

function wordCount(text: string): number {
    return text.trim().split(/\s+/).filter(Boolean).length;
}

export default function StudioChapterCreate({
    novel,
}: StudioChapterCreateProps) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        content: '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        post(`/studio/novels/${novel.id}/chapters`);
    };

    return (
        <PublicLayout title={`Bab Baru · ${novel.title}`}>
            <Head title={`Bab Baru · ${novel.title}`} />

            <section className="space-y-6">
                <div>
                    <Link
                        href={`/studio/novels/${novel.id}`}
                        className="text-sm text-on-surface-variant hover:text-primary"
                    >
                        &larr; Kembali ke {novel.title}
                    </Link>
                    <h1 className="mt-2 text-xl font-bold text-on-surface">
                        Tulis Bab
                    </h1>
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label htmlFor="title" className="field-label">
                            Judul Bab
                        </label>
                        <input
                            id="title"
                            type="text"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            className={`field-input ${
                                errors.title
                                    ? 'border-error focus:border-error focus:ring-error/30'
                                    : ''
                            }`}
                            required
                        />
                        {errors.title && (
                            <p className="field-error">{errors.title}</p>
                        )}
                    </div>

                    <div>
                        <label htmlFor="content" className="field-label">
                            Isi Bab
                        </label>
                        <textarea
                            id="content"
                            value={data.content}
                            onChange={(e) => setData('content', e.target.value)}
                            rows={14}
                            className={`field-input ${
                                errors.content
                                    ? 'border-error focus:border-error focus:ring-error/30'
                                    : ''
                            }`}
                        />
                        {errors.content && (
                            <p className="field-error">{errors.content}</p>
                        )}
                        <p className="mt-1 text-xs text-on-surface-variant">
                            {wordCount(data.content)} kata
                        </p>
                    </div>

                    <Button
                        type="submit"
                        variant="primary"
                        disabled={processing}
                        className="w-full"
                    >
                        {processing ? 'Memproses...' : 'Simpan Draft'}
                    </Button>
                </form>
            </section>
        </PublicLayout>
    );
}
