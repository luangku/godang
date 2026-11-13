import { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { api, endpoints, errorMessage } from '../api';

const resetSchema = z
    .object({
        password: z.string().min(8, 'Password minimal 8 karakter.'),
        password_confirmation: z.string().min(1, 'Konfirmasi password wajib diisi.'),
    })
    .refine((values) => values.password === values.password_confirmation, {
        message: 'Konfirmasi password tidak cocok.',
        path: ['password_confirmation'],
    });

export default function ResetPassword() {
    const [searchParams] = useSearchParams();
    const [serverError, setServerError] = useState(null);
    const [success, setSuccess] = useState(false);
    const token = searchParams.get('token') || '';
    const email = searchParams.get('email') || '';
    const form = useForm({ resolver: zodResolver(resetSchema) });

    const onSubmit = async (values) => {
        setServerError(null);

        try {
            await api.post(endpoints.auth.resetPassword, {
                token,
                email,
                password: values.password,
                password_confirmation: values.password_confirmation,
            });
            setSuccess(true);
            form.reset();
        } catch (error) {
            setServerError(errorMessage(error, 'Gagal mereset password.'));
        }
    };

    if (!token || !email) {
        return (
            <div className="min-h-screen flex items-center justify-center px-4">
                <div className="w-full max-w-sm bg-(--color-surface) border border-(--color-border) rounded-xl p-6 text-center space-y-4">
                    <h1 className="text-lg font-semibold">Link reset tidak valid</h1>
                    <p className="text-sm text-(--color-text-muted)">
                        Token atau email tidak ditemukan. Silakan minta link reset baru.
                    </p>
                    <Link to="/admin/login" className="inline-block text-sm text-(--color-accent) hover:underline">
                        Kembali ke login
                    </Link>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen flex items-center justify-center px-4">
            <div className="w-full max-w-sm">
                <div className="text-center mb-8">
                    <h1 className="text-lg font-semibold">Reset Password</h1>
                    <p className="mt-1 text-sm text-(--color-text-muted)">
                        Masukkan password baru untuk akun {email}.
                    </p>
                </div>

                <form
                    onSubmit={form.handleSubmit(onSubmit)}
                    className="bg-(--color-surface) border border-(--color-border) rounded-xl p-6 space-y-4"
                    noValidate
                >
                    {serverError && (
                        <div
                            role="alert"
                            className="rounded-lg px-4 py-3 text-sm border border-(--color-error) text-(--color-error) bg-(--color-accent-bg)"
                        >
                            {serverError}
                        </div>
                    )}

                    {success && (
                        <div className="rounded-lg px-4 py-3 text-sm border border-(--color-accent) text-(--color-accent) bg-(--color-accent-bg)">
                            Password berhasil direset. Silakan masuk dengan password baru.
                        </div>
                    )}

                    <div>
                        <label htmlFor="password" className="block text-sm font-medium mb-1.5">
                            Password Baru
                        </label>
                        <input
                            id="password"
                            type="password"
                            autoComplete="new-password"
                            className="w-full px-4 py-2.5 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm focus:border-(--color-accent) focus:ring-1 focus:ring-(--color-accent) outline-none"
                            {...form.register('password')}
                        />
                        {form.formState.errors.password && (
                            <p className="mt-1 text-xs text-(--color-error)">
                                {form.formState.errors.password.message}
                            </p>
                        )}
                    </div>

                    <div>
                        <label htmlFor="password_confirmation" className="block text-sm font-medium mb-1.5">
                            Konfirmasi Password
                        </label>
                        <input
                            id="password_confirmation"
                            type="password"
                            autoComplete="new-password"
                            className="w-full px-4 py-2.5 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm focus:border-(--color-accent) focus:ring-1 focus:ring-(--color-accent) outline-none"
                            {...form.register('password_confirmation')}
                        />
                        {form.formState.errors.password_confirmation && (
                            <p className="mt-1 text-xs text-(--color-error)">
                                {form.formState.errors.password_confirmation.message}
                            </p>
                        )}
                    </div>

                    <button
                        type="submit"
                        disabled={form.formState.isSubmitting || success}
                        className="w-full py-2.5 rounded-lg bg-(--color-accent) text-(--color-on-accent) font-semibold text-sm hover:opacity-90 transition-opacity disabled:opacity-60"
                    >
                        {form.formState.isSubmitting ? 'Menyimpan...' : 'Simpan Password Baru'}
                    </button>

                    <Link
                        to="/admin/login"
                        className="block w-full text-center text-sm text-(--color-text-muted) hover:text-(--color-text)"
                    >
                        Kembali ke login
                    </Link>
                </form>
            </div>
        </div>
    );
}
