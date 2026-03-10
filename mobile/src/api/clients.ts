// API Clients
import api from './axios';
import { Client, CreateClientData } from '../types/client';
import { PaginatedResponse } from '../types/document';

export const clientsApi = {
    // Liste des clients avec pagination
    getAll: (page = 1, search?: string) => {
        let url = `/api/clients?page=${page}`;
        if (search) url += `&search=${encodeURIComponent(search)}`;
        return api.get<PaginatedResponse<Client>>(url);
    },

    // Détail d'un client
    getOne: (id: number) => api.get<Client>(`/api/clients/${id}`),

    // Créer un client
    create: (data: CreateClientData) => api.post('/api/clients', data),

    // Modifier un client
    update: (id: number, data: Partial<CreateClientData>) =>
        api.put(`/api/clients/${id}`, data),

    // Supprimer un client
    delete: (id: number) => api.delete(`/api/clients/${id}`),
};
