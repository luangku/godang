/** Read the site branding rendered by Blade before the admin SPA mounts. */
export function initialSiteData() {
    const metaContent = (name) => document.querySelector(`meta[name="${name}"]`)?.getAttribute('content') || null;

    return {
        site_title: metaContent('site-title'),
        site_logo: metaContent('site-logo'),
        site_logo_dark: metaContent('site-logo-dark'),
        site_logo_alt: metaContent('site-logo-alt'),
        author_name: metaContent('author-name'),
    };
}

/** Cloudflare Turnstile site key rendered by Blade; empty when not configured. */
export function readTurnstileSiteKey() {
    return document.querySelector('meta[name="turnstile-site-key"]')?.getAttribute('content') || '';
}

/** Resolve a stored media path into a browser URL. */
export function resolveSiteLogo(value) {
    if (!value) return '';
    return value.startsWith('http') ? value : `/storage/${value.replace(/^\/?(storage\/)?/, '')}`;
}
