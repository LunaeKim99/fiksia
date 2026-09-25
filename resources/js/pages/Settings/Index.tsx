import { Link, usePage } from '@inertiajs/react';
import { AppearanceSection } from '@/components/settings/AppearanceSection';
import { ReadingSection } from '@/components/settings/ReadingSection';
import { AccountSection } from '@/components/settings/AccountSection';
import { UnavailableSection } from '@/components/settings/UnavailableSection';
import PublicLayout from '@/layouts/PublicLayout';
import type { SettingsSnapshot } from '@/types/settings';

type SettingsSectionKey =
    | 'appearance'
    | 'reading'
    | 'privacy'
    | 'notifications'
    | 'account';

export type SettingsProps = {
    settings: SettingsSnapshot;
    account: {
        name: string;
        email: string;
        googleConnected: boolean;
        hasPassword: boolean;
    };
};

const SECTIONS: readonly {
    key: SettingsSectionKey;
    label: string;
    description: string;
}[] = [
    { key: 'appearance', label: 'Tampilan', description: 'Mode dan warna' },
    { key: 'reading', label: 'Membaca', description: 'Preferensi baca' },
    { key: 'privacy', label: 'Privasi', description: 'Belum tersedia' },
    {
        key: 'notifications',
        label: 'Notifikasi',
        description: 'Belum tersedia',
    },
    { key: 'account', label: 'Akun', description: 'Nama dan masuk' },
];

const SECTION_ACTIVE =
    'flex min-h-11 flex-col justify-center rounded-xl border border-primary bg-primary-container px-3 py-2 text-sm text-on-primary-container focus-visible:ring-2 focus-visible:ring-primary/40';
const SECTION_INACTIVE =
    'flex min-h-11 flex-col justify-center rounded-xl border border-outline-variant bg-surface px-3 py-2 text-sm text-on-surface-variant hover:bg-surface-container focus-visible:ring-2 focus-visible:ring-primary/40';

function sectionFromUrl(url: string): SettingsSectionKey {
    const query = url.split('?')[1] ?? '';
    const value = new URLSearchParams(query).get('section');

    return SECTIONS.some((section) => section.key === value)
        ? (value as SettingsSectionKey)
        : 'appearance';
}

export default function Settings({ settings, account }: SettingsProps) {
    const page = usePage();
    const activeSection = sectionFromUrl(page.url);

    return (
        <PublicLayout title="Pengaturan">
            <section className="space-y-6">
                <header className="space-y-1">
                    <h1 className="text-2xl font-bold text-on-surface">
                        Pengaturan
                    </h1>
                    <p className="text-sm text-on-surface-variant">
                        Atur pengalaman membaca dan akun Fiksia kamu.
                    </p>
                </header>

                <div className="grid gap-6 md:grid-cols-[14rem_minmax(0,1fr)]">
                    <nav aria-label="Bagian pengaturan">
                        <ul className="flex gap-2 overflow-x-auto pb-2 md:sticky md:top-20 md:block md:space-y-2 md:overflow-visible">
                            {SECTIONS.map((section) => {
                                const active = activeSection === section.key;

                                return (
                                    <li key={section.key} className="shrink-0">
                                        <Link
                                            href={`/settings?section=${section.key}`}
                                            preserveScroll
                                            aria-current={
                                                active ? 'page' : undefined
                                            }
                                            className={
                                                active
                                                    ? SECTION_ACTIVE
                                                    : SECTION_INACTIVE
                                            }
                                        >
                                            <span className="font-semibold">
                                                {section.label}
                                            </span>
                                            <span className="text-xs opacity-80">
                                                {section.description}
                                            </span>
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>
                    </nav>

                    <div className="min-w-0">
                        {activeSection === 'appearance' ? (
                            <AppearanceSection
                                initialValue={settings.appearance}
                            />
                        ) : null}
                        {activeSection === 'reading' ? (
                            <ReadingSection initialValue={settings.reading} />
                        ) : null}
                        {activeSection === 'privacy' ? (
                            <UnavailableSection
                                title="Privasi"
                                description="Kontrol aktivitas bacaan dan bookmark publik belum tersedia. Tidak ada perubahan yang disimpan dari bagian ini."
                            />
                        ) : null}
                        {activeSection === 'notifications' ? (
                            <UnavailableSection
                                title="Notifikasi"
                                description="Pengaturan email dan push belum tersedia. Tidak ada perubahan yang disimpan dari bagian ini."
                            />
                        ) : null}
                        {activeSection === 'account' ? (
                            <AccountSection
                                initialName={account.name}
                                googleConnected={account.googleConnected}
                                hasPassword={account.hasPassword}
                            />
                        ) : null}
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
