import { useEffect, useRef, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api, endpoints, errorMessage } from '../api';
import ConfirmModal from '../components/ConfirmModal';
import MediaCategoryManager from '../components/MediaCategoryManager';
import MediaEditModal from '../components/MediaEditModal';
import { Toast, useToast } from '../components/Toast';
import { resolveMediaUrl, resolveThumbUrl } from '../media';

const FOLDER_OPTIONS = [
    { value: '', label: 'Semua' },
    { value: 'uploads', label: 'Gambar artikel' },
    { value: 'cover', label: 'Cover & OG' },
    { value: 'avatar', label: 'Avatar & Logo' },
];

const FOLDER_VALUES = FOLDER_OPTIONS.map((option) => option.value).filter(Boolean);

const UPLOAD_FOLDERS = FOLDER_OPTIONS.filter((option) => option.value);

const FOLDER_LABELS = {
    uploads: 'Gambar artikel',
    cover: 'Cover & OG',
    avatar: 'Avatar & Logo',
    files: 'File',
    other: 'Folder lain',
};

const FOLDER_BADGE_CLASSES = {
    uploads: 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
    cover: 'bg-purple-500/10 text-purple-600 dark:text-purple-400',
    avatar: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    files: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
    other: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
};

const SORT_OPTIONS = [
    { value: '', label: 'Terbaru' },
    { value: 'oldest', label: 'Terlama' },
    { value: 'name', label: 'Nama A-Z' },
    { value: 'name_desc', label: 'Nama Z-A' },
    { value: 'largest', label: 'Ukuran terbesar' },
    { value: 'smallest', label: 'Ukuran terkecil' },
];

const FILE_ACCEPT = '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip';

const MEDIA_TABS = [
    { value: 'image', label: 'Gambar' },
    { value: 'file', label: 'File' },
];

function fileExtension(item) {
    const name = item.original_name || item.path || '';
    const dot = name.lastIndexOf('.');

    return dot === -1 ? 'FILE' : name.slice(dot + 1).toUpperCase();
}

function filtersFromParams(params) {
    const folder = params.get('folder') || '';

    return {
        q: params.get('q') || '',
        folder: FOLDER_VALUES.includes(folder) ? folder : '',
        category: params.get('category') || '',
        sort: params.get('sort') || '',
        unused: params.get('unused') === '1',
    };
}

function formatSize(bytes) {
    if (bytes > 1024 * 1024) return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    if (bytes > 1024) return `${Math.round(bytes / 1024)} KB`;
    return `${bytes} B`;
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

async function copyToClipboard(text) {
    if (navigator.clipboard?.writeText) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch {
            // Clipboard API unavailable (e.g. insecure context); use fallback.
        }
    }

    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.top = '-1000px';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    textarea.setSelectionRange(0, textarea.value.length);

    let copied = false;
    try {
        copied = document.execCommand('copy');
    } catch {
        copied = false;
    } finally {
        document.body.removeChild(textarea);
    }

    return copied;
}

