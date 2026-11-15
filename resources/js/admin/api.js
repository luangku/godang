import axios from 'axios';

export const api = axios.create({
    baseURL: '/api',
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    withXSRFToken: true,
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            window.__adminAuth?.setGuest?.();
        }
        return Promise.reject(error);
    },
);

/** Extract the first validation error message from a failed response. */
export function errorMessage(error, fallback = 'Terjadi kesalahan.') {
    const data = error?.response?.data;
    if (!data) return fallback;
    const errors = data.errors ? Object.values(data.errors).flat() : [];
    return errors[0] || data.message || fallback;
}

/** Admin API endpoints grouped by resource. */
export const endpoints = {
    auth: {
        login: '/auth/login',
        logout: '/auth/logout',
        me: '/auth/me',
        forgotPassword: '/auth/forgot-password',
        resetPassword: '/auth/reset-password',
    },
    site: {
        settings: '/site/settings',
    },
    dashboard: '/admin/dashboard',
    posts: {
        list: '/admin/posts',
        bulkDelete: '/admin/posts/bulk-delete',
        restore: (id) => `/admin/posts/${id}/restore`,
    },
    comments: '/admin/comments',
    commentReply: (id) => `/admin/comments/${id}/reply`,
    commentSpam: (id) => `/admin/comments/${id}/spam`,
    commentBulkDelete: '/admin/comments/bulk-delete',
    categories: '/admin/categories',
    tags: '/admin/tags',
    media: '/admin/media',
    mediaCategories: '/admin/media-categories',
    mediaBulkDelete: '/admin/media/bulk-delete',
    mediaBulkRestore: '/admin/media/bulk-restore',
    mediaBulkForceDelete: '/admin/media/bulk-force-delete',
    mediaRestore: (id) => `/admin/media/${id}/restore`,
    mediaForceDelete: (id) => `/admin/media/${id}/force`,
    users: '/admin/users',
    settings: '/admin/settings',
};
