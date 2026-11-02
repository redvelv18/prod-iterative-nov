import './quantity.js';
import './tag-editor.js';

// Test condition: the marker attribute proves the JS bundle is loaded via the Vite dev server
document.documentElement.dataset.jsLoaded = 'true';

// Auto-dismiss flash messages (progressive enhancement, see .alert[data-auto-dismiss])
document.querySelectorAll('.alert[data-auto-dismiss]').forEach((alert) => {
    setTimeout(() => alert.remove(), 4000);
});

// Dark mode toggle: flips the root theme attribute and remembers the choice
const themeToggle = document.querySelector('[data-theme-toggle]');

if (themeToggle) {
    themeToggle.addEventListener('click', () => {
        const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';

        document.documentElement.dataset.theme = next;

        try {
            localStorage.setItem('theme', next);
        } catch {
            // Private mode may block storage; the choice still applies for this page view
        }
    });
}