export default function Media() {
    const queryClient = useQueryClient();
    const { toast, show } = useToast();
    const fileInputRef = useRef(null);
    const [searchParams, setSearchParams] = useSearchParams();
    const filters = filtersFromParams(searchParams);
    const mediaType = searchParams.get('type') === 'file' ? 'file' : 'image';
    const isFile = mediaType === 'file';
    const page = Math.max(1, Number(searchParams.get('page') || 1));
    const [searchDraft, setSearchDraft] = useState(filters.q);
    const [altText, setAltText] = useState('');
    const [uploadFolder, setUploadFolder] = useState('uploads');
    const [uploadCategory, setUploadCategory] = useState('');
    const [uploading, setUploading] = useState(false);
    const [selected, setSelected] = useState([]);
    const [editingMedia, setEditingMedia] = useState(null);
    const [managingCategories, setManagingCategories] = useState(false);
    const [confirm, setConfirm] = useState(null);
    const [busy, setBusy] = useState(false);

    useEffect(() => {
        setSearchDraft(filters.q);
    }, [filters.q]);

    useEffect(() => {
        setSelected([]);
    }, [searchParams.toString()]);

    useEffect(() => {
        const folder = searchParams.get('folder');

        if (!folder || FOLDER_VALUES.includes(folder)) return;

        const next = new URLSearchParams(searchParams);
        next.delete('folder');
        next.delete('page');
        setSearchParams(next, { replace: true });
    }, [searchParams, setSearchParams]);

    const setFilter = (patch) => {
        const next = new URLSearchParams(searchParams);

        Object.entries(patch).forEach(([key, value]) => {
            if (value === '' || value === null || value === false) next.delete(key);
            else next.set(key, value === true ? '1' : String(value));
        });

        next.delete('page');
        setSearchParams(next);
        setSelected([]);
    };

    const resetFilters = () => {
        const next = new URLSearchParams();
        if (isFile) next.set('type', 'file');

        setSearchDraft('');
        setSearchParams(next);
        setSelected([]);
    };

    const setMediaType = (nextType) => {
        const next = new URLSearchParams();
        if (nextType === 'file') next.set('type', 'file');

        setSearchDraft('');
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

    const { data, isLoading, isFetching } = useQuery({
        queryKey: ['media', searchParams.toString()],
        queryFn: async () => {
            const params = Object.fromEntries(searchParams.entries());
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
        mutationFn: async ({ method, url, payload, config }) => (await api({ method, url, data: payload, ...config })).data,
        onSuccess: (resp) => {
            invalidate();
            show(resp.message || 'Berhasil.');
        },
        onError: (error) => show(errorMessage(error), 'error'),
    });

    const handleUpload = async (event) => {
        const file = event.target.files?.[0];

        if (!file) return;

        setUploading(true);
        try {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', mediaType);
            if (!isFile) formData.append('folder', uploadFolder);
            if (altText.trim()) formData.append('alt_text', altText.trim());
            if (uploadCategory) formData.append('category_id', uploadCategory);

            await mutation.mutateAsync({
                method: 'POST',
                url: endpoints.media,
                payload: formData,
                config: { headers: { 'Content-Type': 'multipart/form-data' } },
            });

            setAltText('');
            setFilter(isFile ? { q: '', category: uploadCategory } : { folder: uploadFolder, category: uploadCategory, q: '' });
        } catch {
            /* toast handled in mutation */
        } finally {
            setUploading(false);
            event.target.value = '';
        }
    };

    const deleteMedia = (item) => {
        const label = mediaLabel(item);
        const used = (item.usage_count ?? 0) > 0;

        setConfirm({
            title: 'Pindahkan ke sampah',
            message: used
                ? `"${label}" masih dipakai ${item.usage_count}×: ${(item.usage_labels ?? []).join('; ')}. Media akan dipindahkan ke sampah dan file tetap aman. Hapus permanen baru bisa dilakukan setelah referensinya dilepas.`
                : `Pindahkan "${label}" ke sampah? File tetap aman sampai dihapus permanen dari halaman sampah.`,
            confirmLabel: 'Pindahkan ke sampah',
            action: async () => {
                setBusy(true);
                try {
                    await mutation.mutateAsync({ method: 'DELETE', url: `${endpoints.media}/${item.id}` });
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

    const askBulkDelete = () => {
        const usedCount = (data?.data ?? []).filter(
            (item) => selected.includes(item.id) && (item.usage_count ?? 0) > 0,
        ).length;

        setConfirm({
            title: 'Pindahkan ke sampah',
            message: usedCount > 0
                ? `${selected.length} media akan dipindahkan ke sampah. ${usedCount} di antaranya masih dipakai; file tetap aman sampai referensinya dilepas lalu dihapus permanen.`
                : `${selected.length} media akan dipindahkan ke sampah. File tetap aman sampai dihapus permanen dari halaman sampah.`,
            confirmLabel: 'Pindahkan ke sampah',
            action: async () => {
                setBusy(true);
                try {
                    await mutation.mutateAsync({
                        method: 'POST',
                        url: endpoints.mediaBulkDelete,
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

    const copyUrl = async (item) => {
        const url = resolveMediaUrl(item);

        if (!url) {
            show('URL media tidak tersedia.', 'error');
            return;
        }

        const copied = await copyToClipboard(url);
        show(copied ? 'URL disalin.' : 'Gagal menyalin URL.', copied ? 'success' : 'error');
    };

    const toggleOne = (id) => {
        setSelected((current) => (current.includes(id) ? current.filter((item) => item !== id) : [...current, id]));
    };

    const allSelected = Boolean(data?.data?.length) && data.data.every((item) => selected.includes(item.id));

    const toggleAll = () => {
        if (allSelected) setSelected([]);
        else setSelected(data.data.map((item) => item.id));
    };

    const hasFilters = Boolean(filters.q || filters.folder || filters.category || filters.sort || filters.unused);
    const folderCount = (value) => (value === '' ? data?.folders?.all : data?.folders?.[value]) ?? 0;
    const categories = data?.categories ?? [];

    return (
        <div className="space-y-5">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 className="text-xl font-bold">Media</h1>
                    <p className="text-sm text-(--color-text-muted)">
                        {data?.total ?? 0} {isFile ? 'file' : 'gambar'} {hasFilters ? 'cocok dengan filter' : 'tersimpan'}
                    </p>
                </div>
                <Link
                    to="/admin/media/trash"
                    className="inline-flex items-center gap-2 self-start rounded-lg border border-(--color-border) px-3 py-2 text-sm hover:bg-(--color-accent-bg)"
                >
                    Sampah media
                </Link>
            </div>

            <div role="tablist" aria-label="Jenis media" className="flex gap-1 border-b border-(--color-border)">
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

            <div className="space-y-3 rounded-xl border border-(--color-border) bg-(--color-surface) p-4">
                <div className="flex flex-col gap-3 lg:flex-row">
                    <form
                        className="flex w-full gap-2 lg:max-w-md"
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
                            placeholder={isFile ? 'Cari nama atau label file...' : 'Cari nama file atau alt text...'}
                            aria-label="Cari media"
                            className="flex-1 rounded-lg border border-(--color-border) bg-(--color-bg) px-4 py-2 text-sm"
                        />
                        <button
                            type="submit"
                            className="rounded-lg bg-(--color-accent) px-4 py-2 text-sm font-medium text-(--color-on-accent) hover:opacity-90"
                        >
                            Cari
                        </button>
                    </form>

                    <div className="flex flex-wrap gap-2">
                        <select
                            value={filters.category}
                            onChange={(event) => setFilter({ category: event.target.value })}
                            aria-label="Filter kategori media"
                            className="min-w-0 rounded-lg border border-(--color-border) bg-(--color-surface) px-3 py-2 text-sm"
                        >
                            <option value="">Semua kategori</option>
                            {categories.map((category) => (
                                <option key={category.id} value={category.id}>
                                    {category.name} ({category.count})
                                </option>
                            ))}
                            <option value="none">Tanpa kategori ({data?.uncategorized_count ?? 0})</option>
                        </select>

                        <select
                            value={filters.sort}
                            onChange={(event) => setFilter({ sort: event.target.value })}
                            aria-label="Urutkan media"
                            className="min-w-0 rounded-lg border border-(--color-border) bg-(--color-surface) px-3 py-2 text-sm"
                        >
                            {SORT_OPTIONS.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>

                        <button
                            type="button"
                            onClick={() => setManagingCategories(true)}
                            className="rounded-lg border border-(--color-border) px-3 py-2 text-sm hover:bg-(--color-accent-bg)"
                        >
                            Kelola kategori
                        </button>
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    {!isFile && FOLDER_OPTIONS.map((option) => {
                        const active = filters.folder === option.value;

                        return (
                            <button
                                key={option.value || 'all'}
                                type="button"
                                onClick={() => setFilter({ folder: option.value })}
                                aria-pressed={active}
                                className={`inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-medium transition-colors ${
                                    active
                                        ? 'border-(--color-accent) bg-(--color-accent-bg) text-(--color-accent)'
                                        : 'border-(--color-border) text-(--color-text-muted) hover:border-(--color-accent) hover:text-(--color-accent)'
                                }`}
                            >
                                {option.label}
                                <span className={`text-[11px] ${active ? '' : 'opacity-70'}`}>{folderCount(option.value)}</span>
                            </button>
                        );
                    })}

                    <button
                        type="button"
                        onClick={() => setFilter({ unused: !filters.unused })}
                        aria-pressed={filters.unused}
                        className={`inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-medium transition-colors ${
                            filters.unused
                                ? 'border-(--color-warning) bg-(--color-warning)/10 text-(--color-warning)'
                                : 'border-(--color-border) text-(--color-text-muted) hover:border-(--color-warning) hover:text-(--color-warning)'
                        }`}
                    >
                        Belum dipakai
                        <span className={`text-[11px] ${filters.unused ? '' : 'opacity-70'}`}>{data?.unused_count ?? 0}</span>
                    </button>

                    {hasFilters && (
                        <button
                            type="button"
                            onClick={resetFilters}
                            className="ml-auto text-xs text-(--color-text-muted) hover:text-(--color-error) hover:underline"
                        >
                            Reset filter
                        </button>
                    )}
                </div>
            </div>

            <div className="space-y-3 rounded-xl border border-(--color-border) bg-(--color-surface) p-4">
                <p className="text-sm font-medium">Unggah {isFile ? 'file' : 'gambar'}</p>

                <div className="flex flex-col gap-3 lg:flex-row lg:items-start">
                    <div className="flex-1 lg:max-w-md">
                        <input
                            type="text"
                            value={altText}
                            onChange={(event) => setAltText(event.target.value)}
                            placeholder={isFile ? 'Label file (opsional)' : 'Alt text (opsional)'}
                            aria-label={isFile ? 'Label untuk upload berikutnya' : 'Alt text untuk upload berikutnya'}
                            className="w-full rounded-lg border border-(--color-border) bg-(--color-bg) px-4 py-2 text-sm"
                        />
                        <p className="mt-1 text-xs text-(--color-text-muted)">
                            {isFile
                                ? 'PDF, DOC/DOCX, XLS/XLSX, PPT/PPTX, TXT, CSV, ZIP. Maks. 10 MB.'
                                : 'Otomatis dikonversi ke WebP, maks. 5 MB.'}
                        </p>
                    </div>
                    {!isFile && (
                        <select
                            value={uploadFolder}
                            onChange={(event) => setUploadFolder(event.target.value)}
                            aria-label="Folder upload"
                            className="rounded-lg border border-(--color-border) bg-(--color-bg) px-3 py-2 text-sm"
                        >
                            {UPLOAD_FOLDERS.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                    )}
                    <select
                        value={uploadCategory}
                        onChange={(event) => setUploadCategory(event.target.value)}
                        aria-label="Kategori upload"
                        className="rounded-lg border border-(--color-border) bg-(--color-bg) px-3 py-2 text-sm"
                    >
                        <option value="">Tanpa kategori</option>
                        {categories.map((category) => (
                            <option key={category.id} value={category.id}>
                                {category.name}
                            </option>
                        ))}
                    </select>
                    <button
                        type="button"
                        onClick={() => fileInputRef.current?.click()}
                        disabled={uploading}
                        className="rounded-lg bg-(--color-accent) px-4 py-2 text-sm font-medium text-(--color-on-accent) hover:opacity-90 disabled:opacity-60"
                    >
                        {uploading ? 'Mengunggah...' : 'Pilih & Unggah'}
                    </button>
                    <input
                        ref={fileInputRef}
                        type="file"
                        accept={isFile ? FILE_ACCEPT : 'image/*'}
                        className="hidden"
                        onChange={handleUpload}
                    />
                </div>
            </div>

            {isFetching && !isLoading && (
                <p className="text-xs text-(--color-text-muted)">Memperbarui daftar...</p>
            )}

            {isLoading && <p className="text-sm text-(--color-text-muted)">Memuat...</p>}

            {!isLoading && data?.data?.length === 0 && (
                <div className="space-y-3 rounded-xl border border-(--color-border) bg-(--color-surface) p-10 text-center">
                    <p className="text-sm text-(--color-text-muted)">
                        {hasFilters ? 'Tidak ada media yang cocok dengan filter.' : 'Belum ada media.'}
                    </p>
                    {hasFilters && (
                        <button
                            type="button"
                            onClick={resetFilters}
                            className="rounded-lg border border-(--color-border) px-3 py-1.5 text-xs hover:bg-(--color-accent-bg)"
                        >
                            Tampilkan semua media
                        </button>
                    )}
                </div>
            )}

            {data?.data?.length > 0 && (
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
                        onClick={askBulkDelete}
                        className="font-medium text-(--color-error) hover:underline"
                    >
                        Pindahkan ke sampah
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

            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                {data?.data?.map((item) => {
                    const folderKey = FOLDER_LABELS[item.folder] ? item.folder : 'other';
                    const url = resolveMediaUrl(item);
                    const thumb = resolveThumbUrl(item);
                    const label = mediaLabel(item);
                    const isSelected = selected.includes(item.id);
                    const meta = [
                        item.width && item.height ? `${item.width}×${item.height}` : null,
                        formatSize(item.size),
                        formatDate(item.created_at),
                    ].filter(Boolean).join(' · ');

                    return (
                        <figure
                            key={item.id}
                            className={`group overflow-hidden rounded-xl border bg-(--color-surface) ${
                                isSelected ? 'border-(--color-accent) ring-2 ring-(--color-accent)' : 'border-(--color-border)'
                            }`}
                        >
                            <div className="relative flex aspect-square items-center justify-center overflow-hidden bg-(--color-bg)">
                                {item.type === 'file' ? (
                                    <div className="flex flex-col items-center gap-2 px-4 text-center">
                                        <span className="rounded-lg border border-(--color-border) bg-(--color-surface) px-3 py-2 text-lg font-bold tracking-wide text-(--color-text-muted)">
                                            {fileExtension(item)}
                                        </span>
                                        <span className="line-clamp-2 text-[11px] text-(--color-text-muted)" title={item.original_name || item.path}>
                                            {item.original_name || item.path}
                                        </span>
                                    </div>
                                ) : (
                                    <img
                                        src={thumb}
                                        alt={item.alt_text || ''}
                                        loading="lazy"
                                        className="max-h-full max-w-full object-contain"
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

                                <span
                                    className={`absolute right-2 top-2 rounded-full px-2 py-0.5 text-[10px] font-medium ${FOLDER_BADGE_CLASSES[folderKey]}`}
                                >
                                    {FOLDER_LABELS[folderKey]}
                                </span>

                                {item.usage_count > 0 ? (
                                    <span
                                        className="absolute bottom-2 left-2 rounded-full bg-red-500/90 px-2 py-0.5 text-[10px] font-medium text-white"
                                        title={(item.usage_labels ?? []).join('; ')}
                                    >
                                        Dipakai {item.usage_count}×
                                    </span>
                                ) : (
                                    <span className="absolute bottom-2 left-2 rounded-full bg-emerald-500/90 px-2 py-0.5 text-[10px] font-medium text-white">
                                        Belum dipakai
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
                                    {item.category ? (
                                        <span className="rounded-full bg-(--color-accent-bg) px-2 py-0.5 text-[10px] font-medium text-(--color-accent)">
                                            {item.category.name}
                                        </span>
                                    ) : (
                                        <span className="text-[10px] text-(--color-text-muted)">Tanpa kategori</span>
                                    )}
                                </div>

                                <p className="text-[11px] text-(--color-text-muted)">{meta}</p>

                                <div className="flex flex-wrap gap-x-3 gap-y-1 text-xs">
                                    <button
                                        type="button"
                                        onClick={() => setEditingMedia(item)}
                                        className="text-(--color-accent) hover:underline"
                                    >
                                        Edit
                                    </button>
                                    <a
                                        href={url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="text-(--color-accent) hover:underline"
                                    >
                                        Buka
                                    </a>
                                    <button
                                        type="button"
                                        onClick={() => copyUrl(item)}
                                        className="text-(--color-accent) hover:underline"
                                    >
                                        Salin URL
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => deleteMedia(item)}
                                        className="text-(--color-error) hover:underline"
                                    >
                                        Sampah
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
                        Menampilkan {data.from}–{data.to} dari {data.total} media
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

            {managingCategories && <MediaCategoryManager onClose={() => setManagingCategories(false)} />}

            {editingMedia && (
                <MediaEditModal
                    media={editingMedia}
                    categories={categories}
                    onClose={() => setEditingMedia(null)}
                    onSaved={invalidate}
                />
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
