/** Circular avatar for a comment author, falling back to their initial. */
export default function CommentAvatar({ comment, size = 'md' }) {
    const dimension = size === 'sm' ? 'w-7 h-7' : 'w-9 h-9';
    const textSize = size === 'sm' ? 'text-[10px]' : 'text-xs';
    const avatarUrl = comment?.is_author ? comment.user?.avatar_url : null;

    if (avatarUrl) {
        return (
            <img
                src={avatarUrl}
                alt={comment.user?.avatar_alt || comment.name || 'Avatar'}
                className={`${dimension} rounded-full object-cover border border-(--color-border) flex-shrink-0`}
            />
        );
    }

    return (
        <div
            className={`${dimension} rounded-full bg-gradient-to-br from-(--color-accent-bg) to-(--color-accent)/20 flex items-center justify-center flex-shrink-0`}
        >
            <span className={`${textSize} font-bold text-(--color-accent)`}>
                {(comment?.name || '?').charAt(0).toUpperCase()}
            </span>
        </div>
    );
}
