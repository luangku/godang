import { useEffect, useRef, useState } from 'react';

let idCounter = 0;

export function useToast() {
    const [toast, setToast] = useState(null);

    const show = (message, type = 'success') => {
        setToast({ id: ++idCounter, message, type });
    };

    return { toast, show };
}

function hasOpenDialog() {
    return typeof document !== 'undefined' && Boolean(document.querySelector('dialog[open]'));
}

function supportsPopover() {
    return (
        typeof HTMLElement !== 'undefined' &&
        Object.hasOwn(HTMLElement.prototype, 'popover')
    );
}

function supportsAriaNotify() {
    return typeof navigator !== 'undefined' && typeof navigator.ariaNotify === 'function';
}

export function Toast({ toast }) {
    const [dismissedId, setDismissedId] = useState(null);
    const [paused, setPaused] = useState(false);
    const [dialogOpen, setDialogOpen] = useState(hasOpenDialog);
    const popoverRef = useRef(null);

    const visible = Boolean(toast) && toast.id !== dismissedId && !dialogOpen;

    useEffect(() => {
        if (toast) {
            setDismissedId(null);
            setPaused(false);
        }
    }, [toast?.id]);

    useEffect(() => {
        const update = () => setDialogOpen(hasOpenDialog());

        document.addEventListener('close', update, true);
        document.addEventListener('toggle', update, true);

        const observer = typeof MutationObserver !== 'undefined'
            ? new MutationObserver(update)
            : null;
        observer?.observe(document.body, {
            attributes: true,
            subtree: true,
            attributeFilter: ['open'],
        });

        return () => {
            document.removeEventListener('close', update, true);
            document.removeEventListener('toggle', update, true);
            observer?.disconnect();
        };
    }, []);

    useEffect(() => {
        const node = popoverRef.current;
        if (!visible || !node) {
            return;
        }

        if (supportsPopover() && typeof node.showPopover === 'function' && !node.matches(':popover-open')) {
            node.showPopover();
        }

        return () => {
            if (supportsPopover() && typeof node.hidePopover === 'function' && node.matches(':popover-open')) {
                node.hidePopover();
            }
        };
    }, [visible, toast?.id]);

    useEffect(() => {
        if (!visible || paused) {
            return;
        }

        const timer = window.setTimeout(() => setDismissedId(toast.id), 3500);

        return () => window.clearTimeout(timer);
    }, [visible, paused, toast?.id]);

    useEffect(() => {
        if (!visible || !supportsAriaNotify()) {
            return;
        }

        navigator.ariaNotify(toast.message, {
            priority: toast.type === 'error' ? 'important' : 'normal',
        });
    }, [visible, toast?.id]);

    const dismiss = () => {
        if (toast) {
            setDismissedId(toast.id);
        }
    };

    return (
        <>
            <div role="region" aria-label="Notifikasi" className="toast-container">
                {visible && (
                    <div
                        ref={popoverRef}
                        popover="manual"
                        className={`toast ${toast.type === 'error' ? 'toast-error' : 'toast-success'}`}
                        onMouseEnter={() => setPaused(true)}
                        onMouseLeave={() => setPaused(false)}
                        onFocusCapture={() => setPaused(true)}
                        onBlurCapture={(event) => {
                            if (!event.currentTarget.contains(event.relatedTarget)) {
                                setPaused(false);
                            }
                        }}
                    >
                        <span>{toast.message}</span>
                        <button
                            type="button"
                            onClick={dismiss}
                            className="toast-close"
                            aria-label="Tutup notifikasi"
                        >
                            ✕
                        </button>
                    </div>
                )}
            </div>

            <div aria-live="polite" aria-atomic="true" className="sr-only">
                {visible && !supportsAriaNotify() ? toast.message : ''}
            </div>
        </>
    );
}
