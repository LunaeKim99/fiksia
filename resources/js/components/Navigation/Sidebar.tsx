import { Link, usePage } from '@inertiajs/react';
import { isActive, navItems } from './NavItems';
import { cn } from '@/lib/utils';

export function Sidebar() {
    const { url } = usePage();

    return (
        <aside
            aria-label="Navigasi samping"
            className="fixed inset-y-0 left-0 z-30 hidden w-60 flex-col border-r border-outline-variant bg-surface pt-14 min-[840px]:flex"
        >
            <nav className="flex flex-1 flex-col gap-1 px-3 py-4">
                {navItems.map((item) => {
                    const active = isActive(item, url);
                    const Icon = item.icon;

                    return (
                        <Link
                            key={item.href}
                            href={item.href}
                            aria-current={active ? 'page' : undefined}
                            className={cn(
                                'flex min-h-11 items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition',
                                active
                                    ? 'bg-primary-container text-on-primary-container'
                                    : 'text-on-surface-variant hover:bg-surface-container',
                            )}
                        >
                            <Icon className="h-6 w-6 shrink-0" />
                            <span>{item.label}</span>
                        </Link>
                    );
                })}
            </nav>
        </aside>
    );
}
