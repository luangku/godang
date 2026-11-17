import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api, endpoints, errorMessage } from '../api';
import CommentAvatar from './CommentAvatar';
import StatusBadge from './StatusBadge';
import ConfirmModal from './ConfirmModal';
import { Toast, useToast } from './Toast';

function formatDate(date) {
    try {
        return new Date(date).toLocaleString('id-ID');
    } catch {
        return date;
    }
}

function CommentActions({ comment, onApprove, onSpam, onEdit, onReply, onDelete }) {
    return (
        <div className="flex flex-wrap items-center gap-2 text-xs">
            {comment.status === 'pending' && (
                <button
                    type="button"
                    onClick={() => onApprove(comment)}
                    className="px-2.5 py-1 rounded-lg border border-(--color-success) text-(--color-success) hover:bg-(--color-accent-bg)"
                >
                    Setujui
                </button>
            )}
            {!comment.is_author && comment.status !== 'spam' && (
                <button
                    type="button"
                    onClick={() => onSpam(comment)}
                    className="px-2.5 py-1 rounded-lg border border-(--color-warning) text-(--color-warning) hover:bg-(--color-accent-bg)"
                >
                    Spam
                </button>
            )}
            <button
                type="button"
                onClick={() => onEdit(comment)}
                className="px-2.5 py-1 rounded-lg border border-(--color-border) hover:bg-(--color-accent-bg)"
            >
                Edit
            </button>
            <button
                type="button"
                onClick={() => onReply(comment)}
                className="px-2.5 py-1 rounded-lg border border-(--color-border) hover:bg-(--color-accent-bg)"
            >
                Balas
            </button>
            <button
                type="button"
                onClick={() => onDelete(comment)}
                className="px-2.5 py-1 rounded-lg border border-(--color-error) text-(--color-error) hover:bg-(--color-accent-bg)"
            >
                Hapus
            </button>
        </div>
    );
}

function ReplyForm({ comment, replying, setReplying, onSubmit, pending }) {
    if (replying.id !== comment.id) return null;

    return (
        <div className="space-y-2">
            <textarea
                rows="3"
                value={replying.body}
                onChange={(event) => setReplying({ id: comment.id, body: event.target.value })}
                placeholder="Tulis balasan..."
                className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm resize-y"
            />
            <div className="flex justify-end gap-2">
                <button
                    type="button"
                    onClick={() => setReplying({ id: null, body: '' })}
                    className="px-2.5 py-1 rounded-lg text-xs border border-(--color-border) hover:bg-(--color-accent-bg)"
                >
                    Batal
                </button>
                <button
                    type="button"
                    onClick={() => onSubmit(comment)}
                    disabled={pending || !replying.body.trim()}
                    className="px-2.5 py-1 rounded-lg text-xs bg-(--color-accent) text-(--color-on-accent) hover:opacity-90 disabled:opacity-50"
                >
                    Kirim Balasan
                </button>
            </div>
        </div>
    );
}

function CommentBody({ comment }) {
    return (
        <div className="text-sm whitespace-pre-wrap">{comment.body}</div>
    );
}

