import { useEffect, useState, type FormEvent } from 'react';
import { Link, router } from '@inertiajs/react';
import { NovelCardView } from '@/components/catalog/NovelCard';
import { EmptyState } from '@/components/ui/EmptyState';
import { SearchIcon } from '@/components/icons';
import { explore } from '@/routes';
import PublicLayout from '@/layouts/PublicLayout';
import type { ExploreProps } from '@/types';

const STATUS_OPTIONS = [
    { value: '', label: 'Semua' },
    { value: 'ongoing', label: 'Berlangsung' },
    { value: 'completed', label: 'Selesai' },
    { value: 'hiatus', label: 'Tunda' },
];

function exploreUrl(filters: ExploreProps['activeFilters']): string {
    return explore.url({
        query: {
            q: filters.q || undefined,
            genre: filters.genre || undefined,
            status: filters.status || undefined,
            sort: filters.sort === 'latest' ? undefined : filters.sort,
        },
    });
}

function paginationLabel(label: string): string {
    return label
        .replace('&laquo;', '«')
        .replace('&raquo;', '»')
        .replace('Previous', 'Sebelumnya')
        .replace('Next', 'Berikutnya');
}

export default function Explore({
    novels,
    genres,
    activeFilters,
    totalResults,
}: ExploreProps) {
    const [query, setQuery] = useState(activeFilters.q);

    useEffect(() => {
        setQuery(activeFilters.q);
    }, [activeFilters.q]);

    const submitSearch = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.get(
            exploreUrl({ ...activeFilters, q: query }),
            {},
            { preserveScroll: true },
        );
    };

    const changeSort = (sort: ExploreProps['activeFilters']['sort']) => {
        router.get(
            exploreUrl({ ...activeFilters, sort }),
            {},
            { preserveScroll: true },
        );
    };

    return (
        <PublicLayout title="Jelajahi">
            <section className="space-y-6">
                <header className="space-y-2">
                    <h1 className="text-2xl font-bold text-on-surface">
                        Jelajahi Novel
                    </h1>
                    <p className="text-sm text-on-surface-variant">
                        Cari cerita berdasarkan judul, penulis, genre, atau
                        status.
                    </p>
                </header>

                <form
                    role="search"
                    aria-label="Cari novel"
                    className="flex gap-2"
                    onSubmit={submitSearch}
                >
                    <label htmlFor="explore-q" className="sr-only">
                        Cari novel
                    </label>
                    <input
                        id="explore-q"
                        className="field-input flex-1"
                        placeholder="Cari judul, penulis, atau sinopsis…"
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        autoComplete="off"
                    />
                    <button
                        type="submit"
                        aria-label="Cari"
                        className="btn-primary min-h-11"
                    >
                        <SearchIcon className="h-5 w-5" />
                        <span className="hidden sm:inline">Cari</span>
                    </button>
                </form>

                <div className="space-y-4 rounded-2xl border border-outline-variant bg-surface-container p-4">
                    <div className="space-y-2">
                        <h2 className="text-sm font-semibold text-on-surface">
                            Genre
                        </h2>
                        <div className="flex flex-wrap gap-2">
                            <Link
                                href={exploreUrl({
                                    ...activeFilters,
                                    genre: '',
                                })}
                                preserveScroll
                                className={`rounded-full border px-3 py-1.5 text-sm ${
                                    activeFilters.genre === ''
                                        ? 'border-primary bg-primary-container text-on-primary-container'
                                        : 'border-outline-variant bg-surface text-on-surface-variant hover:bg-surface-container-high'
                                }`}
                            >
                                Semua
                            </Link>
                            {genres.map((genre) => (
                                <Link
                                    key={genre.id}
                                    href={exploreUrl({
                                        ...activeFilters,
                                        genre: genre.slug,
                                    })}
                                    preserveScroll
                                    className={`rounded-full border px-3 py-1.5 text-sm ${
                                        activeFilters.genre === genre.slug
                                            ? 'border-primary bg-primary-container text-on-primary-container'
                                            : 'border-outline-variant bg-surface text-on-surface-variant hover:bg-surface-container-high'
                                    }`}
                                >
                                    {genre.name}
                                </Link>
                            ))}
                        </div>
                    </div>

                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div className="space-y-2">
                            <h2 className="text-sm font-semibold text-on-surface">
                                Status
                            </h2>
                            <div className="flex flex-wrap gap-2">
                                {STATUS_OPTIONS.map((option) => (
                                    <Link
                                        key={option.value || 'all'}
                                        href={exploreUrl({
                                            ...activeFilters,
                                            status: option.value,
                                        })}
                                        preserveScroll
                                        className={`rounded-full border px-3 py-1.5 text-sm ${
                                            activeFilters.status ===
                                            option.value
                                                ? 'border-primary bg-primary-container text-on-primary-container'
                                                : 'border-outline-variant bg-surface text-on-surface-variant hover:bg-surface-container-high'
                                        }`}
                                    >
                                        {option.label}
                                    </Link>
                                ))}
                            </div>
                        </div>

                        <label
                            className="field-label min-w-44"
                            htmlFor="explore-sort"
                        >
                            Urutkan
                            <select
                                id="explore-sort"
                                className="field-input"
                                value={activeFilters.sort}
                                onChange={(event) =>
                                    changeSort(
                                        event.target
                                            .value as ExploreProps['activeFilters']['sort'],
                                    )
                                }
                            >
                                <option value="latest">Terbaru</option>
                                <option value="popular">Terpopuler</option>
                            </select>
                        </label>
                    </div>
                </div>

                <div className="flex items-center justify-between gap-3">
                    <p className="text-sm text-on-surface-variant">
                        {totalResults} novel ditemukan
                    </p>
                </div>

                {novels.data.length === 0 ? (
                    <EmptyState
                        title="Tidak ada hasil"
                        description="Coba ubah kata kunci atau hapus filter untuk melihat novel lainnya."
                        action={
                            <Link
                                href={explore.url()}
                                preserveScroll
                                className="btn-primary"
                            >
                                Hapus filter
                            </Link>
                        }
                    />
                ) : (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2">
                            {novels.data.map((novel) => (
                                <NovelCardView key={novel.id} novel={novel} />
                            ))}
                        </div>
                        {novels.links.length > 0 && (
                            <nav
                                aria-label="Navigasi halaman novel"
                                className="flex flex-wrap justify-center gap-2"
                            >
                                {novels.links.map((link, index) =>
                                    link.url ? (
                                        <Link
                                            key={`${link.label}-${index}`}
                                            href={link.url}
                                            preserveScroll
                                            aria-current={
                                                link.active ? 'page' : undefined
                                            }
                                            className={`min-h-11 rounded-full border px-4 py-2 text-sm ${
                                                link.active
                                                    ? 'border-primary bg-primary text-on-primary'
                                                    : 'border-outline-variant bg-surface text-on-surface hover:bg-surface-container'
                                            }`}
                                        >
                                            {paginationLabel(link.label)}
                                        </Link>
                                    ) : (
                                        <span
                                            key={`${link.label}-${index}`}
                                            aria-disabled="true"
                                            className="min-h-11 rounded-full border border-outline-variant bg-surface-container px-4 py-2 text-sm text-on-surface-variant opacity-50"
                                        >
                                            {paginationLabel(link.label)}
                                        </span>
                                    ),
                                )}
                            </nav>
                        )}
                    </>
                )}
            </section>
        </PublicLayout>
    );
}
