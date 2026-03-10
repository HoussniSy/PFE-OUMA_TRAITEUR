// Store d'authentification avec Zustand
import { create } from 'zustand';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { User } from '../types/user';
import { authApi } from '../api/auth';

interface AuthStore {
    token: string | null;
    user: User | null;
    isLoading: boolean;
    isAuthenticated: boolean;

    // Actions
    login: (email: string, password: string) => Promise<{ success: boolean; error?: string }>;
    logout: () => Promise<void>;
    checkAuth: () => Promise<void>;
    fetchUser: () => Promise<void>;
}

export const useAuthStore = create<AuthStore>((set, get) => ({
    token: null,
    user: null,
    isLoading: true,
    isAuthenticated: false,

    // Connexion
    login: async (email: string, password: string) => {
        try {
            const response = await authApi.login({ email, password });
            const { token } = response.data;

            await AsyncStorage.setItem('jwt_token', token);
            set({ token, isAuthenticated: true });

            // Récupérer les infos utilisateur
            await get().fetchUser();

            return { success: true };
        } catch (error: any) {
            const message =
                error.response?.data?.message || 'Email ou mot de passe incorrect';
            return { success: false, error: message };
        }
    },

    // Déconnexion
    logout: async () => {
        await AsyncStorage.removeItem('jwt_token');
        await AsyncStorage.removeItem('user_data');
        set({ token: null, user: null, isAuthenticated: false });
    },

    // Vérifier l'authentification au démarrage
    checkAuth: async () => {
        try {
            const storedToken = await AsyncStorage.getItem('jwt_token');
            if (storedToken) {
                set({ token: storedToken, isAuthenticated: true });

                // Essayer de récupérer les données utilisateur
                const storedUser = await AsyncStorage.getItem('user_data');
                if (storedUser) {
                    set({ user: JSON.parse(storedUser) });
                }

                // Rafraîchir les données utilisateur depuis l'API
                await get().fetchUser();
            }
        } catch (error) {
            console.error('Erreur vérification auth:', error);
        } finally {
            set({ isLoading: false });
        }
    },

    // Récupérer les infos utilisateur depuis /api/me
    fetchUser: async () => {
        try {
            const response = await authApi.getMe();
            const user = response.data;
            await AsyncStorage.setItem('user_data', JSON.stringify(user));
            set({ user });
        } catch (error) {
            console.error('Erreur récupération utilisateur:', error);
        }
    },
}));