export default function PostComments({ postId }) {
    const queryClient = useQueryClient();
    const { toast, show } = useToast();
    const [newBody, setNewBody] = useState('');
    const [editing, setEditing] = useState({ id: null, body: '' });
    const [replying, setReplying] = useState({ id: null, body: '' });
    const [confirm, setConfirm] = useState(null);
    const [busy, setBusy] = useState(false);

    const queryKey = ['comments', 'post', postId];

    const { data, isLoading } = useQuery({
        queryKey,
        queryFn: async () =>
            (await api.get(endpoints.comments, { params: { post_id: postId, per_page: 100 } })).data,
        enabled: Boolean(postId),
    });

    const invalidate = () => {
        queryClient.invalidateQueries({ queryKey });
        queryClient.invalidateQueries({ queryKey: ['comments'] });
        queryClient.invalidateQueries({ queryKey: ['dashboard'] });
    };

    const mutation = useMutation({
        mutationFn: async ({ method, url, payload }) => (await api({ method, url, data: payload })).data,
        onSuccess: (resp) => {
            invalidate();
            show(resp.message || 'Berhasil.');
            setNewBody('');
            setEditing({ id: null, body: '' });
            setReplying({ id: null, body: '' });
        },
        onError: (error) => show(errorMessage(error), 'error'),
    });

    const runAction = async (action) => {
        setBusy(true);
        try {
            await action();
            setConfirm(null);
        } catch {
            /* toast handled in mutation */
        } finally {
            setBusy(false);
        }
    };

    const handleCreate = (event) => {
        event.preventDefault();
        if (!newBody.trim()) return;
        mutation.mutate({ method: 'POST', url: endpoints.comments, payload: { post_id: postId, body: newBody } });
    };

    const handleApprove = (comment) => {
        mutation.mutate({ method: 'PATCH', url: `/admin/comments/${comment.id}` });
    };

    const handleSpam = (comment) => {
        mutation.mutate({ method: 'PATCH', url: endpoints.commentSpam(comment.id) });
    };

    const handleUpdate = (comment) => {
        if (!editing.body.trim()) return;
        mutation.mutate({ method: 'PUT', url: `/admin/comments/${comment.id}`, payload: { body: editing.body } });
    };

    const handleReply = (comment) => {
        if (!replying.body.trim()) return;
        mutation.mutate({ method: 'POST', url: endpoints.commentReply(comment.id), payload: { body: replying.body } });
    };

    const handleDelete = (comment) => {
        setConfirm({
            title: 'Hapus komentar',
            message: comment.parent_id
                ? 'Hapus balasan ini?'
                : 'Hapus komentar ini? Termasuk seluruh balasannya.',
            confirmLabel: 'Hapus',
            action: () =>
                runAction(async () => {
                    await mutation.mutateAsync({ method: 'DELETE', url: `/admin/comments/${comment.id}` });
                }),
        });
    };

    const comments = data?.data ?? [];
    const total = comments.reduce((sum, c) => sum + 1 + (c.replies?.length ?? 0), 0);

    return (
        <div className="bg-(--color-surface) border border-(--color-border) rounded-xl p-5 space-y-4">
            <div className="flex items-center justify-between">
                <h3 className="font-semibold text-sm">Komentar</h3>
                <span className="text-xs text-(--color-text-muted)">{total}</span>
            </div>

            <form onSubmit={handleCreate} className="space-y-2">
                <textarea
                    rows="2"
                    value={newBody}
                    onChange={(event) => setNewBody(event.target.value)}
                    placeholder="Tulis komentar sebagai admin..."
                    className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm resize-y"
                />
                <button
                    type="submit"
                    disabled={mutation.isPending || !newBody.trim()}
                    className="w-full px-3 py-1.5 rounded-lg text-xs bg-(--color-accent) text-(--color-on-accent) font-medium hover:opacity-90 disabled:opacity-50"
                >
                    Tambah Komentar
                </button>
            </form>

            <div className="space-y-3">
                {isLoading && <p className="text-xs text-(--color-text-muted)">Memuat...</p>}
                {!isLoading && comments.length === 0 && (
                    <p className="text-xs text-(--color-text-muted)">Belum ada komentar di artikel ini.</p>
                )}

                {comments.map((comment) => (
                    <div key={comment.id} className="border border-(--color-border) rounded-lg p-3 space-y-2">
                        <div className="flex items-center justify-between gap-2">
                            <div className="flex items-center gap-2 min-w-0">
                                <CommentAvatar comment={comment} size="sm" />
                                <span className="text-xs font-semibold truncate">{comment.name}</span>
                                <StatusBadge status={comment.status} />
                            </div>
                            <span className="text-[11px] text-(--color-text-muted) shrink-0">
                                {formatDate(comment.created_at)}
                            </span>
                        </div>

                        {editing.id === comment.id ? (
                            <div className="space-y-2">
                                <textarea
                                    rows="3"
                                    value={editing.body}
                                    onChange={(event) => setEditing({ id: comment.id, body: event.target.value })}
                                    className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm resize-y"
                                />
                                <div className="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        onClick={() => setEditing({ id: null, body: '' })}
                                        className="px-2.5 py-1 rounded-lg text-xs border border-(--color-border) hover:bg-(--color-accent-bg)"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => handleUpdate(comment)}
                                        className="px-2.5 py-1 rounded-lg text-xs bg-(--color-accent) text-(--color-on-accent) hover:opacity-90"
                                    >
                                        Simpan
                                    </button>
                                </div>
                            </div>
                        ) : (
                            <CommentBody comment={comment} />
                        )}

                        <CommentActions
                            comment={comment}
                            onApprove={handleApprove}
                            onSpam={handleSpam}
                            onEdit={(c) => setEditing({ id: c.id, body: c.body })}
                            onReply={(c) => setReplying({ id: c.id, body: '' })}
                            onDelete={handleDelete}
                        />

                        <ReplyForm
                            comment={comment}
                            replying={replying}
                            setReplying={setReplying}
                            onSubmit={handleReply}
                            pending={mutation.isPending}
                        />

                        {comment.replies?.length > 0 && (
                            <div className="space-y-3 ml-3 pl-3 border-l-2 border-(--color-accent)/20">
                                {comment.replies.map((reply) => (
                                    <div key={reply.id} className="space-y-2">
                                        <div className="flex items-center justify-between gap-2">
                                            <div className="flex items-center gap-2 min-w-0">
                                                <CommentAvatar comment={reply} size="sm" />
                                                <span className="text-xs font-semibold truncate">{reply.name}</span>
                                                <StatusBadge status={reply.status} />
                                                {reply.is_author && (
                                                    <span className="text-[10px] px-1.5 py-0.5 rounded bg-(--color-accent) text-(--color-on-accent)">
                                                        Admin
                                                    </span>
                                                )}
                                                {reply.reply_to_name && (
                                                    <span className="text-[11px] text-(--color-text-muted) truncate">
                                                        membalas ke @{reply.reply_to_name}
                                                    </span>
                                                )}
                                            </div>
                                            <span className="text-[11px] text-(--color-text-muted) shrink-0">
                                                {formatDate(reply.created_at)}
                                            </span>
                                        </div>

                                        {editing.id === reply.id ? (
                                            <div className="space-y-2">
                                                <textarea
                                                    rows="3"
                                                    value={editing.body}
                                                    onChange={(event) => setEditing({ id: reply.id, body: event.target.value })}
                                                    className="w-full px-3 py-2 rounded-lg bg-(--color-bg) border border-(--color-border) text-sm resize-y"
                                                />
                                                <div className="flex justify-end gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={() => setEditing({ id: null, body: '' })}
                                                        className="px-2.5 py-1 rounded-lg text-xs border border-(--color-border) hover:bg-(--color-accent-bg)"
                                                    >
                                                        Batal
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleUpdate(reply)}
                                                        className="px-2.5 py-1 rounded-lg text-xs bg-(--color-accent) text-(--color-on-accent) hover:opacity-90"
                                                    >
                                                        Simpan
                                                    </button>
                                                </div>
                                            </div>
                                        ) : (
                                            <CommentBody comment={reply} />
                                        )}

                                        <CommentActions
                                            comment={reply}
                                            onApprove={handleApprove}
                                            onSpam={handleSpam}
                                            onEdit={(c) => setEditing({ id: c.id, body: c.body })}
                                            onReply={(c) => setReplying({ id: c.id, body: '' })}
                                            onDelete={handleDelete}
                                        />

                                        <ReplyForm
                                            comment={reply}
                                            replying={replying}
                                            setReplying={setReplying}
                                            onSubmit={handleReply}
                                            pending={mutation.isPending}
                                        />
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
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
