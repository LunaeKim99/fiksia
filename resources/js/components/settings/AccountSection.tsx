import { useEffect, useRef, useState, type FormEvent, type JSX } from 'react';
import { router, usePage } from '@inertiajs/react';
import { GoogleIcon } from '@/components/icons';

type SaveStatus = 'idle' | 'saving' | 'saved' | 'error';

const SAVE_LABELS: Record<SaveStatus, string> = {
    idle: '',
    saving: 'Menyimpan',
    saved: 'Tersimpan',
    error: 'Gagal',
};

const PROVIDER_LABELS: Record<SaveStatus, string> = {
    idle: '',
    saving: 'Memproses',
    saved: 'Tersimpan',
    error: 'Gagal',
};

const SAVE_BUTTON =
    'btn-primary disabled:cursor-not-allowed disabled:opacity-50';
const SECONDARY_BUTTON =
    'btn-secondary disabled:cursor-not-allowed disabled:opacity-50';

export function AccountSection({
    initialName,
    googleConnected,
    hasPassword,
}: {
    initialName: string;
    googleConnected: boolean;
    hasPassword: boolean;
}): JSX.Element {
    const page = usePage();
    const email = page.props.auth.user?.email ?? '';
    const [name, setName] = useState(initialName);
    const [status, setStatus] = useState<SaveStatus>('idle');
    const [errorMessage, setErrorMessage] = useState<string | null>(null);
    const [providerStatus, setProviderStatus] = useState<SaveStatus>('idle');
    const preserveDraftOnError = useRef(false);
    const nameRef = useRef(name);
    nameRef.current = name;

    useEffect(() => {
        if (preserveDraftOnError.current) {
            preserveDraftOnError.current = false;
            setName(nameRef.current);
            return;
        }

        setName(initialName);
    }, [initialName]);

    const saveProfile = (): void => {
        if (status === 'saving') return;

        preserveDraftOnError.current = false;
        setStatus('saving');
        setErrorMessage(null);
        router.patch(
            '/settings/account/profile',
            { name },
            {
                preserveScroll: true,
                onSuccess: () => {
                    preserveDraftOnError.current = false;
                    setStatus('saved');
                },
                onError: (errors) => {
                    preserveDraftOnError.current = true;
                    setErrorMessage(
                        typeof errors.name === 'string'
                            ? errors.name
                            : 'Nama akun gagal disimpan. Coba lagi.',
                    );
                    setStatus('error');
                },
                onHttpException: () => {
                    preserveDraftOnError.current = false;
                    setErrorMessage('Nama akun gagal disimpan. Coba lagi.');
                    setStatus('error');
                },
                onNetworkError: () => {
                    preserveDraftOnError.current = false;
                    setErrorMessage('Tidak dapat terhubung. Coba lagi.');
                    setStatus('error');
                },
                onCancel: () => {
                    preserveDraftOnError.current = false;
                    setErrorMessage('Penyimpanan nama dibatalkan.');
                    setStatus('error');
                },
                onFinish: () => {
                    setStatus((current) =>
                        current === 'saving' ? 'error' : current,
                    );
                },
            },
        );
    };

    const submit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        saveProfile();
    };

    const disconnectGoogle = (): void => {
        if (!hasPassword || providerStatus === 'saving') return;

        setProviderStatus('saving');
        router.post(
            '/settings/account/google/disconnect',
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    setProviderStatus('saved');
                },
                onError: () => {
                    setProviderStatus('error');
                },
                onHttpException: () => {
                    setProviderStatus('error');
                },
                onNetworkError: () => {
                    setProviderStatus('error');
                },
                onCancel: () => {
                    setProviderStatus('error');
                },
                onFinish: () => {
                    setProviderStatus((current) =>
                        current === 'saving' ? 'error' : current,
                    );
                },
            },
        );
    };

    return (
        <section aria-labelledby="account-heading" className="space-y-6">
            <header className="space-y-1">
                <h2
                    id="account-heading"
                    className="text-xl font-bold text-on-surface"
                >
                    Akun
                </h2>
                <p className="text-sm text-on-surface-variant">
                    Kelola nama tampilan dan cara masuk ke Fiksia.
                </p>
            </header>

            <form
                onSubmit={submit}
                className="space-y-5 rounded-2xl border border-outline-variant bg-surface-container p-4"
                noValidate
            >
                <div>
                    <label htmlFor="account-name" className="field-label">
                        Nama
                    </label>
                    <input
                        id="account-name"
                        type="text"
                        value={name}
                        maxLength={255}
                        autoComplete="name"
                        required
                        aria-describedby={
                            errorMessage === null
                                ? 'account-name-hint'
                                : 'account-name-error'
                        }
                        aria-invalid={errorMessage !== null}
                        className={
                            errorMessage === null
                                ? 'field-input'
                                : 'field-input border-error focus:border-error focus:ring-error/30'
                        }
                        onChange={(event) => {
                            preserveDraftOnError.current = false;
                            setName(event.target.value);
                            setStatus('idle');
                            setErrorMessage(null);
                        }}
                    />
                    {errorMessage === null ? (
                        <p
                            id="account-name-hint"
                            className="mt-1.5 text-xs text-on-surface-variant"
                        >
                            Nama ini hanya mengubah akun Fiksia kamu.
                        </p>
                    ) : (
                        <p id="account-name-error" className="field-error">
                            {errorMessage}
                        </p>
                    )}
                </div>

                <div>
                    <label htmlFor="account-email" className="field-label">
                        Email
                    </label>
                    <input
                        id="account-email"
                        type="email"
                        value={email}
                        readOnly
                        aria-readonly="true"
                        aria-describedby="account-email-hint"
                        className="field-input cursor-not-allowed bg-surface-container-high"
                    />
                    <p
                        id="account-email-hint"
                        className="mt-1.5 text-xs text-on-surface-variant"
                    >
                        Email belum dapat diubah dari halaman ini.
                    </p>
                </div>

                <div className="flex flex-wrap items-center gap-3 border-t border-outline-variant pt-4">
                    <button
                        type="submit"
                        disabled={status === 'saving'}
                        className={SAVE_BUTTON}
                    >
                        {status === 'saving' ? 'Menyimpan...' : 'Simpan nama'}
                    </button>
                    <span
                        role="status"
                        aria-live="polite"
                        className="text-sm text-on-surface-variant"
                    >
                        {SAVE_LABELS[status]}
                    </span>
                    {status === 'error' ? (
                        <button
                            type="button"
                            onClick={saveProfile}
                            className={SECONDARY_BUTTON}
                        >
                            Coba lagi
                        </button>
                    ) : null}
                </div>
            </form>

            <section
                aria-labelledby="account-provider-heading"
                className="space-y-4 rounded-2xl border border-outline-variant bg-surface-container p-4"
            >
                <div>
                    <h3
                        id="account-provider-heading"
                        className="text-sm font-semibold text-on-surface"
                    >
                        Cara masuk
                    </h3>
                    <p className="mt-1 text-sm text-on-surface-variant">
                        Hubungkan Google tanpa mengubah email atau kata sandi.
                    </p>
                </div>

                <div className="flex flex-wrap items-center gap-3">
                    <span className="inline-flex min-h-11 items-center gap-2 rounded-xl border border-outline-variant bg-surface px-3 text-sm text-on-surface">
                        <GoogleIcon className="h-5 w-5 shrink-0" />
                        {googleConnected
                            ? 'Google terhubung'
                            : 'Google belum terhubung'}
                    </span>
                    <span className="text-sm text-on-surface-variant">
                        {hasPassword
                            ? 'Akun memakai email dan kata sandi.'
                            : 'Akun ini memakai Google saja.'}
                    </span>
                </div>

                {googleConnected ? (
                    hasPassword ? (
                        <button
                            type="button"
                            onClick={disconnectGoogle}
                            disabled={providerStatus === 'saving'}
                            className={SECONDARY_BUTTON}
                        >
                            Putuskan Google
                        </button>
                    ) : (
                        <div className="space-y-2">
                            <button
                                type="button"
                                disabled
                                aria-describedby="google-only-help"
                                className={SECONDARY_BUTTON}
                            >
                                Putuskan Google
                            </button>
                            <p
                                id="google-only-help"
                                className="text-sm text-on-surface-variant"
                            >
                                Hubungan Google tidak dapat diputus karena akun
                                ini tidak memiliki kata sandi. Alur membuat kata
                                sandi belum tersedia.
                            </p>
                        </div>
                    )
                ) : (
                    <a
                        href="/settings/account/google/connect"
                        className="btn-secondary"
                    >
                        <GoogleIcon className="h-5 w-5 shrink-0" />
                        Hubungkan Google
                    </a>
                )}

                <p
                    role="status"
                    aria-live="polite"
                    className="text-sm text-on-surface-variant"
                >
                    {PROVIDER_LABELS[providerStatus]}
                </p>
            </section>
        </section>
    );
}
