import { cn } from '@/lib/utils';

type LoadingStateProps = {
    label?: string;
    className?: string;
};

export function LoadingState({
    label = 'Memuat…',
    className,
}: LoadingStateProps) {
    return (
        <div
            role="status"
            className={cn(
                'flex items-center justify-center gap-3 px-6 py-12',
                className,
            )}
        >
            <span
                aria-hidden="true"
                className="h-6 w-6 animate-spin rounded-full border-2 border-outline-variant border-t-primary"
            />
            <span className="text-sm text-on-surface-variant">{label}</span>
        </div>
    );
}
