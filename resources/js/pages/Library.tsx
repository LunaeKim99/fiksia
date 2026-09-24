import { Link } from '@inertiajs/react';
import { NovelCardView } from '@/components/catalog/NovelCard';
import { EmptyState } from '@/components/ui/EmptyState';
import PublicLayout from '@/layouts/PublicLayout';
import novels from '@/routes/novels';
import { library } from '@/routes';
import type { LibraryProps, LibraryRow, LibraryTab } from '@/types';

const tabs: Array<{ value: LibraryTab; label: string }> = [
    { value: 'following', label: 'Diikuti' },
    { value: 'saved', label: 'Disimpan' },
    { value: 'reading', label: 'Sedang Dibaca' },
    { value: 'finished', label: 'Selesai' },
];

function formatDate(iso: string | null): string {
    if (!iso) return '-';

    return new Date(iso).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

function paginationLabel(label: string): string {
    return label
        .replace('&laquo;', '«')
        .replace('&raquo;', '»')
        .replace('Previous', 'Sebelumnya')
        .replace('Next', 'Berikutnya');
}

function ReadingRow({ novel }: { novel: LibraryRow }) {
    return (
        <article className="flex flex-col gap-3 rounded-2xl border border-outline-variant bg-surface-container p-4 sm:flex-row sm:items-center">
            <Link
                href={novels.show.url(novel.slug)}
                className="h-28 w-20 shrink-0 overflow-hidden rounded-xl bg-surface-container-high"
                aria-label={`Buka ${novel.title}`}
            >
                {novel.coverUrl ? (
                    <img
                        src={novel.coverUrl}
                        alt={`Sampul ${novel.title}`}
                        className="h-full w-full object-cover"
                        loading="lazy"
                    />
                ) : (
                    <span className="flex h-full w-full items-center justify-center text-center text-xs text-on-surface-variant">
                        Tanpa
                        <br />
                        Sampul
                    </span>
                )}
            </Link>
            <div className="min-w-0 flex-1">
                <Link
                    href={novels.show.url(novel.slug)}
                    className="font-semibold text-on-surface hover:text-primary"
                >
                    {novel.title}
                </Link>
                <p className="mt-1 text-xs text-on-surface-variant">
                    {novel.author.name} · @{novel.author.username}
                </p>
                <p className="mt-2 text-sm text-on-surface-variant">
                    Bab {novel.currentChapterNumber ?? '-'} · Terakhir dibaca{' '}
                    {formatDate(novel.lastReadAt)}
                </p>
                <div className="mt-3 flex flex-wrap gap-1.5">
                    {novel.genres.slice(0, 3).map((genre) => (
                        <span
                            key={genre.id}
                            className="rounded-full bg-primary-container px-2 py-1 text-xs text-on-primary-container"
                        >
                            {genre.name}
                        </span>
                    ))}
                    <span className="text-xs text-on-surface-variant">
                        {novel.chapterCount} Bab
                    </span>
                </div>
            </div>
            {novel.currentChapterNumber !== null ? (
                <Link
                    href={novels.chapters.show.url({
                        novel: novel.slug,
                        chapter: novel.currentChapterNumber,
                    })}
                    className="btn-secondary shrink-0 self-start sm:self-center"
                >
                    Lanjut Baca
                </Link>
            ) : null}
        </article>
    );
}

export default function Library({
    activeTab,
    counts,
    items,
    links,
    currentPage,
    lastPage,
}: LibraryProps) {
    const emptyCopy = {
        following: {
            title: 'Belum ada novel diikuti',
            description: 'Novel yang kamu ikuti akan muncul di sini.',
        },
        saved: {
            title: 'Belum ada novel disimpan',
            description: 'Novel yang kamu simpan akan muncul di sini.',
        },
        reading: {
            title: 'Belum ada bacaan berjalan',
            description: 'Novel yang sedang kamu baca akan muncul di sini.',
        },
        finished: {
            title: 'Belum ada novel selesai',
            description:
                'Novel selesai yang sudah kamu baca akan muncul di sini.',
        },
    }[activeTab];

    return (
        <PublicLayout title="Rak">
            <section className="space-y-6">
                <header className="space-y-2">
                    <h1 className="text-2xl font-bold text-on-surface">
                        Rak Saya
                    </h1>
                    <p className="text-sm text-on-surface-variant">
                        Novel yang kamu ikuti, simpan, dan baca.
                    </p>
                </header>

                <nav
                    aria-label="Tab Rak"
                    className="flex gap-2 overflow-x-auto border-b border-outline-variant pb-2"
                >
                    {tabs.map((tab) => (
                        <Link
                            key={tab.value}
                            href={library.url({ query: { tab: tab.value } })}
                            aria-current={
                                activeTab === tab.value ? 'page' : undefined
                            }
                            className={`flex min-h-11 shrink-0 items-center gap-2 rounded-full px-4 text-sm font-medium ${
                                activeTab === tab.value
                                    ? 'bg-primary-container text-on-primary-container'
                                    : 'text-on-surface-variant hover:bg-surface-container'
                            }`}
                        >
                            {tab.label}
                            <span className="rounded-full bg-surface-container-high px-2 py-0.5 text-xs">
                                {counts[tab.value]}
                            </span>
                        </Link>
                    ))}
                </nav>

                {items.length === 0 ? (
                    <EmptyState
                        title={emptyCopy.title}
                        description={emptyCopy.description}
                        action={
                            <Link href="/explore" className="btn-primary">
                                Jelajahi Novel
                            </Link>
                        }
                    />
                ) : activeTab === 'reading' || activeTab === 'finished' ? (
                    <div className="grid gap-3">
                        {items.map((novel) => (
                            <ReadingRow key={novel.id} novel={novel} />
                        ))}
                    </div>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2">
                        {items.map((novel) => (
                            <NovelCardView key={novel.id} novel={novel} />
                        ))}
                    </div>
                )}

                {links.length > 0 ? (
                    <div className="space-y-2">
                        <p className="text-center text-xs text-on-surface-variant">
                            Halaman {currentPage} dari {lastPage}
                        </p>
                        <nav
                            aria-label="Navigasi halaman Rak"
                            className="flex flex-wrap justify-center gap-2"
                        >
                            {links.map((link, index) =>
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
                    </div>
                ) : null}
            </section>
        </PublicLayout>
    );
}
