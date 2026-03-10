// Navigateur racine — Auth OU Main selon l'état d'authentification
import React, { useEffect } from 'react';
import { NavigationContainer, DefaultTheme, DarkTheme } from '@react-navigation/native';
import { useAuthStore } from '../store/authStore';
import { useSettingsStore } from '../store/settingsStore';
import { useTheme } from '../context/ThemeContext';
import AuthNavigator from './AuthNavigator';
import MainNavigator from './MainNavigator';
import LoadingSpinner from '../components/LoadingSpinner';

const AppNavigator: React.FC = () => {
    const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
    const isLoading = useAuthStore((s) => s.isLoading);
    const checkAuth = useAuthStore((s) => s.checkAuth);
    const loadSettings = useSettingsStore((s) => s.loadSettings);
    const { colors, isDark } = useTheme();

    useEffect(() => {
        checkAuth();
        loadSettings();
    }, []);

    if (isLoading) {
        return <LoadingSpinner message="Chargement..." />;
    }

    // Custom navigation theme based on our palette
    const navigationTheme = {
        ...(isDark ? DarkTheme : DefaultTheme),
        colors: {
            ...(isDark ? DarkTheme.colors : DefaultTheme.colors),
            primary: colors.primary,
            background: colors.background,
            card: colors.surface,
            text: colors.text,
            border: colors.border,
            notification: colors.secondary,
        },
    };

    return (
        <NavigationContainer theme={navigationTheme}>
            {isAuthenticated ? <MainNavigator /> : <AuthNavigator />}
        </NavigationContainer>
    );
};

export default AppNavigator;
