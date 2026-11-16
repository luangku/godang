import { useEffect, useState } from 'react';
import { NavLink, Outlet, useNavigate } from 'react-router-dom';
import { initialSiteData, resolveSiteLogo } from '../site';
import { useAuth } from '../stores/auth';

const navItems = [
    {
        to: '/admin',
        label: 'Dashboard',
        icon: 'M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4m-4-5h6m-6 4h6m4-9v10a1 1 0 01-1 1h-4m4-5h-6m6 4H9',
        end: true,
    },
    { to: '/admin/posts', label: 'Artikel', icon: 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z' },
    { to: '/admin/comments', label: 'Komentar', icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' },
    { to: '/admin/categories', label: 'Kategori', icon: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z' },
    { to: '/admin/tags', label: 'Tag', icon: 'M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z M6 6h.008v.008H6V6z' },
    { to: '/admin/media', label: 'Media', icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { to: '/admin/users', label: 'Pengguna', primaryOnly: true, icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
    { to: '/admin/profile', label: 'Profil', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
    { to: '/admin/settings', label: 'Pengaturan', primaryOnly: true, icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z' },
];

export default function Shell() {
    const [open, setOpen] = useState(false);
    const [isDark, setIsDark] = useState(() => document.documentElement.classList.contains('dark'));
    const user = useAuth((state) => state.user);
    const logout = useAuth((state) => state.logout);
    const navigate = useNavigate();
    const [siteData] = useState(initialSiteData);
    const adminName = user?.name || user?.email || siteData.author_name;
    const visibleNavItems = navItems.filter((item) => !item.primaryOnly || user?.is_primary);
    const logoLight = siteData.site_logo || siteData.site_logo_dark;
    const logoDark = siteData.site_logo_dark || siteData.site_logo;

    useEffect(() => {
        const close = () => setOpen(false);
        window.addEventListener('resize', close);
        return () => window.removeEventListener('resize', close);
    }, []);

    const toggleTheme = () => {
        const nowDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', nowDark ? 'dark' : 'light');
        setIsDark(nowDark);
    };

    const handleLogout = async () => {
        await logout();
        navigate('/admin/login', { replace: true });
    };

    return (
        <div className="min-h-screen">
            <button
                type="button"
                className="fixed top-3 left-3 z-30 lg:hidden p-2 rounded-lg bg-(--color-surface) border border-(--color-border)"
                onClick={() => setOpen((value) => !value)}
                aria-label="Toggle menu"
            >
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            {open && (
                <div
                    className="fixed inset-0 bg-black/40 z-20 lg:hidden"
                    onClick={() => setOpen(false)}
                    aria-hidden="true"
                />
            )}

            <aside
                className={`admin-sidebar fixed top-0 left-0 z-30 flex flex-col bg-(--color-surface) border-r border-(--color-border) ${open ? 'open' : ''}`}
            >
                <div className="px-5 py-5 border-b border-(--color-border) flex items-center justify-center">
                    {logoLight ? (
                        <>
                            <img
                                src={resolveSiteLogo(logoLight)}
                                alt={siteData.site_logo_alt || siteData.site_title || 'Logo'}
                                className="h-14 w-auto max-w-[200px] object-contain dark:hidden"
                            />
                            <img
                                src={resolveSiteLogo(logoDark)}
                                alt={siteData.site_logo_alt || siteData.site_title || 'Logo'}
                                className="h-14 w-auto max-w-[200px] object-contain hidden dark:block"
                            />
                        </>
                    ) : (
                        <div className="h-14 w-14 rounded-xl bg-(--color-accent) text-(--color-on-accent) flex items-center justify-center font-bold text-xl">
                            {(siteData.site_title || 'Admin CMS').charAt(0).toUpperCase()}
                        </div>
                    )}
                </div>

                <nav className="flex-1 py-4 px-3 space-y-1 overflow-y-auto">
                    {visibleNavItems.map((item) => (
                        <NavLink
                            key={item.to}
                            to={item.to}
                            end={item.end}
                            onClick={() => setOpen(false)}
                            className={({ isActive }) =>
                                `flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors ${
                                    isActive
                                        ? 'bg-(--color-accent-bg) text-(--color-accent) font-medium'
                                        : 'text-(--color-text-muted) hover:bg-(--color-accent-bg) hover:text-(--color-text)'
                                }`
                            }
                        >
                            <svg className="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d={item.icon} />
                            </svg>
                            {item.label}
                        </NavLink>
                    ))}
                </nav>

                <div className="px-5 py-4 border-t border-(--color-border) flex items-center justify-between gap-2">
                    <div className="min-w-0">
                        <p className="text-xs text-(--color-text-muted) truncate" title={adminName}>
                            {adminName}
                        </p>
                        <p className="text-[10px] text-(--color-text-muted) truncate">
                            {user?.is_primary ? 'Admin Utama' : 'Admin'}
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={handleLogout}
                        className="flex-shrink-0 px-2.5 py-1 rounded-lg text-xs border border-(--color-border) hover:bg-(--color-accent-bg) hover:text-(--color-accent) transition-colors"
                    >
                        Keluar
                    </button>
                </div>
            </aside>

            <div className="lg:pl-[260px]">
                <header className="sticky top-0 z-10 flex items-center justify-end gap-3 px-4 sm:px-6 py-3 bg-(--color-bg)/90 backdrop-blur border-b border-(--color-border)">
                    <a
                        href="/"
                        id="admin-home-link"
                        title="Buka halaman utama"
                        className="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-(--color-accent)/40 bg-(--color-accent-bg) text-(--color-accent) text-xs font-semibold hover:bg-(--color-accent) hover:text-(--color-on-accent) hover:border-(--color-accent) transition-all duration-200"
                    >
                        <span className="w-2 h-2 rounded-full bg-current animate-pulse" aria-hidden="true" />
                        Home
                    </a>
                    <button
                        type="button"
                        onClick={toggleTheme}
                        className="p-2 rounded-lg hover:bg-(--color-accent-bg) transition-colors"
                        aria-label="Toggle dark mode"
                    >
                        {isDark ? (
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        ) : (
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        )}
                    </button>
                </header>

                <main className="px-4 sm:px-6 py-6">
                    <Outlet />
                </main>
            </div>
        </div>
    );
}
