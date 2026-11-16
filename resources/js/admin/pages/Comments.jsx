import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { api, endpoints, errorMessage } from '../api';
import StatusBadge from '../components/StatusBadge';
import ConfirmModal from '../components/ConfirmModal';
import { Toast, useToast } from '../components/Toast';

function formatDate(date) {
    try {
        return new Date(date).toLocaleString('id-ID');
    } catch {
        return date;
    }
}

export default function Comments() {
    const queryClient = useQueryClient();
    const { toast, show } = useToast();
    const [status, setStatus] = useState('');
    const [categoryId, setCategoryId] = useState('');
    const [sort, setSort] = useState('desc');
    const [selected, setSelected] = useState([]);
    const [confirm, setConfirm] = useState(null);
    const [busy, setBusy] = useState(false);
    const [replying, setReplying] = useState({ id: null, text: '' });
    const [editing, setEditing] = useState({ id: null, body: '' });

    const { data, isLoading } = useQuery({
        queryKey: ['comments', status, categoryId, sort],
        queryFn: async () =>
            (
                await api.get(endpoints.comments, {
                    params: {
                        ...(status ? { status } : {}),
                        ...(categoryId ? { category_id: categoryId } : {}),
                        sort,
                    },
                })
            ).data,
    });

    const { data: categories = [] } = useQuery({
        queryKey: ['categories'],
        queryFn: async () => (await api.get(endpoints.categories)).data,
    });

    const invalidate = () => {
        queryClient.invalidateQueries({ queryKey: ['comments'] });
        queryClient.invalidateQueries({ queryKey: ['dashboard'] });
    };

    const mutation = useMutation({
        mutationFn: async ({ method, url, payload }) => (await api({ method, url, data: payload })).data,
        onSuccess: (resp) => {
            invalidate();
            show(resp.message || 'Berhasil.');
            setEditing({ id: null, body: '' });
        },
        onError: (error) => show(errorMessage(error), 'error'),
    });

    const handleApprove = (comment) => {
        mutation.mutate({ method: 'PATCH', url: `/admin/comments/${comment.id}` });
    };

    const handleSpam = (comment) => {
        mutation.mutate({ method: 'PATCH', url: endpoints.commentSpam(comment.id) });
    };

    const handleDelete = (comment) => {
        setConfirm({
            title: 'Hapus komentar',
            message: comment.parent_id
                ? `Hapus balasan dari ${comment.name}?`
                : `Hapus komentar dari ${comment.name}? Termasuk balasannya jika ada.`,
            confirmLabel: 'Hapus',
            action: async () => {
                setBusy(true);
                try {
                    await mutation.mutateAsync({ method: 'DELETE', url: `/admin/comments/${comment.id}` });
                    setConfirm(null);
                } finally {
                    setBusy(false);
                }
            },
        });
    };

    const handleBulkDelete = () => {
        setConfirm({
            title: 'Hapus komentar',
            message: `Hapus ${selected.length} komentar terpilih?`,
            confirmLabel: 'Hapus',
            action: async () => {
                setBusy(true);
                try {
                    await mutation.mutateAsync({
                        method: 'POST',
                        url: endpoints.commentBulkDelete,
                        payload: { ids: selected },
                    });
                    setSelected([]);
                    setConfirm(null);
                } finally {
                    setBusy(false);
                }
            },
        });
    };

    const handleUpdate = async (comment) => {
        if (!editing.body.trim()) return;
        try {
            await mutation.mutateAsync({
                method: 'PUT',
                url: `/admin/comments/${comment.id}`,
                payload: { body: editing.body },
            });
        } catch {
            /* handled */
        }
    };

    const handleReply = async (comment) => {
        if (!replying.text.trim()) return;
        try {
            await mutation.mutateAsync({
                method: 'POST',
                url: endpoints.commentReply(comment.id),
                payload: { body: replying.text },
            });
            setReplying({ id: null, text: '' });
        } catch {
            /* handled */
        }
    };

    const replyForm = (target) =>
        replying.id === target.id ? (
            <div className="space-y-2 mt-2">
                <textarea
                    rows="3"
                    value={replying.text}
                    onChange={(event) => setReplying({ id: target.id, text: event.target.value })}
                    className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm"
                    placeholder="Tulis balasan..."
                />
                <div className="flex justify-end gap-2">
                    <button
                        type="button"
                        onClick={() => setReplying({ id: null, text: '' })}
                        className="px-3 py-1.5 rounded-lg text-xs border border-(--color-border) hover:bg-(--color-accent-bg)"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        onClick={() => handleReply(target)}
                        className="px-3 py-1.5 rounded-lg text-xs bg-(--color-accent) text-(--color-on-accent) hover:opacity-90"
                    >
                        Kirim Balasan
                    </button>
                </div>
            </div>
        ) : null;

    const bodyBlock = (target, className = 'mt-2') =>
        editing.id === target.id ? (
            <div className={`space-y-2 ${className}`}>
                <textarea
                    rows="3"
                    value={editing.body}
                    onChange={(event) => setEditing({ id: target.id, body: event.target.value })}
                    className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm resize-y"
                />
                <div className="flex justify-end gap-2">
                    <button
                        type="button"
                        onClick={() => setEditing({ id: null, body: '' })}
                        className="px-3 py-1.5 rounded-lg text-xs border border-(--color-border) hover:bg-(--color-accent-bg)"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        onClick={() => handleUpdate(target)}
                        disabled={!editing.body.trim()}
                        className="px-3 py-1.5 rounded-lg text-xs bg-(--color-accent) text-(--color-on-accent) hover:opacity-90 disabled:opacity-50"
                    >
                        Simpan
                    </button>
                </div>
            </div>
        ) : (
            <p className={`${className} text-sm whitespace-pre-wrap`}>{target.body}</p>
        );

    const toggleAll = () => {
        if (data?.data && data.data.length && selected.length === data.data.length) {
            setSelected([]);
        } else if (data?.data) {
            setSelected(data.data.map((c) => c.id));
        }
    };

    const toggleOne = (id) => {
        setSelected((current) => (current.includes(id) ? current.filter((i) => i !== id) : [...current, id]));
    };

    return (
        <div className="space-y-4">
            <h1 className="text-xl font-bold">Komentar</h1>

            <div className="flex flex-wrap items-center gap-3">
                <select
                    value={status}
                    onChange={(event) => setStatus(event.target.value)}
                    className="px-3 py-2 rounded-lg bg-(--color-surface) border border-(--color-border) text-sm"
                >
                    <option value="">Semua status</option>
                    <option value="pending">Menunggu</option>
                    <option value="approved">Disetujui</option>
                    <option value="spam">Spam</option>
                </select>
                <select
                    value={categoryId}
                    onChange={(event) => setCategoryId(event.target.value)}
                    className="px-3 py-2 rounded-lg bg-(--color-surface) border border-(--color-border) text-sm"
                >
                    <option value="">Semua kategori</option>
                    {categories.map((cat) => (
                        <option key={cat.id} value={cat.id}>
                            {cat.name || cat.slug}
                        </option>
                    ))}
                </select>
                <select
                    value={sort}
                    onChange={(event) => setSort(event.target.value)}
                    className="px-3 py-2 rounded-lg bg-(--color-surface) border border-(--color-border) text-sm"
                >
                    <option value="desc">Terbaru dulu</option>
                    <option value="asc">Terlama dulu</option>
                </select>
            </div>

            {selected.length > 0 && (
                <div className="flex items-center gap-3 px-4 py-2 rounded-lg bg-(--color-accent-bg) border border-(--color-accent) text-sm">
                    <span>{selected.length} terpilih</span>
                    <button
                        type="button"
                        onClick={handleBulkDelete}
                        className="font-medium text-(--color-error) hover:underline"
                    >
                        Hapus terpilih
                    </button>
                    <button
                        type="button"
                        onClick={() => setSelected([])}
                        className="text-(--color-text-muted) hover:underline"
                    >
                        Batalkan
                    </button>
                </div>
            )}

            <div className="space-y-3">
                {isLoading && <p className="text-sm text-(--color-text-muted)">Memuat...</p>}
                {!isLoading && data?.data?.length === 0 && (
                    <p className="text-sm text-(--color-text-muted)">Tidak ada komentar.</p>
                )}
                {data?.data?.map((comment) => (
                    <article
                        key={comment.id}
                        className="bg-(--color-surface) border border-(--color-border) rounded-xl p-5 space-y-4"
                    >
                        <div className="flex items-start justify-between gap-4">
                            <div className="flex items-start gap-3 min-w-0">
                                <input
                                    type="checkbox"
                                    checked={selected.includes(comment.id)}
                                    onChange={() => toggleOne(comment.id)}
                                    className="mt-1 rounded accent-(--color-accent)"
                                />
                                <div className="min-w-0">
                                    <div className="flex items-center gap-2 flex-wrap">
                                        <span className="font-medium text-sm">{comment.name}</span>
                                        <StatusBadge status={comment.status} />
                                        {comment.is_author && (
                                            <span className="text-xs px-2 py-0.5 rounded-md bg-(--color-accent) text-(--color-on-accent)">
                                                Admin
                                            </span>
                                        )}
                                    </div>
                                    <p className="text-xs text-(--color-text-muted) mt-1 truncate">
                                        {comment.email} • {formatDate(comment.created_at)}
                                        {comment.post && (
                                            <>
                                                {' • '}
                                                <Link
                                                    to={`/admin/posts/${comment.post.id}/view`}
                                                    title="Lihat artikel"
                                                    className="text-(--color-accent) hover:underline"
                                                >
                                                    {comment.post.title}
                                                </Link>
                                            </>
                                        )}
                                    </p>
                                    {comment.parent && (
                                        <p className="text-xs text-(--color-accent) mt-1 truncate">
                                            Membalas: {comment.parent.name}
                                        </p>
                                    )}
                                    {bodyBlock(comment)}
                                </div>
                            </div>
                            <div className="flex flex-wrap items-center gap-2 text-xs">
                                {comment.status === 'pending' && (
                                    <button
                                        type="button"
                                        onClick={() => handleApprove(comment)}
                                        className="px-2.5 py-1 rounded-lg border border-(--color-success) text-(--color-success) hover:bg-(--color-accent-bg)"
                                    >
                                        Setujui
                                    </button>
                                )}
                                {!comment.is_author && comment.status !== 'spam' && (
                                    <button
                                        type="button"
                                        onClick={() => handleSpam(comment)}
                                        className="px-2.5 py-1 rounded-lg border border-(--color-warning) text-(--color-warning) hover:bg-(--color-accent-bg)"
                                    >
                                        Spam
                                    </button>
                                )}
                                {comment.is_author && (
                                    <button
                                        type="button"
                                        onClick={() => setEditing({ id: comment.id, body: comment.body })}
                                        className="px-2.5 py-1 rounded-lg border border-(--color-border) hover:bg-(--color-accent-bg)"
                                    >
                                        Edit
                                    </button>
                                )}
                                <button
                                    type="button"
                                    onClick={() => setReplying({ id: comment.id, text: '' })}
                                    className="px-2.5 py-1 rounded-lg border border-(--color-border) hover:bg-(--color-accent-bg)"
                                >
                                    Balas
                                </button>
                                <button
                                    type="button"
                                    onClick={() => handleDelete(comment)}
                                    className="px-2.5 py-1 rounded-lg border border-(--color-error) text-(--color-error) hover:bg-(--color-accent-bg)"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>

                        {comment.replies?.length > 0 && (
                            <div className="space-y-3 ml-4 pl-4 border-l-2 border-(--color-accent)/20">
                                {comment.replies.map((reply) => (
                                    <div key={reply.id} className="text-sm">
                                        <div className="flex items-center gap-2">
                                            <span className="font-medium">{reply.name}</span>
                                            <StatusBadge status={reply.status} />
                                            {reply.is_author && (
                                                <span className="text-xs px-2 py-0.5 rounded-md bg-(--color-accent) text-(--color-on-accent)">
                                                    Admin
                                                </span>
                                            )}
                                            {reply.reply_to_name && (
                                                <span className="text-xs text-(--color-text-muted)">
                                                    membalas ke @{reply.reply_to_name}
                                                </span>
                                            )}
                                            <span className="text-xs text-(--color-text-muted)">
                                                {formatDate(reply.created_at)}
                                            </span>
                                        </div>
                                        {bodyBlock(reply, 'mt-1')}
                                        <div className="mt-1 flex flex-wrap items-center gap-2">
                                            {reply.is_author && (
                                                <button
                                                    type="button"
                                                    onClick={() => setEditing({ id: reply.id, body: reply.body })}
                                                    className="px-2.5 py-1 rounded-lg text-xs border border-(--color-border) hover:bg-(--color-accent-bg)"
                                                >
                                                    Edit
                                                </button>
                                            )}
                                            <button
                                                type="button"
                                                onClick={() => setReplying({ id: reply.id, text: '' })}
                                                className="px-2.5 py-1 rounded-lg text-xs border border-(--color-border) hover:bg-(--color-accent-bg)"
                                            >
                                                Balas
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => handleDelete(reply)}
                                                className="px-2.5 py-1 rounded-lg text-xs border border-(--color-error) text-(--color-error) hover:bg-(--color-accent-bg)"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                        {replyForm(reply)}
                                    </div>
                                ))}
                            </div>
                        )}

                        {replyForm(comment)}
                    </article>
                ))}
            </div>

            <ConfirmModal
                open={Boolean(confirm)}
                title={confirm?.title}
                message={confirm?.message}
                confirmLabel={confirm?.confirmLabel}
                busy={busy}
                onConfirm={confirm?.action}
                onCancel={() => setConfirm(null)}
            />
            <Toast toast={toast} />
        </div>
    );
}
