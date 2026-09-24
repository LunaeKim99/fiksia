import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type EmptyStateProps = {
    title: string;
    description: string;
    action?: ReactNode;
    icon?: ReactNode;
    className?: string;
};

export function EmptyState({
    title,
    description,
    action,
    icon,
    className,
}: EmptyStateProps) {
    return (
        <section
            className={cn(
                'flex flex-col items-center gap-3 rounded-2xl border border-outline-variant bg-surface-container px-6 py-12 text-center',
                className,
            )}
        >
            {icon ? (
                <span aria-hidden="true" className="text-on-surface-variant">
                    {icon}
                </span>
            ) : null}
            <h2 className="text-lg font-semibold text-on-surface">{title}</h2>
            <p className="max-w-md text-sm text-on-surface-variant">
                {description}
            </p>
            {action ? (
                <div className="mt-2 flex flex-wrap items-center justify-center gap-3">
                    {action}
                </div>
            ) : null}
        </section>
    );
}
