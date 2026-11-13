import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api, endpoints, errorMessage } from '../api';
import ConfirmModal from './ConfirmModal';
import { Toast, useToast } from './Toast';

export default function MediaCategoryManager({ onClose }) {
    const queryClient = useQueryClient();
    const { toast, show } = useToast();
    const [name, setName] = useState('');
    const [editing, setEditing] = useState(null);
    const [editName, setEditName] = useState('');
    const [confirm, setConfirm] = useState(null);
    const [busy, setBusy] = useState(false);

    const { data: categories = [], isLoading } = useQuery({
        queryKey: ['media-categories'],
        queryFn: async () => (await api.get(endpoints.mediaCategories)).data,
    });

    const invalidate = () => {
        queryClient.invalidateQueries({ queryKey: ['media-categories'] });
        queryClient.invalidateQueries({ queryKey: ['media'] });
        queryClient.invalidateQueries({ queryKey: ['media-picker'] });
    };

    const mutation = useMutation({
        mutationFn: async ({ method, url, payload }) => (await api({ method, url, data: payload })).data,
        onSuccess: (response) => {
            invalidate();
            show(response.message || 'Berhasil.');
        },
        onError: (error) => show(errorMessage(error), 'error'),
    });

    const handleCreate = async (event) => {
        event.preventDefault();

        const trimmed = name.trim();

        if (!trimmed) return;

        try {
            await mutation.mutateAsync({
                method: 'POST',
                url: endpoints.mediaCategories,
                payload: { name: trimmed },
            });
            setName('');
        } catch {
            /* toast handled in mutation */
        }
    };

    const startEditing = (category) => {
        setEditing(category.id);
        setEditName(category.name);
    };

    const cancelEditing = () => {
        setEditing(null);
        setEditName('');
    };

    const handleRename = async (category) => {
        const trimmed = editName.trim();

        if (!trimmed || trimmed === category.name) {
            cancelEditing();
            return;
        }

        try {
            await mutation.mutateAsync({
                method: 'PATCH',
                url: `${endpoints.mediaCategories}/${category.id}`,
                payload: { name: trimmed },
            });
            cancelEditing();
        } catch {
            /* toast handled in mutation */
        }
    };

    const askDelete = (category) => {
        setConfirm({
            title: 'Hapus kategori',
            message: `Hapus kategori "${category.name}"? ${category.media_count ?? 0} media akan menjadi tanpa kategori. File media tidak ikut terhapus.`,
            confirmLabel: 'Hapus kategori',
            action: async () => {
                setBusy(true);
                try {
                    await mutation.mutateAsync({
                        method: 'DELETE',
                        url: `${endpoints.mediaCategories}/${category.id}`,
                    });
                    setConfirm(null);
                } finally {
                    setBusy(false);
                }
            },
        });
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div className="flex max-h-[85vh] w-full max-w-xl flex-col overflow-hidden rounded-xl border border-(--color-border) bg-(--color-surface)">
                <div className="flex items-center justify-between border-b border-(--color-border) px-4 py-3">
                    <div>
                        <h3 className="text-sm font-semibold">Kategori media</h3>
                        <p className="text-xs text-(--color-text-muted)">
                            Kelompokkan media agar mudah difilter. Terpisah dari folder penyimpanan.
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="text-(--color-text-muted) hover:text-(--color-text)"
                        aria-label="Tutup"
                    >
                        ✕
                    </button>
                </div>

                <form onSubmit={handleCreate} className="flex gap-2 border-b border-(--color-border) p-4">
                    <input
                        type="text"
                        value={name}
                        onChange={(event) => setName(event.target.value)}
                        placeholder="Nama kategori baru..."
                        aria-label="Nama kategori media baru"
                        maxLength={60}
                        className="flex-1 rounded-lg border border-(--color-border) bg-(--color-bg) px-3 py-2 text-sm"
                    />
                    <button
                        type="submit"
                        disabled={!name.trim() || mutation.isPending}
                        className="rounded-lg bg-(--color-accent) px-4 py-2 text-sm font-medium text-(--color-on-accent) hover:opacity-90 disabled:opacity-60"
                    >
                        Tambah
                    </button>
                </form>

                <div className="overflow-auto p-4">
                    {isLoading && <p className="text-sm text-(--color-text-muted)">Memuat...</p>}

                    {!isLoading && categories.length === 0 && (
                        <p className="text-sm text-(--color-text-muted)">
                            Belum ada kategori. Tambahkan kategori pertama di atas.
                        </p>
                    )}

                    <ul className="space-y-2">
                        {categories.map((category) => (
                            <li
                                key={category.id}
                                className="flex items-center gap-3 rounded-lg border border-(--color-border) px-3 py-2"
                            >
                                {editing === category.id ? (
                                    <form
                                        className="flex flex-1 gap-2"
                                        onSubmit={(event) => {
                                            event.preventDefault();
                                            handleRename(category);
                                        }}
                                    >
                                        <input
                                            type="text"
                                            value={editName}
                                            onChange={(event) => setEditName(event.target.value)}
                                            maxLength={60}
                                            aria-label={`Nama baru untuk ${category.name}`}
                                            autoFocus
                                            className="flex-1 rounded-lg border border-(--color-border) bg-(--color-bg) px-3 py-1.5 text-sm"
                                        />
                                        <button
                                            type="submit"
                                            disabled={mutation.isPending}
                                            className="rounded-lg bg-(--color-accent) px-3 py-1.5 text-xs font-medium text-(--color-on-accent) hover:opacity-90 disabled:opacity-60"
                                        >
                                            Simpan
                                        </button>
                                        <button
                                            type="button"
                                            onClick={cancelEditing}
                                            className="rounded-lg border border-(--color-border) px-3 py-1.5 text-xs hover:bg-(--color-accent-bg)"
                                        >
                                            Batal
                                        </button>
                                    </form>
                                ) : (
                                    <>
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-medium" title={category.name}>
                                                {category.name}
                                            </p>
                                            <p className="text-xs text-(--color-text-muted)">
                                                {category.media_count ?? 0} media
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => startEditing(category)}
                                            className="text-xs text-(--color-accent) hover:underline"
                                        >
                                            Ubah
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => askDelete(category)}
                                            className="text-xs text-(--color-error) hover:underline"
                                        >
                                            Hapus
                                        </button>
                                    </>
                                )}
                            </li>
                        ))}
                    </ul>
                </div>
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
