// API Documents (devis et factures)
import api from './axios';
import {
    Document,
    DocumentListItem,
    CreateDocumentData,
    PaginatedResponse,
} from '../types/document';

export const documentsApi = {
    // Liste des documents avec pagination et filtres
    getAll: (page = 1, type?: string, status?: string, search?: string) => {
        let url = `/api/documents?page=${page}`;
        if (type) url += `&type=${type}`;
        if (status) url += `&status=${status}`;
        if (search) url += `&search=${encodeURIComponent(search)}`;
        return api.get<PaginatedResponse<DocumentListItem>>(url);
    },

    // Détail d'un document
    getOne: (id: number) => api.get<Document>(`/api/documents/${id}`),

    // Créer un document
    create: (data: CreateDocumentData) =>
        api.post('/api/documents', data),

    // Modifier un document
    update: (id: number, data: Partial<CreateDocumentData>) =>
        api.put(`/api/documents/${id}`, data),

    // Supprimer un document
    delete: (id: number) => api.delete(`/api/documents/${id}`),

    // Télécharger le PDF
    downloadPdf: (id: number) =>
        api.get(`/api/documents/${id}/pdf`, { responseType: 'blob' }),

    // Envoyer par email
    sendByEmail: (id: number, email?: string, message?: string) =>
        api.post(`/api/documents/${id}/send`, { email, message }),

    // Convertir un devis en facture
    convertToInvoice: (id: number) =>
        api.post(`/api/documents/${id}/convert`),
};
