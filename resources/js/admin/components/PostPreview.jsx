import { useEffect, useMemo, useRef } from 'react';
import StatusBadge from './StatusBadge';

export function localizedName(name) {
    return typeof name === 'string' ? name : '';
}

function formatDate(date) {
    if (!date) return '';

    try {
        return new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
    } catch {
        return date;
    }
}

/**
 * Parse the article HTML, giving every heading an id so the table of
 * contents can link to it, and collect the headings for the sidebar.
 */
function useArticleHeadings(content) {
    return useMemo(() => {
        if (!content) {
            return { html: '', headings: [] };
        }

        const doc = new DOMParser().parseFromString(content, 'text/html');
        const headings = [];

        doc.body.querySelectorAll('h2, h3').forEach((heading, index) => {
            const id = heading.id || `heading-${index}`;
            const text = heading.textContent.replace('#', '').trim();

            heading.id = id;
            heading.style.scrollMarginTop = '6rem';

            const anchor = doc.createElement('a');
            anchor.href = `#${id}`;
            anchor.className = 'heading-anchor';
            anchor.textContent = '#';
            anchor.setAttribute('aria-label', 'Link to this section');
            heading.appendChild(anchor);

            if (text) {
                headings.push({ id, text, level: heading.tagName });
            }
        });

        return { html: doc.body.innerHTML, headings };
    }, [content]);
}

/**
 * Enhance rendered code blocks the same way the public article page does:
 * syntax highlighting plus a copy button.
 */
function useCodeBlockEnhancement(html) {
    const contentRef = useRef(null);

    useEffect(() => {
        const content = contentRef.current;
        if (!content || !html) {
            return;
        }

        let cancelled = false;

        import('highlight.js/lib/common').then(async ({ default: hljs }) => {
            const { default: apache } = await import('highlight.js/lib/languages/apache');
            hljs.registerLanguage('apache', apache);

            if (cancelled) {
                return;
            }

            content.querySelectorAll('pre code').forEach((block) => {
                hljs.highlightElement(block);

                const pre = block.parentElement;
                if (!pre || pre.dataset.enhanced) {
                    return;
                }
                pre.dataset.enhanced = '1';

                const wrapper = document.createElement('div');
                wrapper.className = 'code-block-wrapper';
                pre.parentNode.insertBefore(wrapper, pre);
                wrapper.appendChild(pre);

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'copy-button';
                button.textContent = 'Salin';
                button.addEventListener('click', async () => {
                    try {
                        await navigator.clipboard.writeText(block.innerText);
                        button.textContent = 'Tersalin!';
                    } catch {
                        button.textContent = 'Gagal';
                    }
                    setTimeout(() => {
                        button.textContent = 'Salin';
                    }, 2000);
                });
                wrapper.appendChild(button);
            });
        });

        return () => {
            cancelled = true;
        };
    }, [html]);

    return contentRef;
}

export default function PostPreview({
    title,
    categoryName = '',
    status = 'draft',
    publishedAt = '',
    readingTime = 0,
    viewsCount,
    tags = [],
    coverUrl = '',
    excerpt = '',
    content = '',
    note = '',
    isTrashed = false,
    children,
}) {
    const { html, headings } = useArticleHeadings(content);
    const contentRef = useCodeBlockEnhancement(html);

    return (
        <div className="flex flex-col xl:flex-row gap-12 max-w-[var(--width-wide)]">
            <div className="flex-1 min-w-0">
                <article>
                    {note && (
                        <div className="mb-6 px-4 py-3 rounded-lg bg-(--color-warning)/10 border border-(--color-warning)/40 text-sm text-(--color-text)">
                            {note}
                        </div>
                    )}

                    <header className="mb-10">
                        {categoryName && (
                            <span className="inline-block text-xs font-semibold uppercase tracking-wider text-(--color-accent) mb-4">
                                {categoryName}
                            </span>
                        )}

                        <h1 className="text-3xl md:text-4xl lg:text-5xl font-bold leading-tight mb-4">
                            {title || 'Tanpa judul'}
                        </h1>

                        <div className="flex flex-wrap items-center gap-4 text-sm text-(--color-text-muted) mb-6">
                            <StatusBadge status={isTrashed ? 'trashed' : status} />
                            {publishedAt && <time dateTime={publishedAt}>{formatDate(publishedAt)}</time>}
                            {Boolean(readingTime) && (
                                <span className="flex items-center gap-1">
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                    {readingTime} menit baca
                                </span>
                            )}
                            {viewsCount !== undefined && <span>{viewsCount} dilihat</span>}
                        </div>

                        {tags.length > 0 && (
                            <div className="flex flex-wrap gap-2 mb-6">
                                {tags.map((tag) => (
                                    <span
                                        key={tag.id}
                                        className="text-xs px-2.5 py-1 rounded-md bg-(--color-accent-bg) text-(--color-accent) font-medium"
                                    >
                                        {tag.name}
                                    </span>
                                ))}
                            </div>
                        )}

                        {coverUrl && (
                            <figure className="mb-8">
                                <img src={coverUrl} alt={title || ''} className="w-full rounded-xl" />
                            </figure>
                        )}
                    </header>

                    {excerpt && <p className="text-lg text-(--color-text-muted) leading-relaxed mb-6">{excerpt}</p>}

                    {html ? (
                        <div ref={contentRef} className="prose-article" dangerouslySetInnerHTML={{ __html: html }} />
                    ) : (
                        <p className="text-sm text-(--color-text-muted)">Artikel belum memiliki isi.</p>
                    )}
                </article>

                {children}
            </div>

            {headings.length > 0 && (
                <aside className="hidden xl:block w-64 flex-shrink-0">
                    <div className="sticky top-24">
                        <h4 className="text-sm font-semibold uppercase tracking-wider text-(--color-text-muted) mb-4">
                            Daftar Isi
                        </h4>
                        <nav className="space-y-1">
                            {headings.map((heading) => (
                                <a
                                    key={heading.id}
                                    href={`#${heading.id}`}
                                    className={`block text-sm py-1 text-(--color-text-muted) hover:text-(--color-accent) transition-colors duration-200 border-l-2 border-transparent hover:border-(--color-accent) ${
                                        heading.level === 'H3' ? 'pl-6' : 'pl-3'
                                    }`}
                                >
                                    {heading.text}
                                </a>
                            ))}
                        </nav>
                    </div>
                </aside>
            )}
        </div>
    );
}
