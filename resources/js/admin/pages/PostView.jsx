import { useQuery } from '@tanstack/react-query';
import { Link, useParams } from 'react-router-dom';
import { api } from '../api';
import PostComments from '../components/PostComments';
import PostPreview, { localizedName } from '../components/PostPreview';

export default function PostView() {
    const { id } = useParams();

    const { data: post, isLoading, isError } = useQuery({
        queryKey: ['posts', 'view', id],
        queryFn: async () => (await api.get(`/admin/posts/${id}`)).data,
    });

    if (isLoading) {
        return (
            <div className="bg-(--color-surface) border border-(--color-border) rounded-xl px-4 py-6 text-center text-sm text-(--color-text-muted)">
                Memuat pratinjau...
            </div>
        );
    }

    if (isError || !post) {
        return (
            <div className="bg-(--color-surface) border border-(--color-border) rounded-xl px-4 py-6 text-center space-y-3">
                <p className="text-sm text-(--color-text-muted)">Artikel tidak ditemukan.</p>
                <Link to="/admin/posts" className="text-sm text-(--color-accent) hover:underline">
                    Kembali ke artikel
                </Link>
            </div>
        );
    }

    const isTrashed = Boolean(post.deleted_at);
    const isPublished = post.status === 'published' && !isTrashed;
    const tags = post.tags ?? [];

    return (
        <div className="space-y-4">
            {/* Toolbar */}
            <div className="flex flex-wrap items-center justify-between gap-3">
                <Link
                    to="/admin/posts"
                    className="text-sm text-(--color-text-muted) hover:text-(--color-accent)"
                >
                    &larr; Kembali ke artikel
                </Link>
                <div className="flex items-center gap-2">
                    {isPublished && (
                        <a
                            href={`/blog/${post.slug}`}
                            target="_blank"
                            rel="noreferrer"
                            className="px-3 py-2 rounded-lg border border-(--color-border) text-sm hover:bg-(--color-accent-bg)"
                        >
                            Buka di situs
                        </a>
                    )}
                    <Link
                        to={`/admin/posts/${post.id}/edit`}
                        className="px-4 py-2 rounded-lg bg-(--color-accent) text-(--color-on-accent) text-sm font-medium hover:opacity-90 transition-opacity"
                    >
                        Edit
                    </Link>
                </div>
            </div>

            {/* Preview */}
            <PostPreview
                title={post.title}
                categoryName={localizedName(post.category?.name)}
                status={post.status}
                publishedAt={post.published_at}
                readingTime={post.reading_time}
                viewsCount={post.views_count ?? 0}
                tags={tags.map((tag) => ({
                    id: tag.id,
                    name: localizedName(tag.name),
                }))}
                coverUrl={post.cover_url}
                content={post.content}
                note={isTrashed
                    ? 'Artikel berada di sampah. Pulihkan dari halaman Artikel untuk mengembalikannya.'
                    : post.status !== 'published'
                        ? `Ini pratinjau — artikel ${post.status === 'scheduled' ? 'belum terjadwal tayang' : 'belum terbit'}.`
                        : ''}
                isTrashed={isTrashed}
            >
                {/* Moderasi komentar — di dalam kolom artikel agar Daftar Isi tetap sticky sampai bawah */}
                <div className="mt-16">
                    <PostComments postId={post.id} />
                </div>
            </PostPreview>
        </div>
    );
}
