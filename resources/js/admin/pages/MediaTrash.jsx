import { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api, endpoints, errorMessage } from '../api';
import ConfirmModal from '../components/ConfirmModal';
import { Toast, useToast } from '../components/Toast';
import { resolveMediaUrl, resolveThumbUrl } from '../media';

const FOLDER_LABELS = {
    uploads: 'Gambar artikel',
    cover: 'Cover & OG',
    avatar: 'Avatar & Logo',
    files: 'File',
    other: 'Folder lain',
};

const MEDIA_TABS = [
    { value: 'image', label: 'Gambar' },
    { value: 'file', label: 'File' },
];

function fileExtension(item) {
    const name = item.original_name || item.path || '';
    const dot = name.lastIndexOf('.');

    return dot === -1 ? 'FILE' : name.slice(dot + 1).toUpperCase();
}

function formatDate(value) {
    if (!value) return '';

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return '';

    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}

function mediaLabel(item) {
    if (item.type === 'file') {
        return item.original_name || item.alt_text || item.path;
    }

    return item.alt_text || item.path;
}

export default function MediaTrash() {
    const queryClient = useQueryClient();
    const { toast, show } = useToast();
    const [searchParams, setSearchParams] = useSearchParams();
    const mediaType = searchParams.get('type') === 'file' ? 'file' : 'image';
    const page = Math.max(1, Number(searchParams.get('page') || 1));
    const [searchDraft, setSearchDraft] = useState(searchParams.get('q') || '');
    const [selected, setSelected] = useState([]);
    const [confirm, setConfirm] = useState(null);
    const [busy, setBusy] = useState(false);

    useEffect(() => {
        setSearchDraft(searchParams.get('q') || '');
    }, [searchParams]);

    useEffect(() => {
        setSelected([]);
    }, [searchParams.toString()]);

    const setQuery = (key, value) => {
        const next = new URLSearchParams(searchParams);

        if (!value) next.delete(key);
        else next.set(key, value);

        next.delete('page');
        setSearchParams(next);
        setSelected([]);
    };

    const setMediaType = (nextType) => {
        const next = new URLSearchParams();
        if (nextType === 'file') next.set('type', 'file');

        setSearchParams(next);
        setSelected([]);
    };

    const gotoPage = (nextPage) => {
        const next = new URLSearchParams(searchParams);

        if (nextPage <= 1) next.delete('page');
        else next.set('page', String(nextPage));

        setSearchParams(next);
        setSelected([]);
        window.scrollTo({ top: 0 });
    };

    const { data, isLoading } = useQuery({
        queryKey: ['media', 'trash', searchParams.toString()],
        queryFn: async () => {
            const params = Object.fromEntries(searchParams.entries());
            params.trashed = '1';
            params.type = mediaType;
            params.per_page = 24;

            return (await api.get(endpoints.media, { params })).data;
        },
    });

    const invalidate = () => {
        queryClient.invalidateQueries({ queryKey: ['media'] });
        queryClient.invalidateQueries({ queryKey: ['media-picker'] });
        queryClient.invalidateQueries({ queryKey: ['media-categories'] });
    };

    const mutation = useMutation({
        mutationFn: async ({ method, url, payload }) => (await api({ method, url, data: payload })).data,
        onSuccess: (response) => {
            invalidate();
            show(response.message || 'Berhasil.');
        },
        onError: (error) => {
            const blocked = error?.response?.data?.blocked;

            if (Array.isArray(blocked) && blocked.length > 0) {
                const names = blocked
                    .slice(0, 3)
                    .map((item) => item.alt_text || item.path)
                    .join(', ');
                const suffix = blocked.length > 3 ? ` dan ${blocked.length - 3} lainnya` : '';

                show(`${errorMessage(error)} Masih dipakai: ${names}${suffix}.`, 'error');

                return;
            }

            show(errorMessage(error), 'error');
        },
    });

    const restoreOne = async (item) => {
        try {
            await mutation.mutateAsync({ method: 'POST', url: endpoints.mediaRestore(item.id) });
            setSelected((current) => current.filter((id) => id !== item.id));

            if (data?.data?.length === 1 && page > 1) {
                gotoPage(page - 1);
            }
        } catch {
            /* toast handled in mutation */
        }
    };

    const askRestoreSelected = () => {
        setConfirm({
            title: 'Pulihkan media',
            message: `Pulihkan ${selected.length} media terpilih ke perpustakaan media?`,
            confirmLabel: 'Pulihkan',
            action: async () => {
                setBusy(true);
                try {
                    await mutation.mutateAsync({
                        method: 'POST',
                        url: endpoints.mediaBulkRestore,
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

    const askForceDeleteOne = (item) => {
        const used = (item.usage_count ?? 0) > 0;

        setConfirm({
            title: 'Hapus permanen',
            message: used
                ? `"${mediaLabel(item)}" masih dipakai ${item.usage_count}×: ${(item.usage_labels ?? []).join('; ')}. Hapus permanen akan ditolak sampai referensinya dilepas.`
                : `Hapus permanen "${mediaLabel(item)}"? File asli dan thumbnail akan dihapus dan tidak dapat dipulihkan.`,
            confirmLabel: 'Hapus permanen',
            action: async () => {
                setBusy(true);
                try {
                    await mutation.mutateAsync({ method: 'DELETE', url: endpoints.mediaForceDelete(item.id) });
                    setConfirm(null);
                    setSelected((current) => current.filter((id) => id !== item.id));

                    if (data?.data?.length === 1 && page > 1) {
                        gotoPage(page - 1);
                    }
                } finally {
                    setBusy(false);
                }
            },
        });
    };

    const askBulkForceDelete = () => {
        const selectedItems = (data?.data ?? []).filter((item) => selected.includes(item.id));
        const usedCount = selectedItems.filter((item) => (item.usage_count ?? 0) > 0).length;

        setConfirm({
            title: 'Hapus permanen',
            message: usedCount > 0
                ? `${selected.length} media akan dihapus permanen, tetapi ${usedCount} di antaranya masih dipakai. Hapus permanen akan ditolak sampai semua referensinya dilepas.`
                : `Hapus permanen ${selected.length} media terpilih? File asli dan thumbnail akan dihapus dan tidak dapat dipulihkan.`,
            confirmLabel: 'Hapus permanen',
            action: async () => {
                setBusy(true);
                try {
                    await mutation.mutateAsync({
                        method: 'POST',
                        url: endpoints.mediaBulkForceDelete,
                        payload: { ids: selected },
                    });
                    setSelected([]);
                    setConfirm(null);

                    if (data?.data?.length === selected.length && page > 1) {
                        gotoPage(page - 1);
                    }
                } finally {
                    setBusy(false);
                }
            },
        });
    };

    const toggleOne = (id) => {
        setSelected((current) => (current.includes(id) ? current.filter((item) => item !== id) : [...current, id]));
    };

    const allSelected = Boolean(data?.data?.length) && data.data.every((item) => selected.includes(item.id));

    const toggleAll = () => {
        if (allSelected) setSelected([]);
        else setSelected(data.data.map((item) => item.id));
    };

    const items = data?.data ?? [];

    return (
        <div className="space-y-5">
            <div className="flex items-center gap-3">
                <Link to="/admin/media" className="text-sm text-(--color-text-muted) hover:text-(--color-accent)">
                    &larr; Kembali
                </Link>
                <h1 className="text-xl font-bold">Sampah media</h1>
            </div>

            <p className="text-sm text-(--color-text-muted)">
                Media di sini sudah tidak tampil di perpustakaan, tetapi filenya masih aman. Pulihkan kapan saja, atau
                hapus permanen setelah referensinya dilepas.
            </p>

            <div role="tablist" aria-label="Jenis media di sampah" className="flex gap-1 border-b border-(--color-border)">
                {MEDIA_TABS.map((tab) => {
                    const active = mediaType === tab.value;

                    return (
                        <button
                            key={tab.value}
                            type="button"
                            role="tab"
                            aria-selected={active}
                            onClick={() => setMediaType(tab.value)}
                            className={`-mb-px border-b-2 px-4 py-2 text-sm font-medium transition-colors ${
                                active
                                    ? 'border-(--color-accent) text-(--color-accent)'
                                    : 'border-transparent text-(--color-text-muted) hover:text-(--color-text)'
                            }`}
                        >
                            {tab.label}
                        </button>
                    );
                })}
            </div>

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
                    placeholder="Cari alt text atau nama file di sampah..."
                    aria-label="Cari media di sampah"
                    className="flex-1 rounded-lg border border-(--color-border) bg-(--color-surface) px-4 py-2 text-sm outline-none focus:border-(--color-accent)"
                />
                <button
                    type="submit"
                    className="rounded-lg bg-(--color-accent) px-4 py-2 text-sm font-medium text-(--color-on-accent) hover:opacity-90"
                >
                    Cari
                </button>
            </form>

            {items.length > 0 && (
                <div className="flex flex-wrap items-center gap-3 text-xs">
                    <label className="inline-flex items-center gap-2 text-(--color-text-muted)">
                        <input
                            type="checkbox"
                            checked={allSelected}
                            onChange={toggleAll}
                            className="rounded accent-(--color-accent)"
                            aria-label="Pilih semua media di halaman ini"
                        />
                        Pilih semua di halaman ini
                    </label>
                    {selected.length > 0 && (
                        <span className="text-(--color-accent)">{selected.length} terpilih</span>
                    )}
                </div>
            )}

            {selected.length > 0 && (
                <div className="flex flex-wrap items-center gap-3 rounded-lg border border-(--color-accent) bg-(--color-accent-bg) px-4 py-2 text-sm">
                    <span>{selected.length} terpilih</span>
                    <button
                        type="button"
                        onClick={askRestoreSelected}
                        className="font-medium text-(--color-success) hover:underline"
                    >
                        Pulihkan terpilih
                    </button>
                    <button
                        type="button"
                        onClick={askBulkForceDelete}
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

            {isLoading && <p className="text-sm text-(--color-text-muted)">Memuat...</p>}

            {!isLoading && items.length === 0 && (
                <div className="rounded-xl border border-(--color-border) bg-(--color-surface) p-10 text-center">
                    <p className="text-sm text-(--color-text-muted)">
                        {searchParams.get('q') ? 'Tidak ada media yang cocok di sampah.' : 'Sampah media kosong.'}
                    </p>
                </div>
            )}

            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                {items.map((item) => {
                    const folderKey = FOLDER_LABELS[item.folder] ? item.folder : 'other';
                    const label = mediaLabel(item);
                    const isSelected = selected.includes(item.id);
                    const used = (item.usage_count ?? 0) > 0;

                    return (
                        <figure
                            key={item.id}
                            className={`group overflow-hidden rounded-xl border bg-(--color-surface) ${
                                isSelected ? 'border-(--color-accent) ring-2 ring-(--color-accent)' : 'border-(--color-border)'
                            }`}
                        >
                            <div className="relative flex aspect-square items-center justify-center overflow-hidden bg-(--color-bg)">
                                {item.type === 'file' ? (
                                    <div className="flex flex-col items-center gap-2 px-4 text-center opacity-80">
                                        <span className="rounded-lg border border-(--color-border) bg-(--color-surface) px-3 py-2 text-lg font-bold tracking-wide text-(--color-text-muted)">
                                            {fileExtension(item)}
                                        </span>
                                        <span className="line-clamp-2 text-[11px] text-(--color-text-muted)" title={item.original_name || item.path}>
                                            {item.original_name || item.path}
                                        </span>
                                    </div>
                                ) : (
                                    <img
                                        src={resolveThumbUrl(item)}
                                        alt={item.alt_text || ''}
                                        loading="lazy"
                                        className="max-h-full max-w-full object-contain opacity-80"
                                    />
                                )}

                                <label className="absolute left-2 top-2 z-10 inline-flex h-6 w-6 cursor-pointer items-center justify-center rounded-md border border-(--color-border) bg-(--color-surface)/90">
                                    <input
                                        type="checkbox"
                                        checked={isSelected}
                                        onChange={() => toggleOne(item.id)}
                                        className="h-4 w-4 rounded accent-(--color-accent)"
                                        aria-label={`Pilih ${label}`}
                                    />
                                </label>

                                <span className="absolute right-2 top-2 rounded-full bg-red-500/90 px-2 py-0.5 text-[10px] font-medium text-white">
                                    Sampah
                                </span>

                                {used ? (
                                    <span
                                        className="absolute bottom-2 left-2 rounded-full bg-amber-500/90 px-2 py-0.5 text-[10px] font-medium text-white"
                                        title={(item.usage_labels ?? []).join('; ')}
                                    >
                                        Masih dipakai {item.usage_count}×
                                    </span>
                                ) : (
                                    <span className="absolute bottom-2 left-2 rounded-full bg-emerald-500/90 px-2 py-0.5 text-[10px] font-medium text-white">
                                        Bisa dihapus permanen
                                    </span>
                                )}
                            </div>

                            <figcaption className="space-y-2 p-3">
                                <div className="space-y-0.5">
                                    <p className="truncate text-xs font-medium" title={label}>
                                        {label}
                                    </p>
                                    <p className="truncate text-[11px] text-(--color-text-muted)" title={item.path}>
                                        {item.path}
                                    </p>
                                </div>

                                <div className="flex flex-wrap items-center gap-1.5">
                                    <span className="rounded-full bg-(--color-bg) px-2 py-0.5 text-[10px] font-medium text-(--color-text-muted)">
                                        {FOLDER_LABELS[folderKey]}
                                    </span>
                                    {item.category ? (
                                        <span className="rounded-full bg-(--color-accent-bg) px-2 py-0.5 text-[10px] font-medium text-(--color-accent)">
                                            {item.category.name}
                                        </span>
                                    ) : (
                                        <span className="text-[10px] text-(--color-text-muted)">Tanpa kategori</span>
                                    )}
                                </div>

                                <p className="text-[11px] text-(--color-text-muted)">
                                    Dihapus {formatDate(item.deleted_at)}
                                </p>

                                <div className="flex flex-wrap gap-x-3 gap-y-1 text-xs">
                                    <button
                                        type="button"
                                        onClick={() => restoreOne(item)}
                                        className="text-(--color-success) hover:underline"
                                    >
                                        Pulihkan
                                    </button>
                                    <a
                                        href={resolveMediaUrl(item)}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="text-(--color-accent) hover:underline"
                                    >
                                        Buka
                                    </a>
                                    <button
                                        type="button"
                                        onClick={() => askForceDeleteOne(item)}
                                        className="text-(--color-error) hover:underline"
                                    >
                                        Hapus permanen
                                    </button>
                                </div>
                            </figcaption>
                        </figure>
                    );
                })}
            </div>

            {data && data.last_page > 1 && (
                <div className="flex flex-col gap-2 text-sm sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-(--color-text-muted)">
                        Menampilkan {data.from}–{data.to} dari {data.total} media di sampah
                    </p>
                    <div className="flex items-center gap-2">
                        <button
                            type="button"
                            disabled={!data.prev_page_url}
                            onClick={() => gotoPage(page - 1)}
                            className="rounded-lg border border-(--color-border) px-3 py-1.5 hover:bg-(--color-accent-bg) disabled:opacity-40"
                        >
                            Sebelumnya
                        </button>
                        <span className="text-xs text-(--color-text-muted)">
                            {data.current_page} / {data.last_page}
                        </span>
                        <button
                            type="button"
                            disabled={!data.next_page_url}
                            onClick={() => gotoPage(page + 1)}
                            className="rounded-lg border border-(--color-border) px-3 py-1.5 hover:bg-(--color-accent-bg) disabled:opacity-40"
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
