import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import 'highlight.js/styles/github.css';
import '../../css/hljs-dark.css';
import App from './App';

const el = document.getElementById('admin-root');

if (el) {
    createRoot(el).render(
        <StrictMode>
            <App />
        </StrictMode>,
    );
}
