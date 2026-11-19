import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api, endpoints, errorMessage } from '../api';
import ConfirmModal from '../components/ConfirmModal';
import { Toast, useToast } from '../components/Toast';

function initialForm() {
    return { name: '', slug: '', description: '' };
}

export default function Categories() {
    const queryClient = useQueryClient();
    const { toast, show } = useToast();
    const [form, setForm] = useState(initialForm);
    const [editing, setEditing] = useState(null);
    const [confirm, setConfirm] = useState(null);
    const [busy, setBusy] = useState(false);

    const { data: categories = [], isLoading } = useQuery({
        queryKey: ['categories'],
        queryFn: async () => (await api.get(endpoints.categories)).data,
        initialData: [],
    });

    const invalidate = () => {
        queryClient.invalidateQueries({ queryKey: ['categories'] });
        queryClient.invalidateQueries({ queryKey: ['posts'] });
    };

    const mutation = useMutation({
        mutationFn: async ({ method, url, payload }) => (await api({ method, url, data: payload })).data,
        onSuccess: (resp) => {
            invalidate();
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
            slug: form.slug || undefined,
            description: form.description || null,
        };

        if (editing) {
            mutation.mutate({ method: 'PUT', url: `/admin/categories/${editing.id}`, payload });
        } else {
            mutation.mutate({ method: 'POST', url: endpoints.categories, payload });
        }
    };

    const editCategory = (cat) => {
        setEditing(cat);
        setForm({
            name: cat.name || '',
            slug: cat.slug || '',
            description: cat.description || '',
        });
        window.scrollTo({ top: 0 });
    };

    const deleteCategory = (cat) => {
        setConfirm({
            title: 'Hapus kategori',
            message: `Hapus kategori "${cat.name || cat.slug}"?`,
            confirmLabel: 'Hapus',
            action: async () => {
                setBusy(true);
                try {
                    await mutation.mutateAsync({ method: 'DELETE', url: `/admin/categories/${cat.id}` });
                    setConfirm(null);
                } finally {
                    setBusy(false);
                }
            },
        });
    };

    return (
        <div className="space-y-6">
            <h1 className="text-xl font-bold">Kategori</h1>

            <form onSubmit={handleSubmit} className="bg-(--color-surface) border border-(--color-border) rounded-xl p-5 space-y-4">
                <h2 className="font-semibold text-sm">{editing ? 'Edit Kategori' : 'Tambah Kategori'}</h2>
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
                        <label className="block text-sm font-medium mb-1.5">Slug</label>
                        <input
                            value={form.slug}
                            onChange={(e) => setForm({ ...form, slug: e.target.value })}
                            placeholder="otomatis dari nama"
                            className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm"
                        />
                    </div>
                </div>
                <div>
                    <label className="block text-sm font-medium mb-1.5">Deskripsi</label>
                    <textarea
                        value={form.description}
                        onChange={(e) => setForm({ ...form, description: e.target.value })}
                        rows={2}
                        maxLength={500}
                        className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm resize-y"
                    />
                </div>
                <div className="flex items-center gap-3">
                    <button
                        type="submit"
                        className="px-4 py-2 rounded-lg bg-(--color-accent) text-(--color-on-accent) text-sm font-medium hover:opacity-90"
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

            <div
                className="admin-table-region bg-(--color-surface) border border-(--color-border) rounded-xl"
                role="region"
                aria-labelledby="categories-table-caption"
                tabIndex={0}
            >
                <table className="admin-table" data-table="categories">
                    <caption id="categories-table-caption" className="sr-only">Daftar kategori artikel</caption>
                    <thead>
                        <tr className="border-b border-(--color-border) text-left text-xs uppercase text-(--color-text-muted)">
                            <th scope="col" className="px-4 py-3">Nama</th>
                            <th scope="col" className="px-4 py-3">Slug</th>
                            <th scope="col" className="px-4 py-3 text-right">Posts</th>
                            <th scope="col" className="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-(--color-border)">
                        {isLoading && (
                            <tr>
                                <td colSpan="4" className="px-4 py-4 text-center text-(--color-text-muted)">Memuat...</td>
                            </tr>
                        )}
                        {categories.map((cat) => {
                            return (
                                <tr key={cat.id} className="hover:bg-(--color-accent-bg)/40">
                                    <td className="px-4 py-3">{cat.name}</td>
                                    <td className="px-4 py-3">{cat.slug}</td>
                                    <td className="px-4 py-3 text-right">{cat.posts_count ?? 0}</td>
                                    <td className="px-4 py-3 text-right">
                                        <div className="flex justify-end gap-3 text-xs">
                                            <button
                                                type="button"
                                                onClick={() => editCategory(cat)}
                                                className="text-(--color-accent) hover:underline"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => deleteCategory(cat)}
                                                className="text-(--color-error) hover:underline"
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
