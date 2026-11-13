import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { api, endpoints, errorMessage } from '../api';
import TurnstileWidget from '../components/TurnstileWidget';
import { useAuth } from '../stores/auth';
import { initialSiteData, readTurnstileSiteKey, resolveSiteLogo } from '../site';

const loginSchema = z.object({
    email: z.string().min(1, 'Email wajib diisi.').email('Format email tidak valid.'),
    password: z.string().min(1, 'Password wajib diisi.'),
});

const forgotSchema = z.object({
    email: z.string().min(1, 'Email wajib diisi.').email('Format email tidak valid.'),
});

export default function Login() {
    const login = useAuth((state) => state.login);
    const [serverError, setServerError] = useState(null);
    const [mode, setMode] = useState('login'); // login | forgot
    const [siteData] = useState(initialSiteData);
    const [isDark, setIsDark] = useState(() => document.documentElement.classList.contains('dark'));
    const [forgotSuccess, setForgotSuccess] = useState(null);
    const [siteKey] = useState(readTurnstileSiteKey);
    const [turnstileToken, setTurnstileToken] = useState(null);
    const [turnstileResetKey, setTurnstileResetKey] = useState(0);

    const loginForm = useForm({ resolver: zodResolver(loginSchema) });
    const forgotForm = useForm({ resolver: zodResolver(forgotSchema) });

    const onSubmitLogin = async (values) => {
        setServerError(null);

        if (siteKey && !turnstileToken) {
            setServerError('Selesaikan verifikasi keamanan terlebih dahulu.');
            return;
        }

        try {
            await login(values.email, values.password, turnstileToken);
        } catch (error) {
            setServerError(errorMessage(error, 'Login gagal.'));
            setTurnstileResetKey((key) => key + 1);
        }
    };

    const onSubmitForgot = async (values) => {
        setServerError(null);
        setForgotSuccess(null);
        try {
            await api.post(endpoints.auth.forgotPassword, { email: values.email });
            setForgotSuccess('Link reset password telah dikirim ke email Anda jika akun terdaftar.');
            forgotForm.reset();
        } catch (error) {
            setServerError(errorMessage(error, 'Gagal mengirim link reset password.'));
        }
    };

    const siteTitle = siteData.site_title || 'Admin CMS';
    const siteLogoLight = siteData.site_logo || siteData.site_logo_dark;
    const siteLogoDark = siteData.site_logo_dark || siteData.site_logo;

    const toggleTheme = () => {
        const nowDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', nowDark ? 'dark' : 'light');
        setIsDark(nowDark);
    };

    return (
        <div className="min-h-screen flex items-center justify-center px-4">
            <button
                type="button"
                onClick={toggleTheme}
                className="fixed top-4 right-4 p-2 rounded-lg hover:bg-(--color-accent-bg) transition-colors"
                aria-label="Toggle dark mode"
            >
                {isDark ? (
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                ) : (
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                )}
            </button>

            <div className="w-full max-w-sm">
                <div className="text-center mb-8">
                    {siteLogoLight ? (
                        <>
                            <img
                                src={resolveSiteLogo(siteLogoLight)}
                                alt={siteData.site_logo_alt || siteTitle}
                                className="h-8 max-w-[160px] w-auto mx-auto mb-4 object-contain dark:hidden"
                            />
                            <img
                                src={resolveSiteLogo(siteLogoDark)}
                                alt={siteData.site_logo_alt || siteTitle}
                                className="h-8 max-w-[160px] w-auto mx-auto mb-4 object-contain hidden dark:block"
                            />
                        </>
                    ) : (
                        <div className="h-8 w-8 mx-auto mb-4 rounded-lg bg-(--color-accent) text-(--color-on-accent) flex items-center justify-center font-bold text-sm">
                            {siteTitle.charAt(0).toUpperCase()}
                        </div>
                    )}
                    <p className="text-sm text-(--color-text-muted)">
                        {mode === 'login' ? 'Masuk untuk mengelola artikel dan komentar.' : 'Atur ulang password admin.'}
                    </p>
                </div>

                {mode === 'login' ? (
                    <form
                        onSubmit={loginForm.handleSubmit(onSubmitLogin)}
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

                        <div>
                            <label htmlFor="email" className="block text-sm font-medium mb-1.5">
                                Email
                            </label>
                            <input
                                id="email"
                                type="email"
                                autoComplete="email"
                                className="w-full px-4 py-2.5 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm focus:border-(--color-accent) focus:ring-1 focus:ring-(--color-accent) outline-none"
                                {...loginForm.register('email')}
                            />
                            {loginForm.formState.errors.email && (
                                <p className="mt-1 text-xs text-(--color-error)">{loginForm.formState.errors.email.message}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="password" className="block text-sm font-medium mb-1.5">
                                Password
                            </label>
                            <input
                                id="password"
                                type="password"
                                autoComplete="current-password"
                                className="w-full px-4 py-2.5 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm focus:border-(--color-accent) focus:ring-1 focus:ring-(--color-accent) outline-none"
                                {...loginForm.register('password')}
                            />
                            {loginForm.formState.errors.password && (
                                <p className="mt-1 text-xs text-(--color-error)">{loginForm.formState.errors.password.message}</p>
                            )}
                        </div>

                        <TurnstileWidget
                            siteKey={siteKey}
                            theme={isDark ? 'dark' : 'light'}
                            resetKey={turnstileResetKey}
                            onToken={setTurnstileToken}
                        />

                        <div className="flex justify-end">
                            <button
                                type="button"
                                onClick={() => {
                                    setMode('forgot');
                                    setServerError(null);
                                    setForgotSuccess(null);
                                    forgotForm.reset();
                                }}
                                className="text-xs text-(--color-accent) hover:underline"
                            >
                                Lupa password?
                            </button>
                        </div>

                        <button
                            type="submit"
                            disabled={loginForm.formState.isSubmitting}
                            className="w-full py-2.5 rounded-lg bg-(--color-accent) text-(--color-on-accent) font-semibold text-sm hover:opacity-90 transition-opacity disabled:opacity-60"
                        >
                            {loginForm.formState.isSubmitting ? 'Memproses...' : 'Masuk'}
                        </button>
                    </form>
                ) : (
                    <form
                        onSubmit={forgotForm.handleSubmit(onSubmitForgot)}
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
                        {forgotSuccess && (
                            <div className="rounded-lg px-4 py-3 text-sm border border-(--color-accent) text-(--color-accent) bg-(--color-accent-bg)">
                                {forgotSuccess}
                            </div>
                        )}

                        <div>
                            <label htmlFor="forgot-email" className="block text-sm font-medium mb-1.5">
                                Email
                            </label>
                            <input
                                id="forgot-email"
                                type="email"
                                autoComplete="email"
                                className="w-full px-4 py-2.5 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm focus:border-(--color-accent) focus:ring-1 focus:ring-(--color-accent) outline-none"
                                {...forgotForm.register('email')}
                            />
                            {forgotForm.formState.errors.email && (
                                <p className="mt-1 text-xs text-(--color-error)">{forgotForm.formState.errors.email.message}</p>
                            )}
                        </div>

                        <button
                            type="submit"
                            disabled={forgotForm.formState.isSubmitting}
                            className="w-full py-2.5 rounded-lg bg-(--color-accent) text-(--color-on-accent) font-semibold text-sm hover:opacity-90 transition-opacity disabled:opacity-60"
                        >
                            {forgotForm.formState.isSubmitting ? 'Mengirim...' : 'Kirim Link Reset'}
                        </button>

                        <button
                            type="button"
                            onClick={() => {
                                setMode('login');
                                setServerError(null);
                                setForgotSuccess(null);
                            }}
                            className="w-full text-sm text-(--color-text-muted) hover:text-(--color-text)"
                        >
                            Kembali ke login
                        </button>
                    </form>
                )}
            </div>
        </div>
    );
}
