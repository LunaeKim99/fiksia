import { Link, usePage } from '@inertiajs/react';
import { isActive, navItems } from './NavItems';
import { cn } from '@/lib/utils';

export function BottomNav() {
    const { url } = usePage();

    return (
        <nav
            aria-label="Navigasi bawah"
            className="fixed inset-x-0 bottom-0 z-40 border-t border-outline-variant bg-surface min-[840px]:hidden"
        >
            <ul className="grid grid-cols-5">
                {navItems.map((item) => {
                    const active = isActive(item, url);
                    const Icon = item.icon;

                    return (
                        <li key={item.href}>
                            <Link
                                href={item.href}
                                aria-current={active ? 'page' : undefined}
                                className={cn(
                                    'flex min-h-[4.5rem] flex-col items-center justify-center gap-1 px-1 py-2 text-xs font-medium transition',
                                    active
                                        ? 'text-primary'
                                        : 'text-on-surface-variant',
                                )}
                            >
                                <Icon className="h-6 w-6" />
                                <span>{item.label}</span>
                                <span
                                    aria-hidden="true"
                                    className={cn(
                                        'h-1 w-8 rounded-full',
                                        active
                                            ? 'bg-primary'
                                            : 'bg-transparent',
                                    )}
                                />
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
