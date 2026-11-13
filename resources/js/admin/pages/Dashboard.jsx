import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { api, endpoints } from '../api';
import StatusBadge from '../components/StatusBadge';

function StatCard({ label, value, accent = false }) {
    return (
        <div className="bg-(--color-surface) border border-(--color-border) rounded-xl p-5">
            <p className="text-xs font-medium uppercase tracking-wider text-(--color-text-muted)">
                {label}
            </p>
            <p className={`text-2xl font-bold mt-2 ${accent ? 'text-(--color-accent)' : ''}`}>
                {value}
            </p>
        </div>
    );
}

export default function Dashboard() {
    const { data, isLoading, error } = useQuery({
        queryKey: ['dashboard'],
        queryFn: async () => (await api.get(endpoints.dashboard)).data,
    });

    if (isLoading) {
        return <div className="text-sm text-(--color-text-muted)">Memuat dashboard...</div>;
    }

    if (error) {
        return (
            <div className="text-sm text-(--color-error)">
                Gagal memuat dashboard. Muat ulang halaman untuk mencoba lagi.
            </div>
        );
    }

    const stats = [
        { label: 'Total Artikel', value: data.total_posts },
        { label: 'Terbit', value: data.published_posts },
        { label: 'Draft', value: data.draft_posts },
        { label: 'Terjadwal', value: data.scheduled_posts },
        { label: 'Total Views', value: Number(data.total_views).toLocaleString('id-ID') },
        { label: 'Komentar Menunggu', value: data.pending_comments, accent: data.pending_comments > 0 },
    ];

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h1 className="text-xl font-bold">Dashboard</h1>
                <Link
                    to="/admin/posts/create"
                    className="px-4 py-2 rounded-lg bg-(--color-accent) text-(--color-on-accent) text-sm font-medium hover:opacity-90 transition-opacity"
                >
                    Tulis Artikel
                </Link>
            </div>

            <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
                {stats.map((stat) => (
                    <StatCard key={stat.label} {...stat} />
                ))}
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <section className="bg-(--color-surface) border border-(--color-border) rounded-xl">
                    <div className="flex items-center justify-between px-5 py-4 border-b border-(--color-border)">
                        <h2 className="font-semibold text-sm">Artikel Terbaru</h2>
                        <Link to="/admin/posts" className="text-xs text-(--color-accent) hover:underline">
                            Lihat semua
                        </Link>
                    </div>
                    <ul className="divide-y divide-(--color-border)">
                        {data.recent_posts.length === 0 && (
                            <li className="px-5 py-4 text-sm text-(--color-text-muted)">Belum ada artikel.</li>
                        )}
                        {data.recent_posts.map((post) => (
                            <li key={post.id} className="px-5 py-3 flex items-center justify-between gap-3">
                                <div className="min-w-0">
                                    <p className="text-sm font-medium truncate">{post.title}</p>
                                    <p className="text-xs text-(--color-text-muted)">
                                        {post.views_count} dilihat
                                    </p>
                                </div>
                                <div className="flex items-center gap-2 flex-shrink-0">
                                    <StatusBadge status={post.status} />
                                    <Link
                                        to={`/admin/posts/${post.id}/edit`}
                                        className="text-xs text-(--color-accent) hover:underline"
                                    >
                                        Edit
                                    </Link>
                                </div>
                            </li>
                        ))}
                    </ul>
                </section>

                <section className="bg-(--color-surface) border border-(--color-border) rounded-xl">
                    <div className="flex items-center justify-between px-5 py-4 border-b border-(--color-border)">
                        <h2 className="font-semibold text-sm">Komentar Terbaru</h2>
                        <Link to="/admin/comments" className="text-xs text-(--color-accent) hover:underline">
                            Lihat semua
                        </Link>
                    </div>
                    <ul className="divide-y divide-(--color-border)">
                        {data.recent_comments.length === 0 && (
                            <li className="px-5 py-4 text-sm text-(--color-text-muted)">Belum ada komentar.</li>
                        )}
                        {data.recent_comments.map((comment) => (
                            <li key={comment.id} className="px-5 py-3">
                                <div className="flex items-center justify-between gap-3 mb-1">
                                    <p className="text-sm font-medium truncate">{comment.name}</p>
                                    <StatusBadge status={comment.status} />
                                </div>
                                <p className="text-xs text-(--color-text-muted) line-clamp-2">{comment.body}</p>
                                {comment.post && (
                                    <p className="text-xs text-(--color-text-muted) mt-1 truncate">
                                        pada: {comment.post.title}
                                    </p>
                                )}
                            </li>
                        ))}
                    </ul>
                </section>
            </div>
        </div>
    );
}
