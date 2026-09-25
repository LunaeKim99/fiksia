import type { ComponentType, SVGProps } from 'react';

import { SearchIcon } from '@/components/icons';

export type NavItem = {
    label: string;
    href: string;
    icon: ComponentType<SVGProps<SVGSVGElement>>;
};

export function isActive(item: NavItem, url: string): boolean {
    if (item.href === '/') return url === '/';
    return url === item.href || url.startsWith(`${item.href}/`);
}

function HomeIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.8}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            {...props}
        >
            <path d="M3 10.5 12 3l9 7.5" />
            <path d="M5 9.5V21h5v-6h4v6h5V9.5" />
        </svg>
    );
}

function LibraryIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.8}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            {...props}
        >
            <path d="M12 5.5A5.5 5.5 0 0 0 6.5 4 5.5 5.5 0 0 0 2 6.5v12A5.5 5.5 0 0 1 6.5 16 5.5 5.5 0 0 1 12 17.5M12 5.5A5.5 5.5 0 0 1 17.5 4 5.5 5.5 0 0 1 22 6.5v12a5.5 5.5 0 0 0-4.5-2.5 5.5 5.5 0 0 0-5.5 1.5M12 5.5v12" />
        </svg>
    );
}

function StudioIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.8}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            {...props}
        >
            <path d="M13 5.5 18.5 11 8 21.5H2.5V16L13 5.5Z" />
            <path d="m12 7 5 5" />
            <path d="M17.5 2.5 21.5 6.5 19.5 8.5 15.5 4.5 17.5 2.5Z" />
        </svg>
    );
}

export function SettingsIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.8}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            {...props}
        >
            <path d="M12 3.5v2M12 18.5v2M3.5 12h2M18.5 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M18.4 5.6 17 7M7 17l-1.4 1.4" />
            <circle cx="12" cy="12" r="4" />
        </svg>
    );
}

function AccountIcon(props: SVGProps<SVGSVGElement>) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.8}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            {...props}
        >
            <circle cx="12" cy="8" r="4" />
            <path d="M4 21c1-4 4-6 8-6s7 2 8 6" />
        </svg>
    );
}

export const navItems: NavItem[] = [
    { label: 'Beranda', href: '/', icon: HomeIcon },
    { label: 'Jelajahi', href: '/explore', icon: SearchIcon },
    { label: 'Rak', href: '/library', icon: LibraryIcon },
    { label: 'Studio', href: '/studio', icon: StudioIcon },
    { label: 'Akun', href: '/account', icon: AccountIcon },
];
