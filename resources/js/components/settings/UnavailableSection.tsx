import type { JSX } from 'react';

type UnavailableSectionProps = {
    title: string;
    description: string;
};

export function UnavailableSection({
    title,
    description,
}: UnavailableSectionProps): JSX.Element {
    return (
        <section
            aria-label={title}
            className="rounded-2xl border border-outline-variant bg-surface-container p-4"
        >
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 className="text-sm font-semibold text-on-surface">
                        {title}
                    </h3>
                    <p className="mt-1 text-sm text-on-surface-variant">
                        {description}
                    </p>
                </div>
                <span className="rounded-full bg-surface-container-high px-3 py-1 text-xs font-medium text-on-surface-variant">
                    Belum tersedia
                </span>
            </div>
        </section>
    );
}
