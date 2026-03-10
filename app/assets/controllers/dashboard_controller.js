import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur Stimulus pour le dashboard personnalisable
 * Gère le drag & drop des widgets et la sauvegarde des positions
 */
export default class extends Controller {
    static targets = ['widget', 'editButton', 'editPanel'];

    connect() {
        this.editing = false;
        this.draggedElement = null;
    }

    /**
     * Active/désactive le mode édition
     */
    toggleEdit() {
        this.editing = !this.editing;
        const container = this.element;

        if (this.editing) {
            container.classList.add('dashboard-editing');
            // Activer le drag & drop
            this.widgetTargets.forEach(widget => {
                widget.setAttribute('draggable', 'true');
                widget.classList.add('widget-editable');
            });
            // Ajouter les événements
            this.addDragListeners();

            // Afficher le panneau d'édition
            if (this.hasEditPanelTarget) {
                this.editPanelTarget.style.display = 'block';
            }
        } else {
            container.classList.remove('dashboard-editing');
            // Désactiver le drag & drop
            this.widgetTargets.forEach(widget => {
                widget.setAttribute('draggable', 'false');
                widget.classList.remove('widget-editable', 'widget-drag-over');
            });
            // Retirer les événements
            this.removeDragListeners();

            // Cacher le panneau d'édition
            if (this.hasEditPanelTarget) {
                this.editPanelTarget.style.display = 'none';
            }

            // Sauvegarder les positions
            this.savePositions();
        }
    }

    addDragListeners() {
        this.widgetTargets.forEach(widget => {
            widget.addEventListener('dragstart', this.handleDragStart.bind(this));
            widget.addEventListener('dragover', this.handleDragOver.bind(this));
            widget.addEventListener('drop', this.handleDrop.bind(this));
            widget.addEventListener('dragend', this.handleDragEnd.bind(this));
            widget.addEventListener('dragenter', this.handleDragEnter.bind(this));
            widget.addEventListener('dragleave', this.handleDragLeave.bind(this));
        });
    }

    removeDragListeners() {
        this.widgetTargets.forEach(widget => {
            widget.removeEventListener('dragstart', this.handleDragStart);
            widget.removeEventListener('dragover', this.handleDragOver);
            widget.removeEventListener('drop', this.handleDrop);
            widget.removeEventListener('dragend', this.handleDragEnd);
            widget.removeEventListener('dragenter', this.handleDragEnter);
            widget.removeEventListener('dragleave', this.handleDragLeave);
        });
    }

    handleDragStart(e) {
        this.draggedElement = e.currentTarget;
        e.currentTarget.classList.add('widget-dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', e.currentTarget.dataset.widgetType);
    }

    handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    }

    handleDragEnter(e) {
        e.preventDefault();
        if (e.currentTarget !== this.draggedElement) {
            e.currentTarget.classList.add('widget-drag-over');
        }
    }

    handleDragLeave(e) {
        e.currentTarget.classList.remove('widget-drag-over');
    }

    handleDrop(e) {
        e.preventDefault();
        const target = e.currentTarget;
        target.classList.remove('widget-drag-over');

        if (this.draggedElement && this.draggedElement !== target) {
            // Échanger les positions dans le DOM
            const parent = this.draggedElement.parentNode;
            const draggedNext = this.draggedElement.nextElementSibling;

            if (target === draggedNext) {
                // Le dragged est avant le target: insérer le target avant le dragged
                parent.insertBefore(target, this.draggedElement);
            } else {
                // Position relative quelconque
                const targetNext = target.nextElementSibling;
                parent.insertBefore(this.draggedElement, target);
                if (targetNext) {
                    parent.insertBefore(target, draggedNext);
                } else {
                    parent.appendChild(target);
                }
            }
        }
    }

    handleDragEnd(e) {
        e.currentTarget.classList.remove('widget-dragging');
        this.widgetTargets.forEach(widget => {
            widget.classList.remove('widget-drag-over');
        });
        this.draggedElement = null;
    }

    /**
     * Basculer la visibilité d'un widget
     */
    async toggleWidget(e) {
        const type = e.currentTarget.dataset.widgetType;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        try {
            const response = await fetch('/dashboard/widgets/toggle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify({ type }),
            });

            const data = await response.json();
            if (data.success) {
                // Trouver le widget correspondant et le montrer/cacher
                const widget = this.widgetTargets.find(
                    w => w.dataset.widgetType === type
                );
                if (widget) {
                    if (data.visible) {
                        widget.style.display = '';
                        widget.classList.remove('widget-hidden');
                    } else {
                        widget.style.display = 'none';
                        widget.classList.add('widget-hidden');
                    }
                }
                // Mettre à jour le checkbox
                e.currentTarget.checked = data.visible;
            }
        } catch (error) {
            console.error('Erreur lors du toggle du widget:', error);
        }
    }

    /**
     * Sauvegarde les positions actuelles des widgets via AJAX
     */
    async savePositions() {
        const widgets = this.widgetTargets.map((widget, index) => ({
            type: widget.dataset.widgetType,
            position: index,
            visible: !widget.classList.contains('widget-hidden'),
        }));

        try {
            const response = await fetch('/dashboard/widgets/save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ widgets }),
            });

            const data = await response.json();
            if (data.success) {
                // Optionnel: afficher un feedback
                this.showNotification('Disposition sauvegardée !');
            }
        } catch (error) {
            console.error('Erreur lors de la sauvegarde:', error);
        }
    }

    /**
     * Affiche une notification temporaire
     */
    showNotification(message) {
        const notif = document.createElement('div');
        notif.className = 'alert alert-success alert-dismissible fade show position-fixed';
        notif.style.cssText = 'bottom: 20px; right: 20px; z-index: 9999; min-width: 250px;';
        notif.innerHTML = `
            <i class="bi bi-check-circle"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(notif);

        setTimeout(() => {
            notif.remove();
        }, 3000);
    }
}
