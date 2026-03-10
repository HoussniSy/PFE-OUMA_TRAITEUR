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
    async config => {
        const token = await AsyncStorage.getItem('jwt_token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    error => Promise.reject(error),
);

// Intercepteur : gérer les erreurs 401 (token expiré)
api.interceptors.response.use(
    response => response,
    async error => {
        if (error.response?.status === 401) {
            await AsyncStorage.removeItem('jwt_token');
            // Le AuthContext détectera la perte du token
        }
        return Promise.reject(error);
    },
);

// ============ AUTH ============
export const authApi = {
    login: (email, password) =>
        api.post('/api/login', { email, password }),
};

// ============ DASHBOARD ============
export const dashboardApi = {
    getStats: () => api.get('/api/dashboard'),
};

// ============ CLIENTS ============
export const clientsApi = {
    getAll: (page = 1) => api.get(`/api/clients?page=${page}`),
    getOne: id => api.get(`/api/clients/${id}`),
    create: data => api.post('/api/clients', data),
    update: (id, data) => api.put(`/api/clients/${id}`, data),
    delete: id => api.delete(`/api/clients/${id}`),
};

// ============ DOCUMENTS ============
export const documentsApi = {
    getAll: (page = 1, type = null) => {
        let url = `/api/documents?page=${page}`;
        if (type) url += `&type=${type}`;
        return api.get(url);
    },
    getOne: id => api.get(`/api/documents/${id}`),
    create: data => api.post('/api/documents', data),
    update: (id, data) => api.put(`/api/documents/${id}`, data),
    delete: id => api.delete(`/api/documents/${id}`),
};

// ============ STOCK ============
export const stockApi = {
    getAll: (page = 1) => api.get(`/api/stock_items?page=${page}`),
    getOne: id => api.get(`/api/stock_items/${id}`),
    create: data => api.post('/api/stock_items', data),
    update: (id, data) => api.put(`/api/stock_items/${id}`, data),
    delete: id => api.delete(`/api/stock_items/${id}`),
};

// ============ COMPANY ============
export const companyApi = {
    getAll: () => api.get('/api/companies'),
    getOne: id => api.get(`/api/companies/${id}`),
};

// ============ REPORTS ============
export const reportsApi = {
    getSummary: (year = null) => {
        let url = '/api/reports/summary';
        if (year) url += `?year=${year}`;
        return api.get(url);
    },
};

// ============ PAYMENTS ============
export const paymentsApi = {
    getAll: (page = 1) => api.get(`/api/payments?page=${page}`),
    getOne: id => api.get(`/api/payments/${id}`),
    create: data => api.post('/api/payments', data),
};

export default api;
