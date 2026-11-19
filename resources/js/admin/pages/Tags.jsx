import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api, endpoints, errorMessage } from '../api';
import ConfirmModal from '../components/ConfirmModal';
import { Toast, useToast } from '../components/Toast';

function initialForm() {
    return { name: '', slug: '' };
}

export default function Tags() {
    const queryClient = useQueryClient();
    const { toast, show } = useToast();
    const [form, setForm] = useState(initialForm);
    const [editing, setEditing] = useState(null);
    const [confirm, setConfirm] = useState(null);
    const [busy, setBusy] = useState(false);

    const { data: tags = [], isLoading } = useQuery({
        queryKey: ['tags'],
        queryFn: async () => (await api.get(endpoints.tags)).data,
        initialData: [],
    });

    const invalidate = () => {
        queryClient.invalidateQueries({ queryKey: ['tags'] });
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
        };

        if (editing) {
            mutation.mutate({ method: 'PUT', url: `/admin/tags/${editing.id}`, payload });
        } else {
            mutation.mutate({ method: 'POST', url: endpoints.tags, payload });
        }
    };

    const editTag = (tag) => {
        setEditing(tag);
        setForm({
            name: tag.name || '',
            slug: tag.slug || '',
        });
        window.scrollTo({ top: 0 });
    };

    const deleteTag = (tag) => {
        setConfirm({
            title: 'Hapus tag',
            message: `Hapus tag "${tag.name || tag.slug}"?`,
            confirmLabel: 'Hapus',
            action: async () => {
                setBusy(true);
                try {
                    await mutation.mutateAsync({ method: 'DELETE', url: `/admin/tags/${tag.id}` });
                    setConfirm(null);
                } finally {
                    setBusy(false);
                }
            },
        });
    };

    return (
        <div className="space-y-6">
            <h1 className="text-xl font-bold">Tag</h1>

            <form onSubmit={handleSubmit} className="bg-(--color-surface) border border-(--color-border) rounded-xl p-5 space-y-4">
                <h2 className="font-semibold text-sm">{editing ? 'Edit Tag' : 'Tambah Tag'}</h2>
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
                aria-labelledby="tags-table-caption"
                tabIndex={0}
            >
                <table className="admin-table" data-table="tags">
                    <caption id="tags-table-caption" className="sr-only">Daftar tag artikel</caption>
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
                        {tags.map((tag) => {
                            return (
                                <tr key={tag.id} className="hover:bg-(--color-accent-bg)/40">
                                    <td className="px-4 py-3">{tag.name}</td>
                                    <td className="px-4 py-3">{tag.slug}</td>
                                    <td className="px-4 py-3 text-right">{tag.posts_count ?? 0}</td>
                                    <td className="px-4 py-3 text-right">
                                        <div className="flex justify-end gap-3 text-xs">
                                            <button
                                                type="button"
                                                onClick={() => editTag(tag)}
                                                className="text-(--color-accent) hover:underline"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => deleteTag(tag)}
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
