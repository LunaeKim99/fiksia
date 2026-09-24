import type { FormEvent } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/Button';
import PublicLayout from '@/layouts/PublicLayout';

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        post('/register');
    };

    return (
        <PublicLayout title="Daftar">
            <Head title="Daftar" />

            <section className="mx-auto mt-12 max-w-sm">
                <h1 className="mb-6 text-2xl font-bold text-on-surface">
                    Daftar
                </h1>

                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label htmlFor="name" className="field-label">
                            Nama
                        </label>
                        <input
                            id="name"
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className={`field-input ${
                                errors.name
                                    ? 'border-error focus:border-error focus:ring-error/30'
                                    : ''
                            }`}
                            required
                        />
                        {errors.name && (
                            <p className="field-error">{errors.name}</p>
                        )}
                    </div>

                    <div>
                        <label htmlFor="email" className="field-label">
                            Email
                        </label>
                        <input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            className={`field-input ${
                                errors.email
                                    ? 'border-error focus:border-error focus:ring-error/30'
                                    : ''
                            }`}
                            required
                        />
                        {errors.email && (
                            <p className="field-error">{errors.email}</p>
                        )}
                    </div>

                    <div>
                        <label htmlFor="password" className="field-label">
                            Password
                        </label>
                        <input
                            id="password"
                            type="password"
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            className={`field-input ${
                                errors.password
                                    ? 'border-error focus:border-error focus:ring-error/30'
                                    : ''
                            }`}
                            required
                        />
                        {errors.password && (
                            <p className="field-error">{errors.password}</p>
                        )}
                    </div>

                    <div>
                        <label
                            htmlFor="password_confirmation"
                            className="field-label"
                        >
                            Konfirmasi Password
                        </label>
                        <input
                            id="password_confirmation"
                            type="password"
                            value={data.password_confirmation}
                            onChange={(e) =>
                                setData('password_confirmation', e.target.value)
                            }
                            className="field-input"
                            required
                        />
                    </div>

                    <Button
                        type="submit"
                        variant="primary"
                        disabled={processing}
                        className="w-full"
                    >
                        {processing ? 'Memproses...' : 'Daftar'}
                    </Button>
                </form>

                <p className="mt-4 text-center text-sm text-on-surface-variant">
                    Sudah punya akun?{' '}
                    <Link
                        href="/login"
                        className="font-medium text-primary hover:underline"
                    >
                        Masuk
                    </Link>
                </p>
            </section>
        </PublicLayout>
    );
}
