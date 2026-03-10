import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur Stimulus pour la recherche globale
 * Barre de recherche AJAX avec dropdown des résultats
 */
export default class extends Controller {
    static targets = ['input', 'results', 'resultsContainer'];

    connect() {
        this.debounceTimer = null;
        this.isOpen = false;

        // Fermer les résultats quand on clique en dehors
        document.addEventListener('click', this.handleClickOutside.bind(this));
        document.addEventListener('keydown', this.handleKeydown.bind(this));
    }

    disconnect() {
        document.removeEventListener('click', this.handleClickOutside.bind(this));
        document.removeEventListener('keydown', this.handleKeydown.bind(this));
    }

    /**
     * Déclenché à chaque saisie dans le champ de recherche
     * Utilise un debounce de 300ms pour éviter trop de requêtes
     */
    search() {
        clearTimeout(this.debounceTimer);

        const query = this.inputTarget.value.trim();

        if (query.length < 2) {
            this.hideResults();
            return;
        }

        this.debounceTimer = setTimeout(() => {
            this.performSearch(query);
        }, 300);
    }

    /**
     * Effectue la recherche AJAX
     */
    async performSearch(query) {
        try {
            const response = await fetch(`/search?q=${encodeURIComponent(query)}`);
            const data = await response.json();
            this.renderResults(data);
        } catch (error) {
            console.error('Erreur de recherche:', error);
        }
    }

    /**
     * Affiche les résultats dans le dropdown
     */
    renderResults(data) {
        const { documents, clients } = data;
        const hasResults = documents.length > 0 || clients.length > 0;

        if (!hasResults) {
            this.resultsTarget.innerHTML = `
                <div class="card-body text-center text-muted py-3">
                    <i class="bi bi-search"></i> Aucun résultat trouvé
                </div>
            `;
            this.showResults();
            return;
        }

        let html = '<div class="list-group list-group-flush">';

        // Section Documents
        if (documents.length > 0) {
            html += `
                <div class="list-group-item bg-light py-1 px-3">
                    <small class="text-muted fw-bold">
                        <i class="bi bi-file-earmark-text"></i> DOCUMENTS
                    </small>
                </div>
            `;
            documents.forEach(doc => {
                html += `
                    <a href="${doc.url}" class="list-group-item list-group-item-action py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge ${doc.statusClass} me-1" style="font-size: 0.65rem;">
                                    ${doc.typeLabel}
                                </span>
                                <strong>${doc.number}</strong>
                                <br>
                                <small class="text-muted">
                                    <i class="bi bi-person"></i> ${doc.client}
                                </small>
                            </div>
                            <div class="text-end">
                                <small class="fw-bold">${doc.totalTtc} MRU</small>
                            </div>
                        </div>
                    </a>
                `;
            });
        }

        // Section Clients
        if (clients.length > 0) {
            html += `
                <div class="list-group-item bg-light py-1 px-3">
                    <small class="text-muted fw-bold">
                        <i class="bi bi-people"></i> CLIENTS
                    </small>
                </div>
            `;
            clients.forEach(client => {
                html += `
                    <a href="${client.url}" class="list-group-item list-group-item-action py-2 px-3">
                        <div>
                            <strong>${client.name}</strong>
                            <br>
                            <small class="text-muted">
                                ${client.phone ? '<i class="bi bi-telephone"></i> ' + client.phone : ''}
                                ${client.email ? ' <i class="bi bi-envelope"></i> ' + client.email : ''}
                            </small>
                        </div>
                    </a>
                `;
            });
        }

        html += '</div>';

        this.resultsTarget.innerHTML = html;
        this.showResults();
    }

    showResults() {
        this.resultsContainerTarget.style.display = 'block';
        this.isOpen = true;
    }

    hideResults() {
        this.resultsContainerTarget.style.display = 'none';
        this.isOpen = false;
    }

    handleClickOutside(event) {
        if (this.isOpen && !this.element.contains(event.target)) {
            this.hideResults();
        }
    }

    handleKeydown(event) {
        if (event.key === 'Escape' && this.isOpen) {
            this.hideResults();
            this.inputTarget.blur();
        }
    }
}
