import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur Stimulus pour le mode sombre
 * Utilise le data-bs-theme natif de Bootstrap 5.3
 * Persiste la préférence dans localStorage
 * DEFAULT: dark mode
 */
export default class extends Controller {
    static targets = ['toggle'];

    connect() {
        // Charger la préférence sauvegardée — dark par défaut
        const savedTheme = localStorage.getItem('ouma-theme');
        const theme = savedTheme || 'dark';
        this.applyTheme(theme);

        // Synchroniser le toggle checkbox
        if (this.hasToggleTarget) {
            this.toggleTarget.checked = theme === 'dark';
        }
    }

    toggle() {
        const isDark = this.toggleTarget.checked;
        const theme = isDark ? 'dark' : 'light';
        this.applyTheme(theme);
        localStorage.setItem('ouma-theme', theme);
    }

    applyTheme(theme) {
        // Bootstrap 5.3 native dark mode
        document.documentElement.setAttribute('data-bs-theme', theme);

        // Smooth transition on body
        document.body.style.transition = 'background-color 0.3s ease, color 0.3s ease';

        // Mettre à jour l'icône du toggle
        const icon = document.querySelector('#darkModeToggle + label i');
        if (icon) {
            if (theme === 'dark') {
                icon.className = 'bi bi-sun-fill';
                icon.style.color = '#F5A623';
            } else {
                icon.className = 'bi bi-moon-fill';
                icon.style.color = 'white';
            }
        }
    }
}
