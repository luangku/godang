import { useState } from 'react';

let idCounter = 0;

export function useToast() {
    const [toast, setToast] = useState(null);

    const show = (message, type = 'success') => {
        const id = ++idCounter;
        setToast({ id, message, type });
        setTimeout(() => {
            setToast((current) => (current?.id === id ? null : current));
        }, 3500);
    };

    return { toast, show };
}

export function Toast({ toast }) {
    if (!toast) return null;

    return (
        <div className="fixed bottom-4 right-4 z-50">
            <div
                role="status"
                className={`toast ${toast.type === 'error' ? 'toast-error' : 'toast-success'}`}
            >
                {toast.message}
            </div>
        </div>
    );
}
