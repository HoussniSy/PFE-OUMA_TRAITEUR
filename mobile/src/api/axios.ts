// Configuration Axios avec interceptors JWT
import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';

// ⚠️ Adapter cette URL selon votre environnement
// - Émulateur Android : http://10.0.2.2:8001
// - Device physique : http://<IP_LAN>:8001
const API_BASE_URL = 'http://192.168.1.48:8001';

const api = axios.create({
    baseURL: API_BASE_URL,
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
    timeout: 15000,
});

// Intercepteur : ajouter le token JWT à chaque requête
api.interceptors.request.use(
    async (config) => {
        const token = await AsyncStorage.getItem('jwt_token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => Promise.reject(error),
);

// Intercepteur : gérer les erreurs 401 (token expiré)
api.interceptors.response.use(
    (response) => response,
    async (error) => {
        if (error.response?.status === 401) {
            await AsyncStorage.removeItem('jwt_token');
            await AsyncStorage.removeItem('user_data');
        }
        return Promise.reject(error);
    },
);

export default api;
export { API_BASE_URL };
