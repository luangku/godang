import { useEffect, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api, endpoints, errorMessage } from '../api';
import MediaPicker from '../components/MediaPicker';
import { Toast, useToast } from '../components/Toast';
import { resolveMediaUrl } from '../media';

const GENERAL_FIELDS = [
    { key: 'site_title', label: 'Judul Situs', max: 150 },
    { key: 'site_description', label: 'Deskripsi Situs', max: 500 },
    { key: 'tagline', label: 'Tagline', max: 255 },
    { key: 'default_meta_title', label: 'Meta Title Default', max: 255 },
    { key: 'default_meta_description', label: 'Meta Description Default', max: 300, counter: true },
    { key: 'github_url', label: 'URL GitHub', type: 'url' },
    { key: 'twitter_url', label: 'URL Twitter/X', type: 'url' },
    { key: 'linkedin_url', label: 'URL LinkedIn', type: 'url' },
];

const ABOUT_FIELDS = [
    { key: 'author_name', label: 'Nama Penulis', max: 150 },
    { key: 'author_avatar_alt', label: 'Alt Text Avatar', max: 255 },
    { key: 'author_role', label: 'Role', max: 150 },
    { key: 'about_bio', label: 'Bio', type: 'textarea', rows: 6, max: 5000, hint: 'Pisahkan paragraf dengan baris kosong.' },
    { key: 'about_topics_heading', label: 'Judul Bagian Topik', max: 150 },
    { key: 'about_topics', label: 'Topik', type: 'textarea', rows: 5, max: 2000, hint: 'Satu topik per baris.' },
    { key: 'about_contact_heading', label: 'Judul Bagian Kontak', max: 150 },
    { key: 'about_contact', label: 'Teks Kontak', type: 'textarea', rows: 3, max: 1000 },
    { key: 'about_meta_description', label: 'Meta Description About', max: 300, counter: true },
];

const TOGGLE_FIELDS = [
    { key: 'comment_moderation', label: 'Moderasi komentar', hint: 'Komentar baru menunggu persetujuan sebelum tampil.' },
    { key: 'comment_notification', label: 'Notifikasi komentar', hint: 'Kirim email ke admin saat ada komentar baru.' },
];

const ALL_FIELDS = [...GENERAL_FIELDS, ...ABOUT_FIELDS];

const TABS = [
    { id: 'umum', label: 'Umum', fields: GENERAL_FIELDS },
    { id: 'about', label: 'Profil & About', fields: ABOUT_FIELDS },
    { id: 'komentar', label: 'Komentar', fields: [] },
];

function Field({ field, value, onChange }) {
    const length = String(value ?? '').length;
    const overLimit = field.counter && length > field.max;

    return (
        <div className={field.full ? 'md:col-span-2' : ''}>
            <div className="flex items-center justify-between gap-2 mb-1.5">
                <label className="block text-sm font-medium">{field.label}</label>
                {field.counter && (
                    <span className={`text-[11px] ${overLimit ? 'text-(--color-error)' : 'text-(--color-text-muted)'}`}>
                        {length}/{field.max}
                    </span>
                )}
            </div>
            {field.type === 'textarea' ? (
                <textarea
                    value={value ?? ''}
                    onChange={(event) => onChange(event.target.value)}
                    rows={field.rows ?? 4}
                    maxLength={field.max}
                    className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm resize-y"
                />
            ) : (
                <input
                    type={field.type ?? 'text'}
                    value={value ?? ''}
                    onChange={(event) => onChange(event.target.value)}
                    maxLength={field.max}
                    className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm"
                />
            )}
            {field.hint && <p className="mt-1 text-[11px] text-(--color-text-muted)">{field.hint}</p>}
        </div>
    );
}

