import type { FormEvent } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/Button';
import PublicLayout from '@/layouts/PublicLayout';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        post('/login');
    };

    return (
        <PublicLayout title="Masuk">
            <Head title="Masuk" />

            <section className="mx-auto mt-12 max-w-sm">
                <h1 className="mb-6 text-2xl font-bold text-on-surface">
                    Masuk
                </h1>

                <form onSubmit={submit} className="space-y-4">
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

                    <div className="flex items-center gap-2">
                        <input
                            id="remember"
                            type="checkbox"
                            checked={data.remember}
                            onChange={(e) =>
                                setData('remember', e.target.checked)
                            }
                            className="h-4 w-4 rounded border-outline text-primary accent-primary focus:ring-primary/30"
                        />
                        <label
                            htmlFor="remember"
                            className="text-sm text-on-surface-variant"
                        >
                            Ingat saya
                        </label>
                    </div>

                    <Button
                        type="submit"
                        variant="primary"
                        disabled={processing}
                        className="w-full"
                    >
                        {processing ? 'Memproses...' : 'Masuk'}
                    </Button>
                </form>

                <p className="mt-4 text-center text-sm text-on-surface-variant">
                    Belum punya akun?{' '}
                    <Link
                        href="/register"
                        className="font-medium text-primary hover:underline"
                    >
                        Daftar
                    </Link>
                </p>
            </section>
        </PublicLayout>
    );
}
