import 'highlight.js/styles/github.css';
import '../css/hljs-dark.css';
import { initThemeSync, toggleTheme } from './theme';

initThemeSync();

document.addEventListener('click', (event) => {
    if (event.target.closest('#theme-toggle, #theme-toggle-404')) {
        toggleTheme();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const content = document.getElementById('article-content');
    if (!content) return;

    const labels = {
        copy: 'Salin',
        copied: 'Tersalin!',
        failed: 'Gagal',
    };

    import('highlight.js/lib/common').then(async ({ default: hljs }) => {
        const { default: apache } = await import('highlight.js/lib/languages/apache');
        hljs.registerLanguage('apache', apache);

        content.querySelectorAll('pre code').forEach((block) => {
            hljs.highlightElement(block);

            const pre = block.parentElement;
            if (!pre || pre.dataset.enhanced) return;
            pre.dataset.enhanced = '1';

            const wrapper = document.createElement('div');
            wrapper.className = 'code-block-wrapper';
            pre.parentNode.insertBefore(wrapper, pre);
            wrapper.appendChild(pre);

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'copy-button';
            button.textContent = labels.copy;
            button.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(block.innerText);
                    button.textContent = labels.copied;
                } catch {
                    button.textContent = labels.failed;
                }
                setTimeout(() => {
                    button.textContent = labels.copy;
                }, 2000);
            });
            wrapper.appendChild(button);
        });
    });
});
