import { useRef, useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { api, endpoints, errorMessage } from '../api';
import { Toast, useToast } from './Toast';
import { resolveThumbUrl } from '../media';

const FOLDER_OPTIONS = [
    { value: '', label: 'Semua folder' },
    { value: 'uploads', label: 'Gambar artikel' },
    { value: 'cover', label: 'Cover & OG' },
    { value: 'avatar', label: 'Avatar & Logo' },
    { value: 'other', label: 'Folder lain' },
];

function countFor(folders, value) {
    return (value === '' ? folders?.all : folders?.[value]) ?? 0;
}

export default function MediaPicker({ onSelect, onClose, folder = 'uploads', defaultFolder = '' }) {
    const queryClient = useQueryClient();
    const { toast, show } = useToast();
    const fileInputRef = useRef(null);
    const [search, setSearch] = useState('');
    const [query, setQuery] = useState('');
    const [filterFolder, setFilterFolder] = useState(defaultFolder);
    const [filterCategory, setFilterCategory] = useState('');
    const [page, setPage] = useState(1);
    const [uploading, setUploading] = useState(false);

    const uploadFolder = folder === 'all' ? 'uploads' : folder;

    const { data, isLoading } = useQuery({
        queryKey: ['media-picker', query, filterFolder, filterCategory, page],
        queryFn: async () => {
            const params = { per_page: 12, page, type: 'image' };
            if (query) params.q = query;
            if (filterFolder) params.folder = filterFolder;
            if (filterCategory) params.category = filterCategory;

            return (await api.get(endpoints.media, { params })).data;
        },
    });

    const media = data?.data ?? [];
    const folders = data?.folders ?? {};
    const categories = data?.categories ?? [];

    const handleUpload = async (event) => {
        const file = event.target.files?.[0];

        if (!file) return;

        setUploading(true);
        try {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', 'image');
            if (uploadFolder) formData.append('folder', uploadFolder);

            const { data: response } = await api.post(endpoints.media, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            queryClient.invalidateQueries({ queryKey: ['media'] });
            queryClient.invalidateQueries({ queryKey: ['media-picker'] });

            const path = response.media?.path || response.path;
            if (path) {
                onSelect(path);
                show('Gambar berhasil diunggah.');
                onClose();
                return;
            }
            show('Gambar berhasil diunggah.');
        } catch (error) {
            show(errorMessage(error), 'error');
        } finally {
            setUploading(false);
            event.target.value = '';
        }
    };

    return (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
            <div className="bg-(--color-surface) rounded-xl border border-(--color-border) w-full max-w-3xl max-h-[85vh] overflow-hidden flex flex-col">
                <div className="flex items-center justify-between px-4 py-3 border-b border-(--color-border)">
                    <h3 className="font-semibold text-sm">Pilih Media</h3>
                    <button type="button" onClick={onClose} className="text-(--color-text-muted) hover:text-(--color-text)" aria-label="Tutup">
                        ✕
                    </button>
                </div>

                <div className="flex flex-col gap-2 p-4 border-b border-(--color-border)">
                    <div className="flex flex-col sm:flex-row gap-2">
                        <form
                            className="flex flex-1 gap-2"
                            onSubmit={(event) => {
                                event.preventDefault();
                                setPage(1);
                                setQuery(search.trim());
                            }}
                        >
                            <input
                                type="search"
                                value={search}
                                onChange={(event) => {
                                    const value = event.target.value;
                                    setSearch(value);

                                    if (value === '' && query) {
                                        setPage(1);
                                        setQuery('');
                                    }
                                }}
                                placeholder="Cari media..."
                                aria-label="Cari media"
                                className="flex-1 px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm"
                            />
                            <button type="submit" className="px-3 py-2 rounded-lg border border-(--color-border) text-sm hover:bg-(--color-accent-bg)">
                                Cari
                            </button>
                        </form>
                        <select
                            value={filterFolder}
                            onChange={(event) => {
                                setPage(1);
                                setFilterFolder(event.target.value);
                            }}
                            aria-label="Filter kategori media"
                            className="px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm"
                        >
                            {FOLDER_OPTIONS.map((option) => (
                                <option key={option.value || 'all'} value={option.value}>
                                    {option.label} ({countFor(folders, option.value)})
                                </option>
                            ))}
                        </select>
                        <select
                            value={filterCategory}
                            onChange={(event) => {
                                setPage(1);
                                setFilterCategory(event.target.value);
                            }}
                            aria-label="Filter kategori custom media"
                            className="px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm"
                        >
                            <option value="">Semua kategori custom</option>
                            {categories.map((category) => (
                                <option key={category.id} value={category.id}>
                                    {category.name} ({category.count})
                                </option>
                            ))}
                            <option value="none">Tanpa kategori ({data?.uncategorized_count ?? 0})</option>
                        </select>
                        <button
                            type="button"
                            onClick={() => fileInputRef.current?.click()}
                            disabled={uploading}
                            className="px-4 py-2 rounded-lg bg-(--color-accent) text-(--color-on-accent) text-sm font-medium hover:opacity-90 disabled:opacity-60"
                        >
                            {uploading ? 'Mengunggah...' : 'Upload Gambar'}
                        </button>
                        <input ref={fileInputRef} type="file" accept="image/*" className="hidden" onChange={handleUpload} />
                    </div>
                </div>

                <div className="p-4 overflow-auto grid grid-cols-2 md:grid-cols-4 gap-3">
                    {isLoading && (
                        <p className="col-span-full text-sm text-(--color-text-muted)">Memuat...</p>
                    )}
                    {!isLoading && media.length === 0 && (
                        <p className="col-span-full text-sm text-(--color-text-muted)">
                            {query || filterFolder || filterCategory ? 'Tidak ada media yang cocok dengan filter.' : 'Belum ada media.'}
                        </p>
                    )}
                    {media.map((item) => (
                        <button
                            type="button"
                            key={item.id}
                            onClick={() => {
                                onSelect(item.path || item.url);
                                onClose();
                            }}
                            className="border border-(--color-border) rounded-lg overflow-hidden hover:border-(--color-accent)"
                            title={item.alt_text || item.path}
                        >
                            <img
                                src={resolveThumbUrl(item)}
                                alt={item.alt_text || ''}
                                loading="lazy"
                                className="w-full h-28 object-cover"
                            />
                            <span className="block px-2 py-1.5 text-[11px] text-(--color-text-muted) truncate text-left">
                                {item.alt_text || item.path}
                            </span>
                        </button>
                    ))}
                </div>

                {data && data.last_page > 1 && (
                    <div className="flex items-center justify-between px-4 py-3 border-t border-(--color-border) text-xs">
                        <span className="text-(--color-text-muted)">
                            Halaman {data.current_page} / {data.last_page} · {data.total} media
                        </span>
                        <div className="flex gap-2">
                            <button
                                type="button"
                                disabled={!data.prev_page_url}
                                onClick={() => setPage((current) => Math.max(1, current - 1))}
                                className="px-3 py-1.5 rounded-lg border border-(--color-border) disabled:opacity-40 hover:bg-(--color-accent-bg)"
                            >
                                Sebelumnya
                            </button>
                            <button
                                type="button"
                                disabled={!data.next_page_url}
                                onClick={() => setPage((current) => current + 1)}
                                className="px-3 py-1.5 rounded-lg border border-(--color-border) disabled:opacity-40 hover:bg-(--color-accent-bg)"
                            >
                                Berikutnya
                            </button>
                        </div>
                    </div>
                )}
            </div>

            <Toast toast={toast} />
        </div>
    );
}
