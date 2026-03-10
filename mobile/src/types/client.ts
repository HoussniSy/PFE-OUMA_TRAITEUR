// Types pour les clients

export interface Client {
    id: number;
    name: string;
    email?: string;
    phone?: string;
    address?: string;
    createdAt: string;
    documentsCount?: number;
}

export interface CreateClientData {
    name: string;
    email?: string;
    phone?: string;
    address?: string;
}
