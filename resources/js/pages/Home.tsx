import { Link } from '@inertiajs/react';
import { NovelCardView } from '@/components/catalog/NovelCard';
import { EmptyState } from '@/components/ui/EmptyState';
import PublicLayout from '@/layouts/PublicLayout';
import novels from '@/routes/novels';
import type { HomeProps } from '@/types';

function formatDate(iso: string | null): string {
    if (!iso) return '-';

    return new Date(iso).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

export default function Home({ latestNovels, continueReading }: HomeProps) {
    return (
        <PublicLayout title="Beranda">
            <section className="space-y-6">
                <header className="space-y-2">
                    <h1 className="text-2xl font-bold text-on-surface">
                        Selamat datang di Fiksia
                    </h1>
                    <p className="text-sm text-on-surface-variant">
                        Temukan novel komunitas terbaru dan mulai membaca cerita
                        yang baru terbit.
                    </p>
                </header>

                {continueReading.length > 0 ? (
                    <section className="space-y-3">
                        <div className="flex items-center justify-between gap-3">
                            <h2 className="text-lg font-semibold text-on-surface">
                                Lanjut Membaca
                            </h2>
                            <Link
                                href="/library?tab=reading"
                                className="text-sm font-medium text-primary hover:opacity-80"
                            >
                                Lihat semua
                            </Link>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            {continueReading.map((novel) => (
                                <div key={novel.id} className="space-y-2">
                                    <NovelCardView novel={novel} />
                                    <div className="flex items-center justify-between gap-3 px-1 text-xs text-on-surface-variant">
                                        <span>
                                            Bab {novel.chapterNumber} · Dibaca{' '}
                                            {formatDate(novel.lastReadAt)}
                                        </span>
                                        <Link
                                            href={novels.chapters.show.url({
                                                novel: novel.slug,
                                                chapter: novel.chapterNumber,
                                            })}
                                            className="font-medium text-primary hover:opacity-80"
                                        >
                                            Lanjut
                                        </Link>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>
                ) : null}

                {latestNovels.length === 0 ? (
                    <EmptyState
                        title="Belum ada novel untuk ditampilkan"
                        description="Katalog masih kosong. Jelajahi novel yang akan datang, atau mulai menerbitkan cerita pertama di Studio."
                        action={
                            <>
                                <Link href="/explore" className="btn-primary">
                                    Jelajahi Novel
                                </Link>
                                <Link href="/studio" className="btn-secondary">
                                    Ke Studio
                                </Link>
                            </>
                        }
                    />
                ) : (
                    <>
                        <div className="flex items-center justify-between gap-3">
                            <h2 className="text-lg font-semibold text-on-surface">
                                Novel terbaru
                            </h2>
                            <Link
                                href="/explore"
                                className="text-sm font-medium text-primary hover:opacity-80"
                            >
                                Lihat semua
                            </Link>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            {latestNovels.map((novel) => (
                                <NovelCardView key={novel.id} novel={novel} />
                            ))}
                        </div>
                    </>
                )}
            </section>
        </PublicLayout>
    );
}
