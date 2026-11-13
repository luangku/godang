import { useEffect, useRef } from 'react';

const SCRIPT_ID = 'cf-turnstile-script';
const SCRIPT_SRC = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';

function loadTurnstileScript() {
    if (window.turnstile) return Promise.resolve(window.turnstile);

    return new Promise((resolve, reject) => {
        let script = document.getElementById(SCRIPT_ID);

        if (!script) {
            script = document.createElement('script');
            script.id = SCRIPT_ID;
            script.src = SCRIPT_SRC;
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        }

        const startedAt = Date.now();
        const timer = window.setInterval(() => {
            if (window.turnstile) {
                window.clearInterval(timer);
                resolve(window.turnstile);
            } else if (Date.now() - startedAt > 15000) {
                window.clearInterval(timer);
                reject(new Error('Cloudflare Turnstile gagal dimuat.'));
            }
        }, 100);
    });
}

/**
 * Cloudflare Turnstile widget.
 *
 * Renders nothing when no site key is configured so login keeps working in
 * local development and the test suite, matching the server-side behaviour.
 */
export default function TurnstileWidget({ siteKey, theme = 'auto', resetKey = 0, onToken }) {
    const containerRef = useRef(null);
    const widgetRef = useRef(null);
    const onTokenRef = useRef(onToken);
    onTokenRef.current = onToken;

    useEffect(() => {
        if (!siteKey) return;

        let cancelled = false;

        loadTurnstileScript()
            .then((turnstile) => {
                if (cancelled || !containerRef.current) return;

                widgetRef.current = turnstile.render(containerRef.current, {
                    sitekey: siteKey,
                    theme,
                    callback: (token) => onTokenRef.current?.(token),
                    'expired-callback': () => onTokenRef.current?.(null),
                    'error-callback': () => onTokenRef.current?.(null),
                });
            })
            .catch(() => onTokenRef.current?.(null));

        return () => {
            cancelled = true;

            if (widgetRef.current !== null && window.turnstile) {
                window.turnstile.remove(widgetRef.current);
                widgetRef.current = null;
            }

            onTokenRef.current?.(null);
        };
    }, [siteKey, theme, resetKey]);

    if (!siteKey) return null;

    return <div ref={containerRef} className="flex justify-center" />;
}
