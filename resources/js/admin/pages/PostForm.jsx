import { useEffect, useRef, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { api, endpoints, errorMessage } from '../api';
import ConfirmModal from '../components/ConfirmModal';
import MediaPicker from '../components/MediaPicker';
import { Toast, useToast } from '../components/Toast';
import { normalizeContentMediaUrls, resolveMediaUrl, toStoredPath } from '../media';

const schema = z.object({
    title: z.string().min(1, 'Judul wajib diisi.').max(255),
    slug: z.string().max(255).optional().or(z.literal('')),
    status: z.enum(['draft', 'published', 'scheduled']),
    excerpt: z.string().max(500).optional().or(z.literal('')),
    category_id: z.string().optional().or(z.literal('')),
    published_at: z.string().optional().or(z.literal('')),
    meta_title: z.string().max(255).optional().or(z.literal('')),
    meta_description: z.string().max(500).optional().or(z.literal('')),
    og_image: z.string().optional().or(z.literal('')),
    cover_image: z.string().optional().or(z.literal('')),
});

function slugify(text) {
    return text
        .toString()
        .toLowerCase()
        .trim()
        .replace(/[\s_]+/g, '-')
        .replace(/[^a-z0-9\-]+/g, '')
        .replace(/\-+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function toFormValue(value) {
    if (value === null || value === undefined) return '';
    return value;
}

function normalizeForm(formData) {
    const next = { ...formData };
    Object.keys(next).forEach((key) => {
        if (next[key] === '') next[key] = null;
    });
    next.cover_image = toStoredPath(next.cover_image);
    next.og_image = toStoredPath(next.og_image);
    next.content = normalizeContentMediaUrls(next.content);
    if (next.category_id) next.category_id = Number(next.category_id);
    return next;
}

const BLOCK_TAGS = new Set([
    'address', 'article', 'aside', 'blockquote', 'div', 'dl', 'dt', 'dd', 'fieldset',
    'figcaption', 'figure', 'footer', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
    'header', 'hr', 'li', 'main', 'nav', 'ol', 'p', 'pre', 'section', 'table',
    'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'ul',
]);

function openingTag(element) {
    const attributes = Array.from(element.attributes)
        .map((attribute) => ` ${attribute.name}="${attribute.value.replace(/"/g, '&quot;')}"`)
        .join('');

    return `<${element.tagName.toLowerCase()}${attributes}>`;
}

/**
 * Put each block element on its own line so the raw HTML tab is readable.
 * Inline formatting stays intact, and list items / table cells remain on one line.
 */
function formatHtmlForEditing(html) {
    if (!html) return '';

    const body = new DOMParser().parseFromString(`<body>${html}</body>`, 'text/html').body;
    const lines = [];

    const appendNode = (node, depth) => {
        const indentation = '  '.repeat(depth);

        if (node.nodeType === Node.TEXT_NODE) {
            const text = node.textContent.trim();
            if (text) lines.push(`${indentation}${text}`);
            return;
        }

        if (node.nodeType !== Node.ELEMENT_NODE) return;

        const tag = node.tagName.toLowerCase();
        const children = Array.from(node.childNodes);
        const hasBlockChildren = children.some(
            (child) => child.nodeType === Node.ELEMENT_NODE && BLOCK_TAGS.has(child.tagName.toLowerCase()),
        );

        if (!hasBlockChildren || ['li', 'td', 'th'].includes(tag)) {
            lines.push(`${indentation}${node.outerHTML}`);
            return;
        }

        lines.push(`${indentation}${openingTag(node)}`);
        children.forEach((child) => appendNode(child, depth + 1));
        lines.push(`${indentation}</${tag}>`);
    };

    Array.from(body.childNodes).forEach((child) => appendNode(child, 0));

    return lines.join('\n');
}

export default function PostForm() {
    const { id } = useParams();
    const isEdit = Boolean(id);
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const { toast, show } = useToast();

    const [content, setContent] = useState('');
    const [dirty, setDirty] = useState(false);
    const [slugAuto, setSlugAuto] = useState(true);
    const [pendingSlugSubmit, setPendingSlugSubmit] = useState(null);
    const [tags, setTags] = useState([]);
    const [selectedTags, setSelectedTags] = useState([]);
    const [tagInput, setTagInput] = useState('');
    const [tagChips, setTagChips] = useState([]);
    const [showMediaPicker, setShowMediaPicker] = useState(null);
    const coverInputRef = useRef(null);
    const ogInputRef = useRef(null);
    const hydratedTitleRef = useRef(null);
    const rawHtmlRef = useRef('');

    const { data: categories = [] } = useQuery({
        queryKey: ['categories'],
        queryFn: async () => (await api.get(endpoints.categories)).data,
        initialData: [],
    });

    const { data: allTags = [] } = useQuery({
        queryKey: ['tags'],
        queryFn: async () => (await api.get(endpoints.tags)).data,
        initialData: [],
    });

    const { register, handleSubmit, setValue, getValues, watch, reset, formState: { errors, isSubmitting, isDirty } } = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            title: '',
            slug: '',
            status: 'draft',
            excerpt: '',
            category_id: '',
            published_at: '',
            meta_title: '',
            meta_description: '',
            og_image: '',
            cover_image: '',
        },
    });

    const watchTitle = watch('title');
    const watchSlug = watch('slug');
    const watchStatus = watch('status');
    const watchCategoryId = watch('category_id');
    const watchPublishedAt = watch('published_at');
    const watchExcerpt = watch('excerpt');
    const watchCoverImage = watch('cover_image');

    const { data: postData, isFetching } = useQuery({
        queryKey: ['post', id],
        queryFn: async () => (await api.get(`/admin/posts/${id}`)).data,
        enabled: isEdit,
    });

    const isLivePost = Boolean(postData && ['published', 'scheduled'].includes(postData.status));
    const isLiveSlugChanged = Boolean(
        isLivePost
        && watchSlug
        && postData.slug
        && watchSlug !== postData.slug,
    );

    useEffect(() => {
        if (isEdit && postData) {
            reset({
                title: toFormValue(postData.title),
                slug: toFormValue(postData.slug),
                status: postData.status || 'draft',
                excerpt: toFormValue(postData.excerpt),
                category_id: postData.category_id ? String(postData.category_id) : '',
                published_at: postData.published_at ? postData.published_at.substring(0, 16) : '',
                meta_title: toFormValue(postData.meta_title),
                meta_description: toFormValue(postData.meta_description),
                og_image: toFormValue(postData.og_image),
                cover_image: toFormValue(postData.cover_image),
            });
            const initialContent = postData.content || '';
            setContent(initialContent);
            rawHtmlRef.current = initialContent;
            hydratedTitleRef.current = postData.title;
            const isDraft = (postData.status || 'draft') === 'draft';
            setSlugAuto(isDraft || postData.slug === slugify(postData.title));
            const tagList = postData.tags || [];
            setSelectedTags(tagList.map((tag) => tag.id));
            const getTagName = (tag) => tag?.name ?? '';
            setTagChips(tagList.map((tag) => ({
                id: tag.id,
                name: getTagName(tag),
            })));
            setDirty(false);
        }
    }, [isEdit, postData, reset]);

    useEffect(() => {
        setTags(allTags);

        if (tagChips.length === 0) return;

        const getTagName = (tag) => tag?.name ?? '';

        setTagChips((prev) => prev.map((chip) => {
            if (typeof chip.id !== 'number') return chip;
            const found = allTags.find((t) => t.id === chip.id);
            if (found) {
                const name = getTagName(found);
                if (name) return { ...chip, name };
            }

            return chip;
        }));
    }, [allTags]);

    useEffect(() => {
        if (!slugAuto) return;

        const currentTitle = getValues('title');
        if (!currentTitle) return;

        if (isEdit && hydratedTitleRef.current === currentTitle) return;

        setValue('slug', slugify(currentTitle), { shouldDirty: true });
        hydratedTitleRef.current = currentTitle;
    }, [slugAuto, isEdit, watchTitle, getValues, setValue]);

    const hasUnsavedChanges = dirty || isDirty;

    useEffect(() => {
        if (!hasUnsavedChanges) return;

        const handleBeforeUnload = (event) => {
            event.preventDefault();
            event.returnValue = '';
        };

        window.addEventListener('beforeunload', handleBeforeUnload);

        return () => window.removeEventListener('beforeunload', handleBeforeUnload);
    }, [hasUnsavedChanges]);

    const handleLeaveClick = (event) => {
        if (hasUnsavedChanges && !window.confirm('Perubahan belum disimpan. Tetap tinggalkan halaman?')) {
            event.preventDefault();
        }
    };

    const uploadFile = async (file, folder = 'uploads') => {
        const formData = new FormData();
        formData.append('file', file);
        if (folder) formData.append('folder', folder);
        const { data } = await api.post(endpoints.media, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        queryClient.invalidateQueries({ queryKey: ['media'] });
        return data.media?.path || data.path || null;
    };

    const handleCoverUpload = async (event) => {
        const file = event.target.files?.[0];
        if (file) {
            try {
                const path = await uploadFile(file, 'cover');
                if (path) {
                    setValue('cover_image', path, { shouldDirty: true });
                    setDirty(true);
                    show('Cover berhasil diunggah.');
                }
            } catch (error) {
                show(errorMessage(error), 'error');
            }
        }
        event.target.value = '';
    };

    const handleOgUpload = async (event) => {
        const file = event.target.files?.[0];
        if (file) {
            try {
                const path = await uploadFile(file, 'cover');
                if (path) {
                    setValue('og_image', path, { shouldDirty: true });
                    setDirty(true);
                    show('OG image berhasil diunggah.');
                }
            } catch (error) {
                show(errorMessage(error), 'error');
            }
        }
        event.target.value = '';
    };

    const addTagChip = (name) => {
        const clean = String(name).replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim();
        if (!clean) return;
        const exists = tagChips.some((c) => c.name.toLowerCase() === clean.toLowerCase());
        if (exists) {
            setTagInput('');
            return;
        }
        const finalName = clean.length > 50 ? clean.substring(0, 50) : clean;
        setTagChips((prev) => [...prev, { id: `new:${finalName}`, name: finalName }]);
        setTagInput('');
    };

    const removeTagChip = (chip) => {
        setTagChips((prev) => prev.filter((c) => c !== chip));
        if (typeof chip.id === 'number') {
            setSelectedTags((prev) => prev.filter((id) => id !== chip.id));
        }
    };

    const persistPost = async (formValues) => {
        const tagPayload = [
            ...selectedTags.filter((id) => typeof id === 'number'),
            ...tagChips.filter((c) => typeof c.id === 'string' && c.id.startsWith('new:')).map((c) => c.name),
        ];
        const contentToSave = rawHtmlRef.current ?? content ?? '';
        const payload = normalizeForm({ ...formValues, content: contentToSave, tags: tagPayload });
        try {
            if (isEdit) {
                await api.put(`/admin/posts/${id}`, payload);
                show('Artikel berhasil diperbarui.');
                queryClient.invalidateQueries({ queryKey: ['posts'] });
                queryClient.invalidateQueries({ queryKey: ['dashboard'] });
                queryClient.invalidateQueries({ queryKey: ['post', id] });
            } else {
                const { data } = await api.post(endpoints.posts.list, payload);
                show('Artikel berhasil disimpan.');
                queryClient.invalidateQueries({ queryKey: ['posts'] });
                queryClient.invalidateQueries({ queryKey: ['dashboard'] });
                navigate(`/admin/posts/${data.id}/edit`);
            }
            setDirty(false);
        } catch (error) {
            show(errorMessage(error), 'error');
            throw error;
        }
    };

    const savePost = async (formValues) => {
        if (isLiveSlugChanged) {
            setPendingSlugSubmit(formValues);
            return;
        }

        await persistPost(formValues);
    };

    return (
        <form onSubmit={handleSubmit(savePost)} className="space-y-6">
            <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <h1 className="text-xl font-bold">{isEdit ? 'Edit Artikel' : 'Tulis Artikel'}</h1>
                <div className="flex items-center gap-3">
                    <Link
                        to="/admin/posts"
                        className="px-3 py-1.5 rounded-lg text-sm border border-(--color-border) hover:bg-(--color-accent-bg)"
                    >
                        Kembali
                    </Link>
                    <button
                        type="submit"
                        disabled={isSubmitting}
                        className="px-4 py-2 rounded-lg bg-(--color-accent) text-(--color-on-accent) text-sm font-medium hover:opacity-90 disabled:opacity-60"
                    >
                        {isSubmitting ? 'Menyimpan...' : 'Simpan'}
                    </button>
                </div>
            </div>

            <div className="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div className="xl:col-span-2 space-y-4">
                    <div className="bg-(--color-surface) border border-(--color-border) rounded-xl p-5 space-y-4">
                        <div>
                            <label className="block text-sm font-medium mb-1.5">Judul</label>
                            <input
                                type="text"
                                {...register('title')}
                                className="w-full px-4 py-2.5 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm focus:border-(--color-accent) outline-none"
                            />
                            {errors.title && <p className="text-xs text-(--color-error) mt-1">{errors.title.message}</p>}
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1.5">Slug</label>
                            <input
                                type="text"
                                {...register('slug', { onChange: () => setSlugAuto(false) })}
                                className="w-full px-4 py-2.5 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm focus:border-(--color-accent) outline-none"
                                placeholder="otomatis dari judul"
                            />
                            {errors.slug && <p className="text-xs text-(--color-error) mt-1">{errors.slug.message}</p>}
                            {isLiveSlugChanged && (
                                <p className="text-xs text-(--color-warning) mt-1.5 flex gap-1.5">
                                    <span aria-hidden="true">âš </span>
                                    <span>
                                        Slug berubah dari <code>/{postData.slug}</code>. URL artikel yang sudah terbit akan ikut berubah dan tautan lama bisa tidak berfungsi.
                                    </span>
                                </p>
                            )}
                        </div>
                        <div>
                            <div className="flex items-center justify-between mb-2">
                                <label className="block text-sm font-medium">Konten (HTML)</label>
                                <span className="text-xs text-(--color-text-muted)">Edit langsung HTML mentah</span>
                            </div>
                            <div className="article-editor border border-(--color-border) rounded-lg overflow-hidden bg-(--color-surface) resize-y min-h-[420px] md:min-h-[520px]">
                                <textarea
                                    value={content}
                                    onChange={(e) => {
                                        setContent(e.target.value);
                                        rawHtmlRef.current = e.target.value;
                                        setDirty(true);
                                    }}
                                    onKeyDown={(e) => {
                                        if (e.key !== ' ') return;
                                        const el = e.target;
                                        const start = el.selectionStart;
                                        const value = el.value;
                                        const before = value.slice(0, start);

                                        const snippets = [
                                            { trigger: '/p', insert: '<p></p>' },
                                            { trigger: '/div', insert: '<div></div>' },
                                            { trigger: '/h2', insert: '<h2></h2>' },
                                            { trigger: '/h3', insert: '<h3></h3>' },
                                            { trigger: '/h4', insert: '<h4></h4>' },
                                            { trigger: '/ul', insert: '<ul>\n  <li></li>\n</ul>' },
                                            { trigger: '/ol', insert: '<ol>\n  <li></li>\n</ol>' },
                                            { trigger: '/br', insert: '<br>' },
                                            { trigger: '/hr', insert: '<hr>' },
                                            { trigger: '/pre', insert: '<pre><code class="language-php"></code></pre>' },
                                            { trigger: '/code', insert: '<code></code>' },
                                        ];

                                        for (const snippet of snippets) {
                                            if (before.endsWith(snippet.trigger)) {
                                                e.preventDefault();
                                                const after = value.slice(start);
                                                const newValue = before.slice(0, -snippet.trigger.length) + snippet.insert + after;
                                                setContent(newValue);
                                                rawHtmlRef.current = newValue;
                                                setDirty(true);

                                                requestAnimationFrame(() => {
                                                    if (snippet.insert.includes('</')) {
                                                        const tagMatch = snippet.insert.match(/<([a-z0-9]+)/i);
                                                        if (tagMatch) {
                                                            const tag = tagMatch[1];
                                                            const closeTag = `</${tag}>`;
                                                            const pos = before.length - snippet.trigger.length + snippet.insert.indexOf(closeTag);
                                                            el.selectionStart = el.selectionEnd = pos;
                                                            return;
                                                        }
                                                    }
                                                    const pos = before.length - snippet.trigger.length + snippet.insert.length;
                                                    el.selectionStart = el.selectionEnd = pos;
                                                });
                                                break;
                                            }
                                        }
                                    }}
                                    className="w-full h-[420px] md:h-[520px] p-4 bg-(--color-surface) text-sm font-mono focus:outline-none"
                                    spellCheck={false}
                                    placeholder="<p>Tulis HTML mentah di sini...</p>"
                                />
                            </div>
                            <p className="text-xs text-(--color-text-muted) mt-1.5">
                                Struktur HTML akan disimpan persis seperti yang kamu tulis.
                            </p>
                        </div>
                    </div>
                </div>

                <aside className="space-y-4">
                    <div className="bg-(--color-surface) border border-(--color-border) rounded-xl p-5 space-y-3">
                        <div>
                            <label className="block text-sm font-medium mb-1.5">Status</label>
                            <select
                                {...register('status')}
                                className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm"
                            >
                                <option value="draft">Draft</option>
                                <option value="published">Terbit</option>
                                <option value="scheduled">Terjadwal</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1.5">Dipublikasi pada</label>
                            <input
                                type="datetime-local"
                                {...register('published_at')}
                                className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1.5">Kategori</label>
                            <select
                                {...register('category_id')}
                                className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm"
                            >
                                <option value="">Tidak ada</option>
                                {categories.map((cat) => (
                                    <option key={cat.id} value={cat.id}>
                                        {cat.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <div className="bg-(--color-surface) border border-(--color-border) rounded-xl p-5 space-y-3">
                        <h3 className="font-semibold text-sm">Tag</h3>
                        <div className="flex flex-wrap gap-2">
                            {tagChips.map((chip, idx) => (
                                <span
                                    key={`${chip.id}-${idx}`}
                                    className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs border border-(--color-accent) bg-(--color-accent-bg) text-(--color-accent)"
                                >
                                    {chip.name}
                                    <button
                                        type="button"
                                        onClick={() => removeTagChip(chip)}
                                        className="ml-1 text-(--color-accent) hover:text-(--color-error)"
                                    >
                                        ×
                                    </button>
                                </span>
                            ))}
                        </div>
                        <div>
                            <input
                                type="text"
                                value={tagInput}
                                onChange={(e) => setTagInput(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        e.preventDefault();
                                        addTagChip(tagInput);
                                    } else if (e.key === 'Tab' && tagInput.trim()) {
                                        e.preventDefault();
                                        addTagChip(tagInput);
                                    } else if (e.key === 'Backspace' && !tagInput && tagChips.length > 0) {
                                        removeTagChip(tagChips[tagChips.length - 1]);
                                    }
                                }}
                                onBlur={() => {
                                    if (tagInput.trim()) {
                                        addTagChip(tagInput);
                                    }
                                }}
                                className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm"
                                placeholder="Ketik tag lalu Enter..."
                                list="tag-suggestions"
                            />
                            <datalist id="tag-suggestions">
                                {tags.map((tag) => (
                                    <option
                                        key={tag.id}
                                        value={tag.name}
                                    />
                                ))}
                            </datalist>
                        </div>
                    </div>

                    <div className="bg-(--color-surface) border border-(--color-border) rounded-xl p-5 space-y-3">
                        <h3 className="font-semibold text-sm">SEO</h3>
                        <div>
                            <label className="block text-sm font-medium mb-1.5">Meta Title</label>
                            <input
                                type="text"
                                {...register('meta_title')}
                                className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1.5">Meta Description</label>
                            <textarea
                                rows="3"
                                {...register('meta_description')}
                                className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm resize-y"
                            />
                        </div>
                        <div className="space-y-2">
                            <label className="block text-sm font-medium mb-1.5">OG Image</label>
                            {watch('og_image') && (
                                <img
                                    src={resolveMediaUrl(watch('og_image'))}
                                    alt="OG preview"
                                    className="w-full h-32 object-cover rounded-lg border border-(--color-border)"
                                />
                            )}
                            <input
                                type="text"
                                {...register('og_image')}
                                className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm"
                                placeholder="URL eksternal atau path relatif"
                            />
                            <div className="flex flex-wrap gap-2">
                                <button type="button" onClick={() => ogInputRef.current?.click()} className="px-2.5 py-1.5 rounded-lg text-xs border border-(--color-border) hover:bg-(--color-accent-bg)">
                                    Upload
                                </button>
                                <button type="button" onClick={() => setShowMediaPicker('og')} className="px-2.5 py-1.5 rounded-lg text-xs border border-(--color-border) hover:bg-(--color-accent-bg)">
                                    Pilih dari Media
                                </button>
                                {watch('og_image') && (
                                    <button type="button" onClick={() => { setValue('og_image', ''); setDirty(true); }} className="px-2.5 py-1.5 rounded-lg text-xs border border-(--color-error) text-(--color-error) hover:bg-(--color-accent-bg)">
                                        Hapus
                                    </button>
                                )}
                            </div>
                            <input ref={ogInputRef} type="file" accept="image/*" className="hidden" onChange={handleOgUpload} />
                        </div>
                        <div className="space-y-2">
                            <label className="block text-sm font-medium mb-1.5">Cover Image</label>
                            {watch('cover_image') && (
                                <img
                                    src={resolveMediaUrl(watch('cover_image'))}
                                    alt="Cover preview"
                                    className="w-full h-40 object-cover rounded-lg border border-(--color-border)"
                                />
                            )}
                            <input
                                type="text"
                                {...register('cover_image')}
                                className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm"
                                placeholder="URL eksternal atau path relatif"
                            />
                            <div className="flex flex-wrap gap-2">
                                <button type="button" onClick={() => coverInputRef.current?.click()} className="px-2.5 py-1.5 rounded-lg text-xs border border-(--color-border) hover:bg-(--color-accent-bg)">
                                    Upload
                                </button>
                                <button type="button" onClick={() => setShowMediaPicker('cover')} className="px-2.5 py-1.5 rounded-lg text-xs border border-(--color-border) hover:bg-(--color-accent-bg)">
                                    Pilih dari Media
                                </button>
                                {watch('cover_image') && (
                                    <button type="button" onClick={() => { setValue('cover_image', ''); setDirty(true); }} className="px-2.5 py-1.5 rounded-lg text-xs border border-(--color-error) text-(--color-error) hover:bg-(--color-accent-bg)">
                                        Hapus
                                    </button>
                                )}
                            </div>
                            <input ref={coverInputRef} type="file" accept="image/*" className="hidden" onChange={handleCoverUpload} />
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1.5">Excerpt</label>
                            <textarea
                                rows="3"
                                {...register('excerpt')}
                                className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm resize-y"
                            />
                        </div>
                    </div>
                </aside>
            </div>

            {showMediaPicker && (
                <MediaPicker
                    folder="cover"
                    onSelect={(path) => {
                        if (showMediaPicker === 'cover') {
                            setValue('cover_image', path, { shouldDirty: true });
                        } else if (showMediaPicker === 'og') {
                            setValue('og_image', path, { shouldDirty: true });
                        }
                        setDirty(true);
                    }}
                    onClose={() => setShowMediaPicker(null)}
                />
            )}

            <ConfirmModal
                open={Boolean(pendingSlugSubmit)}
                title="Ubah slug artikel terbit?"
                message={`Slug akan berubah dari "/${postData?.slug}" menjadi "/${watchSlug}". URL lama tidak dialihkan otomatis, sehingga tautan yang sudah dibagikan atau diindeks mesin pencari bisa rusak.`}
                confirmLabel="Ya, ubah slug"
                cancelLabel="Batal"
                onCancel={() => setPendingSlugSubmit(null)}
                onConfirm={async () => {
                    await persistPost(pendingSlugSubmit);
                    setPendingSlugSubmit(null);
                }}
            />

            <Toast toast={toast} />
        </form>
    );
}
