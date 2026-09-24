import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/Button';
import PublicLayout from '@/layouts/PublicLayout';

type GenreOption = {
    id: number;
    name: string;
};

type CreateProps = {
    genres: GenreOption[];
};

const STORY_STATUS_OPTIONS = [
    { value: 'ongoing', label: 'Berlangsung' },
    { value: 'completed', label: 'Selesai' },
    { value: 'hiatus', label: 'Tunda' },
];

export default function StudioNovelCreate({ genres }: CreateProps) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        synopsis: '',
        story_status: 'ongoing',
        cover: null as File | null,
        genreIds: [] as number[],
    });

    const [coverPreview, setCoverPreview] = useState<string | null>(null);

    useEffect(() => {
        return () => {
            if (coverPreview) URL.revokeObjectURL(coverPreview);
        };
    }, [coverPreview]);

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        post('/studio/novels', { forceFormData: true });
    };

    const toggleGenre = (id: number) => {
        setData(
            'genreIds',
            data.genreIds.includes(id)
                ? data.genreIds.filter((genreId) => genreId !== id)
                : [...data.genreIds, id],
        );
    };

    return (
        <PublicLayout title="Buat Novel">
            <Head title="Buat Novel" />

            <section className="space-y-6">
                <div>
                    <Link
                        href="/studio"
                        className="text-sm text-on-surface-variant hover:text-primary"
                    >
                        &larr; Kembali ke Studio
                    </Link>
                    <h1 className="mt-2 text-xl font-bold text-on-surface">
                        Buat Novel
                    </h1>
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label htmlFor="title" className="field-label">
                            Judul
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
                        <label htmlFor="synopsis" className="field-label">
                            Sinopsis
                        </label>
                        <textarea
                            id="synopsis"
                            value={data.synopsis}
                            onChange={(e) =>
                                setData('synopsis', e.target.value)
                            }
                            rows={5}
                            className={`field-input ${
                                errors.synopsis
                                    ? 'border-error focus:border-error focus:ring-error/30'
                                    : ''
                            }`}
                        />
                        {errors.synopsis && (
                            <p className="field-error">{errors.synopsis}</p>
                        )}
                    </div>

                    <div>
                        <label htmlFor="story_status" className="field-label">
                            Status Cerita
                        </label>
                        <select
                            id="story_status"
                            value={data.story_status}
                            onChange={(e) =>
                                setData('story_status', e.target.value)
                            }
                            className="field-input"
                        >
                            {STORY_STATUS_OPTIONS.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label htmlFor="cover" className="field-label">
                            Sampul (opsional)
                        </label>
                        <input
                            id="cover"
                            type="file"
                            accept=".jpg,.jpeg,.png,.gif,.webp"
                            onChange={(e) => {
                                const file = e.target.files?.[0] ?? null;
                                if (coverPreview) {
                                    URL.revokeObjectURL(coverPreview);
                                }
                                setData('cover', file);
                                setCoverPreview(
                                    file ? URL.createObjectURL(file) : null,
                                );
                            }}
                            className="field-input"
                        />
                        {errors.cover && (
                            <p className="field-error">{errors.cover}</p>
                        )}
                        <p className="mt-1 text-xs text-on-surface-variant">
                            JPG, PNG, GIF, atau WebP. Maksimal 2 MB.
                        </p>
                        {coverPreview && (
                            <img
                                src={coverPreview}
                                alt="Pratinjau sampul"
                                className="mt-3 h-40 rounded-lg object-cover"
                            />
                        )}
                    </div>

                    <fieldset>
                        <legend className="field-label">Genre</legend>
                        <div className="flex flex-wrap gap-2">
                            {genres.map((genre) => (
                                <label
                                    key={genre.id}
                                    className="flex cursor-pointer items-center gap-2 rounded-full border border-outline px-3 py-1.5 text-sm text-on-surface"
                                >
                                    <input
                                        type="checkbox"
                                        checked={data.genreIds.includes(
                                            genre.id,
                                        )}
                                        onChange={() => toggleGenre(genre.id)}
                                        className="h-4 w-4 rounded border-outline accent-primary"
                                    />
                                    {genre.name}
                                </label>
                            ))}
                        </div>
                        {errors.genreIds && (
                            <p className="field-error">{errors.genreIds}</p>
                        )}
                    </fieldset>

                    <Button
                        type="submit"
                        variant="primary"
                        disabled={processing}
                        className="w-full"
                    >
                        {processing ? 'Memproses...' : 'Buat Novel'}
                    </Button>
                </form>
            </section>
        </PublicLayout>
    );
}
