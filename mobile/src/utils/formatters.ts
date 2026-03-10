// Fonctions utilitaires de formatage
import { COLORS } from '../theme/colors';
import { DocumentStatus, DocumentType } from '../types/document';

/**
 * Formate un montant en devise mauritanienne
 * Ex: 148480 → "148 480 MRU"
 */
export const formatMoney = (
    amount: string | number,
    currency: string = 'MRU',
): string => {
    const num = typeof amount === 'string' ? parseFloat(amount) : amount;
    if (isNaN(num)) return `0 ${currency}`;
    return num.toLocaleString('fr-FR', { maximumFractionDigits: 0 }) + ' ' + currency;
};

/**
 * Formate une date ISO en format français
 * Ex: "2026-01-19" → "19/01/2026"
 */
export const formatDate = (dateStr: string): string => {
    if (!dateStr) return '';
    const parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    return `${parts[2]}/${parts[1]}/${parts[0]}`;
};

/**
 * Formate un numéro de téléphone
 */
export const formatPhone = (phone?: string): string => {
    if (!phone) return '';
    return phone.replace(/(\d{3})(\d{2})(\d{2})(\d{2})(\d{2})/, '+$1 $2 $3 $4 $5');
};

/**
 * Retourne la couleur associée à un statut de document
 */
export const getStatusColor = (status: DocumentStatus): string => {
    const colorMap: Record<DocumentStatus, string> = {
        draft: COLORS.draft,
        sent: COLORS.sent,
        partially_paid: COLORS.partially_paid,
        paid: COLORS.paid,
        cancelled: COLORS.cancelled,
    };
    return colorMap[status] || COLORS.draft;
};

/**
 * Retourne la couleur associée à un type de document
 */
export const getTypeColor = (type: DocumentType): string => {
    return type === 'quote' ? COLORS.quote : COLORS.invoice;
};

/**
 * Retourne le label français d'un type
 */
export const getTypeLabel = (type: DocumentType): string => {
    return type === 'quote' ? 'Devis' : 'Facture';
};

/**
 * Retourne le label français d'un statut
 */
export const getStatusLabel = (status: DocumentStatus): string => {
    const labels: Record<DocumentStatus, string> = {
        draft: 'Brouillon',
        sent: 'Envoyé',
        partially_paid: 'Partiellement payé',
        paid: 'Payé',
        cancelled: 'Annulé',
    };
    return labels[status] || status;
};
