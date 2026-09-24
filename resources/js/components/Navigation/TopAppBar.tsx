import { Link } from '@inertiajs/react';
import { SearchIcon } from '@/components/icons';

export function TopAppBar() {
    return (
        <header className="fixed inset-x-0 top-0 z-50 flex h-14 items-center border-b border-outline-variant bg-surface px-4">
            <Link
                href="/"
                className="min-h-11 rounded-lg px-1 text-lg font-bold tracking-tight text-on-surface"
            >
                Fiksia
            </Link>
            <div className="flex-1" />
            <Link
                href="/explore"
                aria-label="Cari novel"
                className="flex min-h-11 min-w-11 items-center justify-center rounded-full text-on-surface-variant hover:bg-surface-container"
            >
                <SearchIcon className="h-6 w-6" />
            </Link>
        </header>
    );
}
