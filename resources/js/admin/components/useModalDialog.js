import { useEffect, useRef } from 'react';

/**
 * Controls a native modal dialog and keeps React state in sync with platform
 * dismissal (Esc, back gesture, and light dismiss where supported).
 */
export default function useModalDialog(open, onClose) {
    const dialogRef = useRef(null);
    const closingFromState = useRef(false);

    useEffect(() => {
        const dialog = dialogRef.current;
        if (!dialog) {
            return;
        }

        if (open && !dialog.open) {
            dialog.showModal();
        } else if (!open && dialog.open) {
            closingFromState.current = true;
            dialog.close();
        }
    }, [open]);

    useEffect(() => {
        const dialog = dialogRef.current;
        if (!dialog) {
            return;
        }

        const handleClose = () => {
            if (closingFromState.current) {
                closingFromState.current = false;
                return;
            }
            onClose?.();
        };

        dialog.addEventListener('close', handleClose);

        // closedby is not available in Safari: fall back to pointer coordinates,
        // checking the bounding box rather than event.target (padding is the dialog).
        const isOutside = (event) => {
            const rect = dialog.getBoundingClientRect();

            return (
                event.clientX < rect.left ||
                event.clientX > rect.right ||
                event.clientY < rect.top ||
                event.clientY > rect.bottom
            );
        };

        let pressedOutside = false;
        const handlePointerDown = (event) => {
            pressedOutside = isOutside(event);
        };
        const handlePointerUp = (event) => {
            if (pressedOutside && isOutside(event)) {
                dialog.close();
            }
            pressedOutside = false;
        };

        if (!('closedBy' in HTMLDialogElement.prototype)) {
            dialog.addEventListener('pointerdown', handlePointerDown);
            dialog.addEventListener('pointerup', handlePointerUp);
        }

        return () => {
            dialog.removeEventListener('close', handleClose);
            dialog.removeEventListener('pointerdown', handlePointerDown);
            dialog.removeEventListener('pointerup', handlePointerUp);
        };
    }, [onClose]);

    return dialogRef;
}
