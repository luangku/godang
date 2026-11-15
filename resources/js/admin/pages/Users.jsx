import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api, endpoints, errorMessage } from '../api';
import ConfirmModal from '../components/ConfirmModal';
import { Toast, useToast } from '../components/Toast';
import { useAuth } from '../stores/auth';

function initialForm() {
    return { name: '', email: '', password: '', password_confirmation: '' };
}

export default function Users() {
    const queryClient = useQueryClient();
    const { toast, show } = useToast();
    const currentUser = useAuth((state) => state.user);
    const [form, setForm] = useState(initialForm);
    const [editing, setEditing] = useState(null);
    const [confirm, setConfirm] = useState(null);
    const [busy, setBusy] = useState(false);

    const { data: users = [], isLoading } = useQuery({
        queryKey: ['users'],
        queryFn: async () => (await api.get(endpoints.users)).data,
        initialData: [],
    });

    const mutation = useMutation({
        mutationFn: async ({ method, url, payload }) => (await api({ method, url, data: payload })).data,
        onSuccess: (resp) => {
            queryClient.invalidateQueries({ queryKey: ['users'] });
            show(resp.message || 'Berhasil.');
            resetForm();
        },
        onError: (error) => show(errorMessage(error), 'error'),
    });

    const resetForm = () => {
        setForm(initialForm());
        setEditing(null);
    };

    const handleSubmit = (event) => {
        event.preventDefault();
        const payload = {
            name: form.name,
            email: form.email,
        };

        if (form.password) {
            payload.password = form.password;
            payload.password_confirmation = form.password_confirmation;
        }

        if (editing) {
            mutation.mutate({ method: 'PUT', url: `/admin/users/${editing.id}`, payload });
        } else {
            mutation.mutate({ method: 'POST', url: endpoints.users, payload });
        }
    };

    const editUser = (user) => {
        setEditing(user);
        setForm({
            name: user.name || '',
            email: user.email || '',
            password: '',
            password_confirmation: '',
        });
        window.scrollTo({ top: 0 });
    };

    const deleteUser = (user) => {
        if (user.id === currentUser?.id) {
            show('Anda tidak dapat menghapus akun sendiri.', 'error');
            return;
        }

        if ((user.posts_count ?? 0) > 0) {
            show('Admin ini masih memiliki artikel. Pindahkan atau hapus artikelnya terlebih dahulu.', 'error');
            return;
        }

        setConfirm({
            title: 'Hapus admin',
            message: `Hapus akun admin "${user.name || user.email}"?`,
            confirmLabel: 'Hapus',
            action: async () => {
                setBusy(true);
                try {
                    await mutation.mutateAsync({ method: 'DELETE', url: `/admin/users/${user.id}` });
                    setConfirm(null);
                } finally {
                    setBusy(false);
                }
            },
        });
    };

    return (
        <div className="space-y-6">
            <h1 className="text-xl font-bold">Pengguna</h1>

            <form onSubmit={handleSubmit} className="bg-(--color-surface) border border-(--color-border) rounded-xl p-5 space-y-4">
                <h2 className="font-semibold text-sm">{editing ? 'Edit Admin' : 'Tambah Admin'}</h2>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium mb-1.5">Nama</label>
                        <input
                            value={form.name}
                            onChange={(e) => setForm({ ...form, name: e.target.value })}
                            className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm"
                            required
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium mb-1.5">Email</label>
                        <input
                            type="email"
                            value={form.email}
                            onChange={(e) => setForm({ ...form, email: e.target.value })}
                            className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm"
                            required
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium mb-1.5">
                            Password {editing && <span className="text-(--color-text-muted)">(kosongkan jika tidak diubah)</span>}
                        </label>
                        <input
                            type="password"
                            value={form.password}
                            onChange={(e) => setForm({ ...form, password: e.target.value })}
                            minLength={8}
                            autoComplete="new-password"
                            className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm"
                            required={!editing}
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium mb-1.5">Konfirmasi Password</label>
                        <input
                            type="password"
                            value={form.password_confirmation}
                            onChange={(e) => setForm({ ...form, password_confirmation: e.target.value })}
                            minLength={8}
                            autoComplete="new-password"
                            className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm"
                            required={!editing && Boolean(form.password)}
                        />
                    </div>
                </div>
                <div className="flex items-center gap-3">
                    <button
                        type="submit"
                        disabled={mutation.isPending}
                        className="px-4 py-2 rounded-lg bg-(--color-accent) text-(--color-on-accent) text-sm font-medium hover:opacity-90 disabled:opacity-60"
                    >
                        {editing ? 'Perbarui' : 'Simpan'}
                    </button>
                    {editing && (
                        <button
                            type="button"
                            onClick={resetForm}
                            className="px-3 py-1.5 rounded-lg text-sm border border-(--color-border) hover:bg-(--color-accent-bg)"
                        >
                            Batal
                        </button>
                    )}
                </div>
            </form>

            <div className="bg-(--color-surface) border border-(--color-border) rounded-xl overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-(--color-border) text-left text-xs uppercase text-(--color-text-muted)">
                            <th className="px-4 py-3">Nama</th>
                            <th className="px-4 py-3">Email</th>
                            <th className="px-4 py-3 text-right">Artikel</th>
                            <th className="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-(--color-border)">
                        {isLoading && (
                            <tr>
                                <td colSpan="4" className="px-4 py-4 text-center text-(--color-text-muted)">Memuat...</td>
                            </tr>
                        )}
                        {!isLoading && users.length === 0 && (
                            <tr>
                                <td colSpan="4" className="px-4 py-4 text-center text-(--color-text-muted)">Belum ada admin.</td>
                            </tr>
                        )}
                        {users.map((user) => {
                            const isSelf = user.id === currentUser?.id;

                            return (
                                <tr key={user.id} className="hover:bg-(--color-accent-bg)/40">
                                    <td className="px-4 py-3">
                                        <span className="font-medium">{user.name}</span>
                                        {isSelf && (
                                            <span className="ml-2 text-xs px-2 py-0.5 rounded-md bg-(--color-accent-bg) text-(--color-accent)">
                                                Anda
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-(--color-text-muted)">{user.email}</td>
                                    <td className="px-4 py-3 text-right">{user.posts_count ?? 0}</td>
                                    <td className="px-4 py-3 text-right">
                                        <div className="flex justify-end gap-3 text-xs">
                                            <button
                                                type="button"
                                                onClick={() => editUser(user)}
                                                className="text-(--color-accent) hover:underline"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => deleteUser(user)}
                                                className="text-(--color-error) hover:underline disabled:text-(--color-text-muted) disabled:no-underline"
                                                disabled={isSelf || (user.posts_count ?? 0) > 0}
                                                title={isSelf
                                                    ? 'Tidak dapat menghapus akun sendiri'
                                                    : (user.posts_count ?? 0) > 0
                                                        ? 'Admin masih memiliki artikel'
                                                        : 'Hapus admin'}
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>

            <ConfirmModal
                open={Boolean(confirm)}
                title={confirm?.title}
                message={confirm?.message}
                confirmLabel={confirm?.confirmLabel}
                busy={busy}
                onConfirm={confirm?.action}
                onCancel={() => setConfirm(null)}
            />
            <Toast toast={toast} />
        </div>
    );
}
