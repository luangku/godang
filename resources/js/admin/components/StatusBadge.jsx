export const statusLabels = {
    published: { id: 'Terbit', en: 'Published', classes: 'bg-(--color-accent-bg) text-(--color-success)' },
    draft: { id: 'Draft', en: 'Draft', classes: 'bg-(--color-accent-bg) text-(--color-text-muted)' },
    scheduled: { id: 'Terjadwal', en: 'Scheduled', classes: 'bg-(--color-accent-bg) text-(--color-warning)' },
    pending: { id: 'Menunggu', en: 'Pending', classes: 'bg-(--color-accent-bg) text-(--color-warning)' },
    approved: { id: 'Disetujui', en: 'Approved', classes: 'bg-(--color-accent-bg) text-(--color-success)' },
    spam: { id: 'Spam', en: 'Spam', classes: 'bg-(--color-accent-bg) text-(--color-error)' },
    trashed: { id: 'Terhapus', en: 'Trashed', classes: 'bg-(--color-accent-bg) text-(--color-error)' },
};

export default function StatusBadge({ status }) {
    const style = statusLabels[status];
    if (!style) return null;

    return (
        <span className={`inline-block px-2 py-0.5 rounded-md text-xs font-medium ${style.classes}`}>
            {style.id}
        </span>
    );
}
