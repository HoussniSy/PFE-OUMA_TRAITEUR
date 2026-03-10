// Store des paramètres avec Zustand
import { create } from 'zustand';
import AsyncStorage from '@react-native-async-storage/async-storage';

interface SettingsStore {
    isDarkMode: boolean;
    language: 'fr' | 'ar';
    apiUrl: string;

    // Actions
    toggleDarkMode: () => void;
    setLanguage: (lang: 'fr' | 'ar') => void;
    setApiUrl: (url: string) => void;
    loadSettings: () => Promise<void>;
}

export const useSettingsStore = create<SettingsStore>((set) => ({
    isDarkMode: false,
    language: 'fr',
    apiUrl: 'http://10.0.2.2:8001',

    toggleDarkMode: async () => {
        set((state) => {
            const newValue = !state.isDarkMode;
            AsyncStorage.setItem('dark_mode', JSON.stringify(newValue));
            return { isDarkMode: newValue };
        });
    },

    setLanguage: async (lang: 'fr' | 'ar') => {
        await AsyncStorage.setItem('language', lang);
        set({ language: lang });
    },

    setApiUrl: async (url: string) => {
        await AsyncStorage.setItem('api_url', url);
        set({ apiUrl: url });
    },

    loadSettings: async () => {
        try {
            const darkMode = await AsyncStorage.getItem('dark_mode');
            const language = await AsyncStorage.getItem('language');
            const apiUrl = await AsyncStorage.getItem('api_url');

            set({
                isDarkMode: darkMode ? JSON.parse(darkMode) : false,
                language: (language as 'fr' | 'ar') || 'fr',
                apiUrl: apiUrl || 'http://10.0.2.2:8001',
            });
        } catch (error) {
            console.error('Erreur chargement paramètres:', error);
        }
    },
}));
