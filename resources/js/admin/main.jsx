import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import 'highlight.js/styles/github.css';
import '../../css/hljs-dark.css';
import { initThemeSync } from '../theme';
import App from './App';

initThemeSync();

const el = document.getElementById('admin-root');

if (el) {
    createRoot(el).render(
        <StrictMode>
            <App />
        </StrictMode>,
    );
}
