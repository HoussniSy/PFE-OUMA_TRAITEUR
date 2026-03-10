// API Paiements
import api from './axios';
import { Payment, CreatePaymentData, PaginatedResponse } from '../types/document';

export const paymentsApi = {
    // Liste des paiements avec pagination
    getAll: (page = 1) =>
        api.get<PaginatedResponse<Payment>>(`/api/payments?page=${page}`),

    // Détail d'un paiement
    getOne: (id: number) => api.get<Payment>(`/api/payments/${id}`),

    // Créer un paiement
    create: (data: CreatePaymentData) => api.post('/api/payments', data),
};
