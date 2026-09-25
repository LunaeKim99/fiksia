import { Link, usePage } from '@inertiajs/react';
import { SearchIcon } from '@/components/icons';
import { SettingsIcon } from '@/components/Navigation/NavItems';

export function TopAppBar() {
    const page = usePage();
    const { auth } = page.props;
    const settingsActive =
        auth.user !== null &&
        (page.url === '/settings' || page.url.startsWith('/settings/'));

    return (
        <header className="fixed inset-x-0 top-0 z-50 flex h-14 items-center border-b border-outline-variant bg-surface px-4">
            <Link
                href="/"
                className="min-h-11 rounded-lg px-1 text-lg font-bold tracking-tight text-on-surface"
            >
                Fiksia
            </Link>
            <div className="flex-1" />
            {auth.user !== null ? (
                <Link
                    href="/settings"
                    aria-label="Pengaturan"
                    aria-current={settingsActive ? 'page' : undefined}
                    className="flex min-h-11 min-w-11 items-center justify-center rounded-full text-on-surface-variant hover:bg-surface-container focus-visible:ring-2 focus-visible:ring-primary/40"
                >
                    <SettingsIcon className="h-6 w-6" />
                </Link>
            ) : null}
            <Link
                href="/explore"
                aria-label="Cari novel"
                className="flex min-h-11 min-w-11 items-center justify-center rounded-full text-on-surface-variant hover:bg-surface-container focus-visible:ring-2 focus-visible:ring-primary/40"
            >
                <SearchIcon className="h-6 w-6" />
            </Link>
        </header>
    );
}
