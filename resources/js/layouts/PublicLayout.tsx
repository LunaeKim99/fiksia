import type { ReactNode } from 'react';
import { Head } from '@inertiajs/react';
import { Toast } from '@/components/ui/Toast';
import { BottomNav, Sidebar, TopAppBar } from '@/components/Navigation';

type PublicLayoutProps = {
    children: ReactNode;
    title?: string;
};

export default function PublicLayout({ children, title }: PublicLayoutProps) {
    return (
        <>
            {title ? <Head title={title} /> : null}
            <a
                href="#main-content"
                className="sr-only z-[60] rounded-lg bg-primary px-4 py-2 text-on-primary focus:not-sr-only focus:fixed focus:top-4 focus:left-4"
            >
                Lompat ke konten
            </a>
            <TopAppBar />
            <Sidebar />
            <main
                id="main-content"
                className="min-h-screen bg-surface px-4 pt-[4.5rem] pb-[4.5rem] min-[840px]:pb-8 min-[840px]:pl-60"
            >
                <div className="mx-auto w-full max-w-4xl">{children}</div>
            </main>
            <BottomNav />
            <Toast />
        </>
    );
}
