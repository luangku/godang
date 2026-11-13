import { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api, endpoints, errorMessage } from '../api';
import StatusBadge from '../components/StatusBadge';
import ConfirmModal from '../components/ConfirmModal';
import { Toast, useToast } from '../components/Toast';

function filtersFromParams(params) {
    return {
        q: params.get('q') || '',
        status: params.get('status') || '',
    };
}

export default function Posts() {
    const [searchParams, setSearchParams] = useSearchParams();
    const filters = filtersFromParams(searchParams);
    const page = Number(searchParams.get('page') || 1);
    const queryClient = useQueryClient();
    const { toast, show } = useToast();

    const [searchDraft, setSearchDraft] = useState(filters.q);
    const [selected, setSelected] = useState([]);
    const [confirm, setConfirm] = useState(null);
    const [busy, setBusy] = useState(false);

    const setFilter = (patch) => {
        const next = new URLSearchParams(searchParams);
        Object.entries(patch).forEach(([key, value]) => {
            if (value === '' || value === false || value === null) next.delete(key);
            else next.set(key, String(value));
        });
        next.delete('page');
        setSearchParams(next);
        setSelected([]);
    };

    const { data, isLoading } = useQuery({
        queryKey: ['posts', searchParams.toString()],
        queryFn: async () => (await api.get(endpoints.posts.list, { params: Object.fromEntries(searchParams) })).data,
    });

    const invalidate = () => {
        queryClient.invalidateQueries({ queryKey: ['posts'] });
        queryClient.invalidateQueries({ queryKey: ['dashboard'] });
    };

    const runMutation = useMutation({
        mutationFn: async ({ method, url, payload }) =>
            (await api({ method, url, data: payload })).data,
        onSuccess: (response) => {
            invalidate();
            show(response.message || 'Berhasil.');
        },
        onError: (error) => show(errorMessage(error), 'error'),
    });

    const askDelete = () => {
        setConfirm({
            title: 'Hapus artikel',
            message: `Hapus ${selected.length} artikel terpilih? Artikel masuk ke sampah dan bisa dipulihkan.`,
            confirmLabel: 'Hapus',
            action: async () => {
                setBusy(true);
                try {
                    await runMutation.mutateAsync({
                        method: 'POST',
                        url: endpoints.posts.bulkDelete,
                        payload: { ids: selected },
                    });
                    setSelected([]);
                    setConfirm(null);
                } finally {
                    setBusy(false);
                }
            },
        });
    };

    const askDeleteOne = (post) => {
        setConfirm({
            title: 'Hapus artikel',
            message: `Hapus "${post.title}"? Artikel masuk ke sampah dan bisa dipulihkan.`,
            confirmLabel: 'Hapus',
            action: async () => {
                setBusy(true);
                try {
                    await runMutation.mutateAsync({ method: 'DELETE', url: `/admin/posts/${post.id}` });
                    setConfirm(null);
                } finally {
                    setBusy(false);
                }
            },
        });
    };

    const toggleOne = (id) => {
        setSelected((current) =>
            current.includes(id) ? current.filter((item) => item !== id) : [...current, id],
        );
    };

    const allSelected = data?.data?.length > 0 && data.data.every((post) => selected.includes(post.id));

    const toggleAll = () => {
        if (allSelected) {
            setSelected([]);
        } else {
            setSelected(data.data.map((post) => post.id));
        }
    };

    const gotoPage = (nextPage) => {
        const next = new URLSearchParams(searchParams);
        if (nextPage <= 1) next.delete('page');
        else next.set('page', String(nextPage));
        setSearchParams(next);
        window.scrollTo({ top: 0 });
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h1 className="text-xl font-bold">Artikel</h1>
                <Link
                    to="/admin/posts/create"
                    className="px-4 py-2 rounded-lg bg-(--color-accent) text-(--color-on-accent) text-sm font-medium hover:opacity-90 transition-opacity"
                >
                    Tulis Artikel
                </Link>
            </div>

            {/* Filters */}
            <form
                className="flex flex-col sm:flex-row gap-3"
                onSubmit={(event) => {
                    event.preventDefault();
                    setFilter({ q: searchDraft.trim() });
                }}
            >
                <input
                    type="search"
                    value={searchDraft}
                    onChange={(event) => {
                        const value = event.target.value;
                        setSearchDraft(value);

                        if (value === '' && filters.q) {
                            setFilter({ q: '' });
                        }
                    }}
                    placeholder="Cari judul atau excerpt..."
                    className="flex-1 px-4 py-2 rounded-lg bg-(--color-surface) border border-(--color-border) text-sm focus:border-(--color-accent) outline-none"
                />
                <select
                    value={filters.status}
                    onChange={(event) => setFilter({ status: event.target.value })}
                    className="px-3 py-2 rounded-lg bg-(--color-surface) border border-(--color-border) text-sm"
                >
                    <option value="">Semua status</option>
                    <option value="draft">Draft</option>
                    <option value="published">Terbit</option>
                    <option value="scheduled">Terjadwal</option>
                </select>
                <Link
                    to="/admin/posts/trash"
                    title="Sampah"
                    aria-label="Buka sampah"
                    className="flex items-center justify-center px-3 py-2 rounded-lg border border-(--color-border) text-(--color-text-muted) hover:text-(--color-accent) hover:border-(--color-accent) transition-colors"
                >
                    <svg
                        className="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        viewBox="0 0 24 24"
                    >
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                        <line x1="10" y1="11" x2="10" y2="17" />
                        <line x1="14" y1="11" x2="14" y2="17" />
                    </svg>
                </Link>
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
                        onClick={askDelete}
                        className="font-medium text-(--color-error) hover:underline"
                    >
                        Hapus terpilih
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
                                    disabled={!data?.data?.length}
                                    className="rounded accent-(--color-accent)"
                                    aria-label="Pilih semua"
                                />
                            </th>
                            <th className="px-4 py-3">Judul</th>
                            <th className="px-4 py-3">Status</th>
                            <th className="px-4 py-3 text-right">Views</th>
                            <th className="px-4 py-3">Diperbarui</th>
                            <th className="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-(--color-border)">
                        {isLoading && (
                            <tr>
                                <td colSpan="6" className="px-4 py-6 text-center text-(--color-text-muted)">
                                    Memuat...
                                </td>
                            </tr>
                        )}
                        {!isLoading && data?.data?.length === 0 && (
                            <tr>
                                <td colSpan="6" className="px-4 py-6 text-center text-(--color-text-muted)">
                                    Tidak ada artikel.
                                </td>
                            </tr>
                        )}
                        {data?.data?.map((post) => (
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
                                    <StatusBadge status={post.status} />
                                </td>
                                <td className="px-4 py-3 text-right">{post.views_count}</td>
                                <td className="px-4 py-3 text-xs text-(--color-text-muted)">
                                    {new Date(post.updated_at).toLocaleDateString('id-ID')}
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex items-center justify-end gap-3 text-xs">
                                        <Link
                                            to={`/admin/posts/${post.id}/view`}
                                            className="text-(--color-accent) hover:underline"
                                        >
                                            Lihat
                                        </Link>
                                        <Link
                                            to={`/admin/posts/${post.id}/edit`}
                                            className="text-(--color-accent) hover:underline"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            type="button"
                                            onClick={() => askDeleteOne(post)}
                                            className="text-(--color-error) hover:underline"
                                        >
                                            Hapus
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
