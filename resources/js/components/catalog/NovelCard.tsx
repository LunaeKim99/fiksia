import { Link } from '@inertiajs/react';
import novels from '@/routes/novels';
import type { NovelCard as NovelCardData } from '@/types';

export function NovelCardView({ novel }: { novel: NovelCardData }) {
    const coverUrl =
        novel.coverUrl ??
        (novel.coverAvailable ? novels.cover.url(novel.id) : null);

    return (
        <article className="flex h-full gap-4 rounded-2xl border border-outline-variant bg-surface-container p-4">
            <Link
                href={novels.show.url(novel.slug)}
                className="h-32 w-24 shrink-0 overflow-hidden rounded-xl bg-surface-container-high"
                aria-label={`Buka ${novel.title}`}
            >
                {coverUrl ? (
                    <img
                        src={coverUrl}
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

            <div className="flex min-w-0 flex-1 flex-col gap-2">
                <div className="flex items-start gap-2">
                    <Link
                        href={novels.show.url(novel.slug)}
                        className="line-clamp-2 font-semibold text-on-surface hover:text-primary"
                    >
                        {novel.title}
                    </Link>
                    <span className="ml-auto shrink-0 rounded-full bg-surface-container-high px-2 py-1 text-[0.65rem] text-on-surface-variant">
                        {novel.story_status_label}
                    </span>
                </div>
                <p className="truncate text-xs text-on-surface-variant">
                    {novel.author.name} · @{novel.author.username}
                </p>
                <p className="line-clamp-2 text-sm text-on-surface-variant">
                    {novel.synopsis || 'Sinopsis belum tersedia.'}
                </p>
                <div className="mt-auto flex flex-wrap items-center gap-1.5">
                    {novel.genres.slice(0, 2).map((genre) => (
                        <span
                            key={genre.id}
                            className="rounded-full bg-primary-container px-2 py-1 text-[0.65rem] text-on-primary-container"
                        >
                            {genre.name}
                        </span>
                    ))}
                    <span className="text-xs text-on-surface-variant">
                        {novel.chapterCount} Bab
                    </span>
                </div>
            </div>
        </article>
    );
}
