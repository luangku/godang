import { useEffect, useState } from 'react';
import useModalDialog from './useModalDialog';

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
    const dialogRef = useModalDialog(open, onCancel);

    useEffect(() => {
        if (open) {
            setError(null);
        }
    }, [open]);

    const handleConfirm = async () => {
        setError(null);
        try {
            await onConfirm();
        } catch (err) {
            setError(err?.message || 'Terjadi kesalahan.');
        }
    };

    return (
        <dialog
            ref={dialogRef}
            closedby="any"
            aria-labelledby="confirm-modal-title"
            aria-describedby="confirm-modal-message"
            className="modal-dialog w-[calc(100%_-_2rem)] max-w-sm rounded-xl border border-(--color-border) bg-(--color-surface) p-6 shadow-xl"
        >
            <h3 id="confirm-modal-title" className="text-lg font-semibold mb-2">{title}</h3>
            <p id="confirm-modal-message" className="text-sm text-(--color-text-muted) mb-4">{message}</p>
            {error && (
                <p role="alert" className="text-sm text-(--color-error) mb-4">{error}</p>
            )}
            <div className="flex justify-end gap-3">
                <button
                    type="button"
                    autoFocus
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
        </dialog>
    );
}
