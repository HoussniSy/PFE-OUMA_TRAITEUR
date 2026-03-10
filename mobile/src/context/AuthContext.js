import React, { createContext, useState, useEffect, useContext } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { authApi } from '../api/apiService';

const AuthContext = createContext(null);

export const AuthProvider = ({ children }) => {
    const [user, setUser] = useState(null);
    const [token, setToken] = useState(null);
    const [isLoading, setIsLoading] = useState(true);

    // Vérifier le token au démarrage
    useEffect(() => {
        checkToken();
    }, []);

    const checkToken = async () => {
        try {
            const storedToken = await AsyncStorage.getItem('jwt_token');
            const storedUser = await AsyncStorage.getItem('user_data');
            if (storedToken) {
                setToken(storedToken);
                if (storedUser) {
                    setUser(JSON.parse(storedUser));
                }
            }
        } catch (error) {
            console.error('Erreur vérification token:', error);
        } finally {
            setIsLoading(false);
        }
    };

    const login = async (email, password) => {
        try {
            const response = await authApi.login(email, password);
            const { token: jwtToken } = response.data;

            await AsyncStorage.setItem('jwt_token', jwtToken);
            await AsyncStorage.setItem(
                'user_data',
                JSON.stringify({ email }),
            );

            setToken(jwtToken);
            setUser({ email });

            return { success: true };
        } catch (error) {
            const message =
                error.response?.data?.message ||
                'Email ou mot de passe incorrect';
            return { success: false, error: message };
        }
    };

    const logout = async () => {
        await AsyncStorage.removeItem('jwt_token');
        await AsyncStorage.removeItem('user_data');
        setToken(null);
        setUser(null);
    };

    return (
        <AuthContext.Provider
            value={{
                user,
                token,
                isLoading,
                isAuthenticated: !!token,
                login,
                logout,
            }}>
            {children}
        </AuthContext.Provider>
    );
};

export const useAuth = () => {
    const context = useContext(AuthContext);
    if (!context) {
        throw new Error('useAuth doit être utilisé dans un AuthProvider');
    }
    return context;
};

export default AuthContext;
