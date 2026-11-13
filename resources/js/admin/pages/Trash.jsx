import { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api, endpoints, errorMessage } from '../api';
import StatusBadge from '../components/StatusBadge';
import ConfirmModal from '../components/ConfirmModal';
import { Toast, useToast } from '../components/Toast';

function formatDate(date) {
    if (!date) return '—';
    try {
        return new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    } catch {
        return date;
    }
}

export default function Trash() {
    const [searchParams, setSearchParams] = useSearchParams();
    const page = Number(searchParams.get('page') || 1);
    const queryClient = useQueryClient();
    const { toast, show } = useToast();

    const [searchDraft, setSearchDraft] = useState(searchParams.get('q') || '');
    const [selected, setSelected] = useState([]);
    const [confirm, setConfirm] = useState(null);
    const [busy, setBusy] = useState(false);

    const setQuery = (key, value) => {
        const next = new URLSearchParams(searchParams);
        if (!value) next.delete(key);
        else next.set(key, value);
        next.delete('page');
        setSearchParams(next);
        setSelected([]);
    };

    const { data, isLoading } = useQuery({
        queryKey: ['posts', 'trash', searchParams.toString()],
        queryFn: async () =>
            (await api.get(endpoints.posts.list, {
                params: { ...Object.fromEntries(searchParams), trashed: '1' },
            })).data,
    });

    const invalidate = () => {
        queryClient.invalidateQueries({ queryKey: ['posts'] });
        queryClient.invalidateQueries({ queryKey: ['dashboard'] });
    };

    const runMutation = useMutation({
        mutationFn: async ({ method, url, payload }) => (await api({ method, url, data: payload })).data,
        onSuccess: (response) => {
            invalidate();
            show(response.message || 'Berhasil.');
        },
        onError: (error) => show(errorMessage(error), 'error'),
    });

    const askForceDeleteOne = (post) => {
        setConfirm({
            title: 'Hapus permanen',
            message: `Hapus permanen "${post.title}"? Artikel dan semua komentarnya dihapus selamanya dan tidak dapat dipulihkan.`,
            confirmLabel: 'Hapus Permanen',
            action: async () => {
                setBusy(true);
                try {
                    await runMutation.mutateAsync({ method: 'DELETE', url: `/admin/posts/${post.id}?force=1` });
                    setConfirm(null);
                } finally {
                    setBusy(false);
                }
            },
        });
    };

    const askBulkForce = () => {
        setConfirm({
            title: 'Hapus permanen',
            message: `Hapus permanen ${selected.length} artikel terpilih? Artikel dan semua komentarnya dihapus selamanya dan tidak dapat dipulihkan.`,
            confirmLabel: 'Hapus Permanen',
            action: async () => {
                setBusy(true);
                try {
                    await runMutation.mutateAsync({
                        method: 'POST',
                        url: endpoints.posts.bulkDelete,
                        payload: { ids: selected, force: true },
                    });
                    setSelected([]);
                    setConfirm(null);
                } finally {
                    setBusy(false);
                }
            },
        });
    };

    const handleRestore = async (post) => {
        try {
            await runMutation.mutateAsync({ method: 'POST', url: endpoints.posts.restore(post.id) });
        } catch {
            /* toast handled in mutation */
        }
    };

    const toggleOne = (id) => {
        setSelected((current) => (current.includes(id) ? current.filter((item) => item !== id) : [...current, id]));
    };

    const allSelected = data?.data?.length > 0 && data.data.every((post) => selected.includes(post.id));

    const toggleAll = () => {
        if (allSelected) setSelected([]);
        else setSelected(data.data.map((post) => post.id));
    };

    const gotoPage = (nextPage) => {
        const next = new URLSearchParams(searchParams);
        if (nextPage <= 1) next.delete('page');
        else next.set('page', String(nextPage));
        setSearchParams(next);
        window.scrollTo({ top: 0 });
    };

    const posts = data?.data ?? [];

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between gap-3">
                <div className="flex items-center gap-3">
                    <Link
                        to="/admin/posts"
                        className="text-sm text-(--color-text-muted) hover:text-(--color-accent)"
                    >
                        &larr; Kembali
                    </Link>
                    <h1 className="text-xl font-bold">Sampah</h1>
                </div>
            </div>

            <p className="text-sm text-(--color-text-muted)">
                Artikel yang dihapus akan muncul di sini dan bisa dipulihkan.
            </p>

            {/* Search */}
            <form
                className="flex gap-3"
                onSubmit={(event) => {
                    event.preventDefault();
                    setQuery('q', searchDraft.trim());
                }}
            >
                <input
                    type="search"
                    value={searchDraft}
                    onChange={(event) => {
                        const value = event.target.value;
                        setSearchDraft(value);

                        if (value === '' && searchParams.get('q')) {
                            setQuery('q', '');
                        }
                    }}
                    placeholder="Cari judul di sampah..."
                    className="flex-1 px-4 py-2 rounded-lg bg-(--color-surface) border border-(--color-border) text-sm focus:border-(--color-accent) outline-none"
                />
                <button
                    type="submit"
                    className="px-4 py-2 rounded-lg bg-(--color-accent) text-(--color-on-accent) text-sm font-medium hover:opacity-90"
                >
                    Cari
                </button>
            </form>

            {/* Bulk actions */}
            {selected.length > 0 && (
                <div className="flex items-center gap-3 px-4 py-2 rounded-lg bg-(--color-accent-bg) border border-(--color-accent) text-sm">
                    <span>{selected.length} terpilih</span>
                    <button
                        type="button"
                        onClick={askBulkForce}
                        className="font-medium text-(--color-error) hover:underline"
                    >
                        Hapus permanen terpilih
                    </button>
                    <button
                        type="button"
                        onClick={() => setSelected([])}
                        className="text-(--color-text-muted) hover:underline"
                    >
                        Batalkan pilihan
                    </button>
                </div>
            )}

            {/* Table */}
            <div className="bg-(--color-surface) border border-(--color-border) rounded-xl overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-(--color-border) text-left text-xs uppercase tracking-wider text-(--color-text-muted)">
                            <th className="px-4 py-3 w-10">
                                <input
                                    type="checkbox"
                                    checked={Boolean(allSelected)}
                                    onChange={toggleAll}
                                    disabled={!posts.length}
                                    className="rounded accent-(--color-accent)"
                                    aria-label="Pilih semua"
                                />
                            </th>
                            <th className="px-4 py-3">Judul</th>
                            <th className="px-4 py-3">Status</th>
                            <th className="px-4 py-3">Dihapus</th>
                            <th className="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-(--color-border)">
                        {isLoading && (
                            <tr>
                                <td colSpan="5" className="px-4 py-6 text-center text-(--color-text-muted)">
                                    Memuat...
                                </td>
                            </tr>
                        )}
                        {!isLoading && posts.length === 0 && (
                            <tr>
                                <td colSpan="5" className="px-4 py-6 text-center text-(--color-text-muted)">
                                    Sampah kosong.
                                </td>
                            </tr>
                        )}
                        {posts.map((post) => (
                            <tr key={post.id} className="hover:bg-(--color-accent-bg)/40">
                                <td className="px-4 py-3">
                                    <input
                                        type="checkbox"
                                        checked={selected.includes(post.id)}
                                        onChange={() => toggleOne(post.id)}
                                        className="rounded accent-(--color-accent)"
                                        aria-label={`Pilih ${post.title}`}
                                    />
                                </td>
                                <td className="px-4 py-3">
                                    <p className="font-medium truncate max-w-xs" title={post.title}>
                                        {post.title}
                                    </p>
                                    <p className="text-xs text-(--color-text-muted) truncate max-w-xs">
                                        /blog/{post.slug}
                                    </p>
                                </td>
                                <td className="px-4 py-3">
                                    <StatusBadge status="trashed" />
                                </td>
                                <td className="px-4 py-3 text-xs text-(--color-text-muted)">
                                    {formatDate(post.deleted_at)}
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex items-center justify-end gap-3 text-xs">
                                        <Link
                                            to={`/admin/posts/${post.id}/view`}
                                            className="text-(--color-accent) hover:underline"
                                        >
                                            Lihat
                                        </Link>
                                        <button
                                            type="button"
                                            onClick={() => handleRestore(post)}
                                            className="text-(--color-success) hover:underline"
                                        >
                                            Pulihkan
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => askForceDeleteOne(post)}
                                            className="text-(--color-error) hover:underline"
                                        >
                                            Hapus permanen
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Pagination */}
            {data && data.last_page > 1 && (
                <div className="flex items-center justify-between text-sm">
                    <p className="text-(--color-text-muted)">
                        Halaman {data.current_page} dari {data.last_page} · {data.total} artikel
                    </p>
                    <div className="flex gap-2">
                        <button
                            type="button"
                            disabled={!data.prev_page_url}
                            onClick={() => gotoPage(page - 1)}
                            className="px-3 py-1.5 rounded-lg border border-(--color-border) disabled:opacity-40 hover:bg-(--color-accent-bg)"
                        >
                            Sebelumnya
                        </button>
                        <button
                            type="button"
                            disabled={!data.next_page_url}
                            onClick={() => gotoPage(page + 1)}
                            className="px-3 py-1.5 rounded-lg border border-(--color-border) disabled:opacity-40 hover:bg-(--color-accent-bg)"
                        >
                            Berikutnya
                        </button>
                    </div>
                </div>
            )}

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
