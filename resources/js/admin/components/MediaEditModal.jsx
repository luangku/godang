import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api, endpoints, errorMessage } from '../api';
import { resolveMediaUrl } from '../media';
import { Toast, useToast } from './Toast';
import useModalDialog from './useModalDialog';

export default function MediaEditModal({ media, categories, onClose, onSaved }) {
    const queryClient = useQueryClient();
    const { toast, show } = useToast();
    const [altText, setAltText] = useState(media.alt_text || '');
    const [categoryId, setCategoryId] = useState(media.category?.id ? String(media.category.id) : '');
    const dialogRef = useModalDialog(true, onClose);

    const mutation = useMutation({
        mutationFn: async (payload) => (await api.patch(`${endpoints.media}/${media.id}`, payload)).data,
        onSuccess: (response) => {
            queryClient.invalidateQueries({ queryKey: ['media'] });
            queryClient.invalidateQueries({ queryKey: ['media-categories'] });
            show(response.message || 'Media diperbarui.');
            onSaved?.();
            onClose();
        },
        onError: (error) => show(errorMessage(error), 'error'),
    });

    const submit = (event) => {
        event.preventDefault();
        mutation.mutate({
            alt_text: altText.trim(),
            category_id: categoryId === '' ? null : Number(categoryId),
        });
    };

    return (
        <>
            <dialog
                ref={dialogRef}
                closedby="any"
                aria-labelledby="media-edit-title"
                className="modal-dialog w-[calc(100%_-_2rem)] max-w-lg rounded-xl border border-(--color-border) bg-(--color-surface) p-0 shadow-xl"
            >
                <form onSubmit={submit}>
                    <div className="flex items-center justify-between px-4 py-3 border-b border-(--color-border)">
                        <h3 id="media-edit-title" className="font-semibold text-sm">Edit media</h3>
                        <button
                            type="button"
                            onClick={onClose}
                            className="text-(--color-text-muted) hover:text-(--color-text)"
                            aria-label="Tutup"
                        >
                            ✕
                        </button>
                    </div>

                    <div className="p-4 space-y-4">
                        <div className="flex gap-3">
                            <img
                                src={resolveMediaUrl(media)}
                                alt={media.alt_text || ''}
                                className="w-24 h-24 rounded-lg object-contain border border-(--color-border) bg-(--color-bg)"
                            />
                            <div className="min-w-0 text-xs text-(--color-text-muted) space-y-1">
                                <p className="truncate" title={media.path}>{media.path}</p>
                                <p>{media.width && media.height ? `${media.width}×${media.height}` : '—'}</p>
                                {media.usage_count > 0 ? (
                                    <p className="text-(--color-error)">
                                        Dipakai {media.usage_count}×: {media.usage_labels.join('; ')}
                                    </p>
                                ) : (
                                    <p>Belum dipakai.</p>
                                )}
                            </div>
                        </div>

                        <div>
                            <label htmlFor="media-alt-text" className="block text-sm font-medium mb-1">
                                Alt text
                            </label>
                            <input
                                id="media-alt-text"
                                type="text"
                                value={altText}
                                autoFocus
                                onChange={(event) => setAltText(event.target.value)}
                                maxLength={255}
                                className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm"
                                placeholder="Deskripsi singkat gambar"
                            />
                            <p className="mt-1 text-xs text-(--color-text-muted)">
                                Alt text membantu aksesibilitas dan pencarian media.
                            </p>
                        </div>

                        <div>
                            <label htmlFor="media-category" className="block text-sm font-medium mb-1">
                                Kategori
                            </label>
                            <select
                                id="media-category"
                                value={categoryId}
                                onChange={(event) => setCategoryId(event.target.value)}
                                className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm"
                            >
                                <option value="">Tanpa kategori</option>
                                {categories.map((category) => (
                                    <option key={category.id} value={category.id}>
                                        {category.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <div className="flex justify-end gap-2 px-4 py-3 border-t border-(--color-border)">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-4 py-2 rounded-lg border border-(--color-border) text-sm hover:bg-(--color-accent-bg)"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            disabled={mutation.isPending}
                            className="px-4 py-2 rounded-lg bg-(--color-accent) text-(--color-on-accent) text-sm font-medium hover:opacity-90 disabled:opacity-60"
                        >
                            {mutation.isPending ? 'Menyimpan...' : 'Simpan'}
                        </button>
                    </div>
                </form>
            </dialog>

            <Toast toast={toast} />
        </>
    );
}
