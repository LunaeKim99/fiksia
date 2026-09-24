import { Link, router, usePage } from '@inertiajs/react';
import PublicLayout from '@/layouts/PublicLayout';
import { ThemeToggle } from '@/components/ThemeToggle';

export default function Account() {
    const { auth } = usePage().props;

    return (
        <PublicLayout title="Akun">
            <section className="space-y-6">
                <h1 className="text-xl font-bold text-on-surface">Akun</h1>
                {auth.user ? (
                    <section className="rounded-2xl border border-outline-variant bg-surface-container p-4">
                        <h2 className="mb-1 text-sm font-semibold text-on-surface">
                            Halo, {auth.user.name}
                        </h2>
                        <p className="mb-4 text-sm text-on-surface-variant">
                            Anda masuk sebagai {auth.user.email}.
                        </p>
                        <button
                            type="button"
                            onClick={() => router.post('/logout')}
                            className="btn-secondary"
                        >
                            Keluar
                        </button>
                    </section>
                ) : (
                    <section className="rounded-2xl border border-outline-variant bg-surface-container p-4">
                        <h2 className="mb-1 text-sm font-semibold text-on-surface">
                            Masuk untuk mengelola akun
                        </h2>
                        <p className="mb-4 text-sm text-on-surface-variant">
                            Simpan bacaan dan atur preferensi Anda.
                        </p>
                        <Link href="/login" className="btn-primary">
                            Masuk
                        </Link>
                    </section>
                )}
                <section
                    aria-label="Tampilan"
                    className="rounded-2xl border border-outline-variant bg-surface-container p-4"
                >
                    <h2 className="mb-1 text-sm font-semibold text-on-surface">
                        Tampilan
                    </h2>
                    <p className="mb-3 text-sm text-on-surface-variant">
                        Pilih tema tampilan yang nyaman bagi mata.
                    </p>
                    <ThemeToggle />
                </section>
            </section>
        </PublicLayout>
    );
}
