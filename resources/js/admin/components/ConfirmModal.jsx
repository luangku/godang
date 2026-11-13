import { useState } from 'react';

export default function ConfirmModal({
    open,
    title = 'Konfirmasi',
    message,
    confirmLabel = 'Hapus',
    cancelLabel = 'Batal',
    onConfirm,
    onCancel,
    busy = false,
}) {
    const [error, setError] = useState(null);

    if (!open) return null;

    const handleConfirm = async () => {
        setError(null);
        try {
            await onConfirm();
        } catch (err) {
            setError(err?.message || 'Terjadi kesalahan.');
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center px-4" role="dialog" aria-modal="true">
            <div className="absolute inset-0 bg-black/50" onClick={busy ? undefined : onCancel} aria-hidden="true" />
            <div className="relative w-full max-w-sm bg-(--color-surface) border border-(--color-border) rounded-xl p-6 shadow-xl">
                <h3 className="text-lg font-semibold mb-2">{title}</h3>
                <p className="text-sm text-(--color-text-muted) mb-4">{message}</p>
                {error && (
                    <p className="text-sm text-(--color-error) mb-4">{error}</p>
                )}
                <div className="flex justify-end gap-3">
                    <button
                        type="button"
                        onClick={onCancel}
                        disabled={busy}
                        className="px-4 py-2 rounded-lg text-sm border border-(--color-border) hover:bg-(--color-accent-bg) transition-colors disabled:opacity-60"
                    >
                        {cancelLabel}
                    </button>
                    <button
                        type="button"
                        onClick={handleConfirm}
                        disabled={busy}
                        className="px-4 py-2 rounded-lg text-sm bg-(--color-error) text-white font-medium hover:opacity-90 transition-opacity disabled:opacity-60"
                    >
                        {busy ? 'Memproses...' : confirmLabel}
                    </button>
                </div>
            </div>
        </div>
    );
}
