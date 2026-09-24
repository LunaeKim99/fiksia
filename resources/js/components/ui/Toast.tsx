import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { cn } from '@/lib/utils';

export function Toast() {
    const { flash } = usePage().props;
    const [dismissed, setDismissed] = useState(false);

    const isError = flash.error !== null;
    const message = isError ? flash.error : flash.success;

    useEffect(() => {
        setDismissed(false);
    }, [message]);

    if (message === null || dismissed) return null;

    return (
        <div
            role={isError ? 'alert' : 'status'}
            className={cn(
                'fixed inset-x-4 bottom-20 z-50 flex items-center gap-3 rounded-2xl border px-4 py-3 shadow-lg min-[840px]:bottom-6 min-[840px]:left-auto min-[840px]:right-6 min-[840px]:max-w-sm',
                isError
                    ? 'border-error bg-surface text-error'
                    : 'border-outline-variant bg-surface-container text-on-surface',
            )}
        >
            <p className="flex-1 text-sm font-medium">{message}</p>
            <button
                type="button"
                onClick={() => setDismissed(true)}
                aria-label="Tutup notifikasi"
                className="btn-secondary min-h-11 min-w-11 rounded-full px-3"
            >
                ✕
            </button>
        </div>
    );
}
