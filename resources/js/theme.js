export const DEFAULT_THEME = 'dark';

const THEME_COLORS = {
    dark: '#0b1026',
    light: '#fafafa',
};

export function getTheme() {
    try {
        return localStorage.getItem('theme') === 'light' ? 'light' : DEFAULT_THEME;
    } catch {
        return DEFAULT_THEME;
    }
}

export function applyTheme(theme) {
    document.documentElement.classList.toggle('dark', theme === 'dark');

    const schemeMeta = document.querySelector('meta[name="color-scheme"]');
    if (schemeMeta) {
        schemeMeta.content = theme;
    }

    const themeColorMeta = document.querySelector('meta[name="theme-color"]');
    if (themeColorMeta) {
        themeColorMeta.content = THEME_COLORS[theme];
    }
}

export function setTheme(theme) {
    try {
        if (theme === DEFAULT_THEME) {
            localStorage.removeItem('theme');
        } else {
            localStorage.setItem('theme', theme);
        }
    } catch {
        // Tema tetap diterapkan walaupun penyimpanan tidak tersedia.
    }

    applyTheme(theme);
}

export function toggleTheme() {
    setTheme(getTheme() === DEFAULT_THEME ? 'light' : DEFAULT_THEME);
}

export function initThemeSync() {
    applyTheme(getTheme());

    window.addEventListener('storage', (event) => {
        if (event.key === 'theme') {
            applyTheme(getTheme());
        }
    });
}
