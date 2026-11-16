import { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import { api, endpoints, errorMessage } from '../api';
import PasswordInput from '../components/PasswordInput';
import { Toast, useToast } from '../components/Toast';
import { useAuth } from '../stores/auth';

function initialForm(user) {
    return {
        name: user?.name || '',
        email: user?.email || '',
        password: '',
        password_confirmation: '',
    };
}

export default function Profile() {
    const { toast, show } = useToast();
    const user = useAuth((state) => state.user);
    const setUser = useAuth((state) => state.setUser);
    const [form, setForm] = useState(() => initialForm(user));

    const mutation = useMutation({
        mutationFn: async (payload) => (await api.put(endpoints.profile, payload)).data,
        onSuccess: (response) => {
            setUser(response.user);
            setForm(initialForm(response.user));
            show(response.message || 'Profil berhasil diperbarui.');
        },
        onError: (error) => show(errorMessage(error), 'error'),
    });

    const handleSubmit = (event) => {
        event.preventDefault();

        if (form.password && form.password !== form.password_confirmation) {
            show('Konfirmasi password tidak cocok.', 'error');
            return;
        }

        const payload = {
            name: form.name,
            email: form.email,
        };

        if (form.password) {
            payload.password = form.password;
            payload.password_confirmation = form.password_confirmation;
        }

        mutation.mutate(payload);
    };

    return (
        <div className="space-y-6">
            <div className="space-y-1">
                <h1 className="text-xl font-bold">Profil</h1>
                <p className="text-sm text-(--color-text-muted)">
                    Perbarui nama, email, dan password akun Anda sendiri.
                </p>
            </div>

            <div className="bg-(--color-surface) border border-(--color-border) rounded-xl p-5">
                <div className="flex items-center gap-3">
                    <div className="w-12 h-12 rounded-full bg-gradient-to-br from-(--color-accent) to-purple-500 flex items-center justify-center text-lg font-bold text-white">
                        {(form.name || form.email || 'A').charAt(0).toUpperCase()}
                    </div>
                    <div>
                        <p className="font-semibold">{form.name || form.email}</p>
                        <span
                            className={`inline-block mt-1 text-xs px-2 py-0.5 rounded-md ${
                                user?.is_primary
                                    ? 'bg-(--color-accent-bg) text-(--color-accent) font-medium'
                                    : 'bg-(--color-bg) text-(--color-text-muted) border border-(--color-border)'
                            }`}
                        >
                            {user?.is_primary ? 'Admin Utama' : 'Admin'}
                        </span>
                    </div>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="bg-(--color-surface) border border-(--color-border) rounded-xl p-5 space-y-4">
                <h2 className="font-semibold text-sm">Informasi Akun</h2>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium mb-1.5">Nama</label>
                        <input
                            value={form.name}
                            onChange={(event) => setForm({ ...form, name: event.target.value })}
                            maxLength={100}
                            className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm"
                            required
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium mb-1.5">Email</label>
                        <input
                            type="email"
                            value={form.email}
                            onChange={(event) => setForm({ ...form, email: event.target.value })}
                            maxLength={255}
                            className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm"
                            required
                        />
                    </div>
                </div>

                <h2 className="font-semibold text-sm pt-2">Ganti Password</h2>
                <p className="text-xs text-(--color-text-muted)">
                    Kosongkan kedua kolom jika tidak ingin mengganti password.
                </p>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium mb-1.5">Password Baru</label>
                        <PasswordInput
                            value={form.password}
                            onChange={(event) => setForm({ ...form, password: event.target.value })}
                            minLength={8}
                            autoComplete="new-password"
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium mb-1.5">Konfirmasi Password Baru</label>
                        <PasswordInput
                            value={form.password_confirmation}
                            onChange={(event) => setForm({ ...form, password_confirmation: event.target.value })}
                            minLength={8}
                            autoComplete="new-password"
                        />
                    </div>
                </div>

                <div className="flex items-center gap-3">
                    <button
                        type="submit"
                        disabled={mutation.isPending}
                        className="px-4 py-2 rounded-lg bg-(--color-accent) text-(--color-on-accent) text-sm font-medium hover:opacity-90 disabled:opacity-60"
                    >
                        {mutation.isPending ? 'Menyimpan...' : 'Simpan Profil'}
                    </button>
                </div>
            </form>

            <Toast toast={toast} />
        </div>
    );
}