export default function Settings() {
    const queryClient = useQueryClient();
    const { toast, show } = useToast();
    const [form, setForm] = useState(null);
    const [activeTab, setActiveTab] = useState('umum');
    const [pickerOpen, setPickerOpen] = useState(false);
    const [logoPickerOpen, setLogoPickerOpen] = useState(false);
    const [logoDarkPickerOpen, setLogoDarkPickerOpen] = useState(false);
    const [dirty, setDirty] = useState(false);

    const { data, isLoading } = useQuery({
        queryKey: ['settings'],
        queryFn: async () => (await api.get(endpoints.settings)).data,
    });

    useEffect(() => {
        if (!data) return;

        const next = {};
        for (const field of ALL_FIELDS) next[field.key] = data[field.key] ?? '';
        next.author_avatar = data.author_avatar ?? '';
        next.site_logo = data.site_logo ?? '';
        next.site_logo_dark = data.site_logo_dark ?? '';
        next.site_logo_alt = data.site_logo_alt ?? '';
        for (const field of TOGGLE_FIELDS) next[field.key] = (data[field.key] ?? 'true') === 'true';

        setForm(next);
        setDirty(false);
    }, [data]);

    useEffect(() => {
        if (!dirty) return undefined;

        const handler = (event) => {
            event.preventDefault();
            event.returnValue = '';
        };

        window.addEventListener('beforeunload', handler);
        return () => window.removeEventListener('beforeunload', handler);
    }, [dirty]);

    const mutation = useMutation({
        mutationFn: async (settings) => (await api.put(endpoints.settings, { settings })).data,
        onSuccess: (response) => {
            queryClient.invalidateQueries({ queryKey: ['settings'] });
            setDirty(false);
            show(response.message || 'Pengaturan disimpan.');
        },
        onError: (error) => show(errorMessage(error), 'error'),
    });

    const updateField = (key, value) => {
        setForm((current) => ({ ...current, [key]: value }));
        setDirty(true);
    };

    const handleSubmit = (event) => {
        event.preventDefault();
        if (!form) return;

        const settings = {};
        for (const field of ALL_FIELDS) settings[field.key] = form[field.key] ?? '';
        settings.author_avatar = form.author_avatar ?? '';
        settings.site_logo = form.site_logo ?? '';
        settings.site_logo_dark = form.site_logo_dark ?? '';
        settings.site_logo_alt = form.site_logo_alt ?? '';
        for (const field of TOGGLE_FIELDS) settings[field.key] = form[field.key] ? 'true' : 'false';

        mutation.mutate(settings);
    };

    if (!form) {
        return <p className="text-sm text-(--color-text-muted)">{isLoading ? 'Memuat...' : 'Data tidak tersedia.'}</p>;
    }

    const avatarUrl = resolveMediaUrl(form.author_avatar);
    const logoUrl = resolveMediaUrl(form.site_logo);
    const logoDarkUrl = resolveMediaUrl(form.site_logo_dark);

    return (
        <div className="space-y-6">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <h1 className="text-xl font-bold">Pengaturan</h1>
                <div className="flex items-center gap-3 text-xs">
                    {dirty && <span className="text-(--color-text-muted)">Belum disimpan</span>}
                </div>
            </div>

            <div className="flex flex-wrap gap-1 border-b border-(--color-border)">
                {TABS.map((tab) => (
                    <button
                        key={tab.id}
                        type="button"
                        onClick={() => setActiveTab(tab.id)}
                        className={`px-4 py-2 -mb-px text-sm border-b-2 transition-colors ${
                            activeTab === tab.id
                                ? 'border-(--color-accent) text-(--color-accent) font-medium'
                                : 'border-transparent text-(--color-text-muted) hover:text-(--color-text)'
                        }`}
                    >
                        {tab.label}
                    </button>
                ))}
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">
                {activeTab === 'umum' && (
                    <>
                        <section className="bg-(--color-surface) border border-(--color-border) rounded-xl p-5 space-y-4">
                            <h2 className="font-semibold text-sm">Logo Navbar</h2>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="rounded-lg border border-(--color-border) p-4 space-y-3">
                                    <div className="text-xs font-medium text-(--color-text-muted)">Mode Terang</div>
                                    {logoUrl ? (
                                        <img
                                            src={logoUrl}
                                            alt={form.site_logo_alt || form.site_title || 'Logo'}
                                            className="h-10 max-w-[200px] object-contain"
                                        />
                                    ) : (
                                        <div className="h-10 px-3 flex items-center justify-center rounded-lg border border-dashed border-(--color-border) text-sm font-bold text-(--color-accent)">
                                            {form.site_title || 'Godang'}
                                        </div>
                                    )}
                                    <div className="flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            onClick={() => setLogoPickerOpen(true)}
                                            className="px-3 py-2 rounded-lg border border-(--color-border) text-sm hover:bg-(--color-accent-bg)"
                                        >
                                            Pilih dari Media
                                        </button>
                                        {form.site_logo && (
                                            <button
                                                type="button"
                                                onClick={() => updateField('site_logo', '')}
                                                className="px-3 py-2 rounded-lg border border-(--color-error) text-(--color-error) text-sm hover:bg-(--color-accent-bg)"
                                            >
                                                Hapus
                                            </button>
                                        )}
                                    </div>
                                </div>

                                <div className="rounded-lg border border-(--color-border) p-4 space-y-3">
                                    <div className="text-xs font-medium text-(--color-text-muted)">Mode Gelap</div>
                                    {logoDarkUrl ? (
                                        <img
                                            src={logoDarkUrl}
                                            alt={form.site_logo_alt || form.site_title || 'Logo'}
                                            className="h-10 max-w-[200px] object-contain"
                                        />
                                    ) : (
                                        <div className="h-10 px-3 flex items-center justify-center rounded-lg border border-dashed border-(--color-border) text-xs text-(--color-text-muted)">
                                            Mengikuti logo mode terang
                                        </div>
                                    )}
                                    <div className="flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            onClick={() => setLogoDarkPickerOpen(true)}
                                            className="px-3 py-2 rounded-lg border border-(--color-border) text-sm hover:bg-(--color-accent-bg)"
                                        >
                                            Pilih dari Media
                                        </button>
                                        {form.site_logo_dark && (
                                            <button
                                                type="button"
                                                onClick={() => updateField('site_logo_dark', '')}
                                                className="px-3 py-2 rounded-lg border border-(--color-error) text-(--color-error) text-sm hover:bg-(--color-accent-bg)"
                                            >
                                                Hapus
                                            </button>
                                        )}
                                    </div>
                                </div>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium mb-1.5">Alt Text Logo</label>
                                    <input
                                        type="text"
                                        value={form.site_logo_alt ?? ''}
                                        onChange={(event) => updateField('site_logo_alt', event.target.value)}
                                        className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm"
                                        placeholder="Deskripsi logo untuk aksesibilitas"
                                    />
                                </div>
                            </div>
                            <p className="text-[11px] text-(--color-text-muted)">
                                Logo mode gelap bersifat opsional. Jika kosong, navbar memakai logo mode terang. Jika keduanya kosong, navbar menampilkan judul situs.
                            </p>
                        </section>

                        <section className="bg-(--color-surface) border border-(--color-border) rounded-xl p-5 space-y-4">
                            <h2 className="font-semibold text-sm">Umum</h2>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {GENERAL_FIELDS.map((field) => (
                                    <Field
                                        key={field.key}
                                        field={field}
                                        value={form[field.key]}
                                        onChange={(value) => updateField(field.key, value)}
                                    />
                                ))}
                            </div>
                        </section>
                    </>
                )}

                {activeTab === 'about' && (
                    <>
                        <section className="bg-(--color-surface) border border-(--color-border) rounded-xl p-5 space-y-4">
                            <h2 className="font-semibold text-sm">Foto Profil</h2>
                            <div className="flex flex-col sm:flex-row sm:items-center gap-5">
                                {avatarUrl ? (
                                    <img
                                        src={avatarUrl}
                                        alt={form.author_avatar_alt || form.author_name || 'Avatar'}
                                        className="w-20 h-20 rounded-full object-cover border border-(--color-border)"
                                    />
                                ) : (
                                    <div className="w-20 h-20 rounded-full bg-gradient-to-br from-(--color-accent) to-purple-500 flex items-center justify-center text-2xl font-bold text-white">
                                        {(form.author_name || 'G').charAt(0).toUpperCase()}
                                    </div>
                                )}
                                <div className="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        onClick={() => setPickerOpen(true)}
                                        className="px-3 py-2 rounded-lg border border-(--color-border) text-sm hover:bg-(--color-accent-bg)"
                                    >
                                        Pilih dari Media
                                    </button>
                                    {form.author_avatar && (
                                        <button
                                            type="button"
                                            onClick={() => updateField('author_avatar', '')}
                                            className="px-3 py-2 rounded-lg border border-(--color-error) text-(--color-error) text-sm hover:bg-(--color-accent-bg)"
                                        >
                                            Hapus
                                        </button>
                                    )}
                                </div>
                            </div>
                            <p className="text-[11px] text-(--color-text-muted)">
                                Jika avatar kosong, halaman publik akan menampilkan huruf awal nama penulis.
                            </p>
                        </section>

                        <section className="bg-(--color-surface) border border-(--color-border) rounded-xl p-5 space-y-4">
                            <h2 className="font-semibold text-sm">Profil & Halaman About</h2>
                            <div className="grid grid-cols-1 gap-4">
                                {ABOUT_FIELDS.map((field) => (
                                    <Field
                                        key={field.key}
                                        field={field}
                                        value={form[field.key]}
                                        onChange={(value) => updateField(field.key, value)}
                                    />
                                ))}
                            </div>
                        </section>
                    </>
                )}

                {activeTab === 'komentar' && (
                    <section className="bg-(--color-surface) border border-(--color-border) rounded-xl p-5 space-y-4">
                        <h2 className="font-semibold text-sm">Komentar</h2>
                        {TOGGLE_FIELDS.map((field) => (
                            <label key={field.key} className="flex items-start gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={Boolean(form[field.key])}
                                    onChange={(event) => updateField(field.key, event.target.checked)}
                                    className="mt-1 rounded accent-(--color-accent)"
                                />
                                <span>
                                    <span className="block text-sm font-medium">{field.label}</span>
                                    <span className="block text-xs text-(--color-text-muted)">{field.hint}</span>
                                </span>
                            </label>
                        ))}
                    </section>
                )}

                <div className="flex items-center gap-3">
                    <button
                        type="submit"
                        disabled={mutation.isPending}
                        className="px-5 py-2.5 rounded-lg bg-(--color-accent) text-(--color-on-accent) text-sm font-medium hover:opacity-90 disabled:opacity-60"
                    >
                        {mutation.isPending ? 'Menyimpan...' : 'Simpan Pengaturan'}
                    </button>
                    {dirty && <span className="text-xs text-(--color-text-muted)">Ada perubahan yang belum disimpan.</span>}
                </div>
            </form>

            {pickerOpen && (
                <MediaPicker
                    folder="avatar"
                    onSelect={(path) => updateField('author_avatar', path)}
                    onClose={() => setPickerOpen(false)}
                />
            )}

            {logoPickerOpen && (
                <MediaPicker
                    folder="all"
                    onSelect={(path) => updateField('site_logo', path)}
                    onClose={() => setLogoPickerOpen(false)}
                />
            )}

            {logoDarkPickerOpen && (
                <MediaPicker
                    folder="all"
                    onSelect={(path) => updateField('site_logo_dark', path)}
                    onClose={() => setLogoDarkPickerOpen(false)}
                />
            )}

            <Toast toast={toast} />
        </div>
    );
}
