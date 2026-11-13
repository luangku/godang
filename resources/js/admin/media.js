/** Resolve a media record, path, or URL into an absolute URL. */
export function resolveMediaUrl(value) {
    return resolveUrl(typeof value === 'object' && value !== null ? (value.url || value.path) : value);
}

/** Resolve a media record to its thumbnail URL, falling back to the original. */
export function resolveThumbUrl(value) {
    if (typeof value === 'object' && value !== null) {
        return resolveUrl(value.thumb_url || value.url || value.path);
    }

    return resolveUrl(value);
}

function resolveUrl(raw) {
    if (!raw) return '';
    if (/^https?:\/\//i.test(raw)) return raw;
    const path = String(raw).replace(/^\/+/, '');
    if (path.toLowerCase().startsWith('storage/')) return `${window.location.origin}/${path}`;
    return `${window.location.origin}/storage/${path}`;
}

/** Convert an absolute /storage/ URL back into a path relative to the public disk. */
export function toStoredPath(value) {
    if (!value || !/^https?:\/\//i.test(value)) return value;

    try {
        const url = new URL(value);

        if (url.pathname.startsWith('/storage/')) {
            return decodeURIComponent(url.pathname.slice('/storage/'.length));
        }
    } catch {
        // Keep values that cannot be parsed as URLs.
    }

    return value;
}

/** Convert /storage URLs into host-independent, root-relative URLs. */
export function toRootRelativeStorageUrl(value) {
    if (!value) return value;

    const raw = String(value).trim();
    if (!raw) return value;

    if (/^\/?storage\//i.test(raw)) {
        return `/${raw.replace(/^\/+/, '')}`;
    }

    if (!/^https?:\/\//i.test(raw)) return value;

    try {
        const url = new URL(raw);

        if (url.pathname.startsWith('/storage/')) {
            return `${url.pathname}${url.search}${url.hash}`;
        }
    } catch {
        // Keep values that cannot be parsed as URLs.
    }

    return value;
}

/** Rewrite legacy absolute image URLs in article HTML to root-relative /storage/ URLs. */
export function normalizeContentMediaUrls(html) {
    if (!html) return html;

    return String(html).replace(/(<img\b[^>]*?\bsrc\s*=\s*)(["'])([^"']*)\2/gi, (match, prefix, quote, src) => {
        const normalized = toRootRelativeStorageUrl(src);

        return normalized === src ? match : `${prefix}${quote}${normalized}${quote}`;
    });
}
