// Types pour l'utilisateur et l'authentification

export interface Company {
    id: number;
    name: string;
    primaryColor: string;
    logo?: string;
    defaultCurrency: string;
    defaultTaxRate: string;
}

export interface User {
    id: number;
    email: string;
    nom?: string;
    prenom?: string;
    phone?: string;
    poste?: string;
    avatar?: string;
    roles: string[];
    company?: Company;
}

export interface LoginCredentials {
    email: string;
    password: string;
}

export interface AuthState {
    token: string | null;
    user: User | null;
    isAuthenticated: boolean;
    isLoading: boolean;
}
