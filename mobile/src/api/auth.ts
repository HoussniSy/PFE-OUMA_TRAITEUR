// API d'authentification
import api from './axios';
import { LoginCredentials, User } from '../types/user';

export const authApi = {
    // Connexion — retourne le token JWT
    login: (credentials: LoginCredentials) =>
        api.post<{ token: string }>('/api/login', credentials),

    // Récupère les informations de l'utilisateur connecté
    getMe: () => api.get<User>('/api/me'),
};
