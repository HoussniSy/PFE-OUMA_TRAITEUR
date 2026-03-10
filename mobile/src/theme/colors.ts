// Palette de couleurs Ouma Traiteur — Dark-first design

export const COLORS = {
    // Couleurs principales
    primary: '#4A90E2',
    primaryDark: '#3A7BC8',
    primaryLight: '#6AABF5',
    primaryBg: 'rgba(74, 144, 226, 0.1)',
    secondary: '#FF6F61',
    accent: '#F5A623',

    // Couleurs neutres (dark mode default)
    background: '#0F1117',
    surface: '#1A1D27',
    card: '#242836',
    border: '#2E3345',

    // Texte
    text: '#E8ECF4',
    textSecondary: '#8B92A5',
    textLight: '#555B6E',
    textInverse: '#0F1117',

    // Statuts
    error: '#E74C3C',
    warning: '#F5A623',
    success: '#27AE60',
    info: '#4A90E2',

    // Documents
    quote: '#4A90E2',
    invoice: '#6C5CE7',

    // Statuts documents
    draft: '#8B92A5',
    sent: '#4A90E2',
    partially_paid: '#F5A623',
    paid: '#27AE60',
    cancelled: '#E74C3C',

    // Base
    white: '#ffffff',
    black: '#000000',
    overlay: 'rgba(0,0,0,0.6)',
    shadow: 'rgba(0,0,0,0.3)',

    // Gradients (comme strings pour LinearGradient)
    gradientStart: '#4A90E2',
    gradientMiddle: '#6C5CE7',
    gradientEnd: '#FF6F61',
} as const;

// Couleurs mode clair
export const LIGHT_COLORS = {
    ...COLORS,
    background: '#F0F2F8',
    surface: '#FFFFFF',
    card: '#FFFFFF',
    border: '#E0E4ED',
    text: '#1A1D27',
    textSecondary: '#6B7280',
    textLight: '#9CA3AF',
    textInverse: '#FFFFFF',
    primaryBg: 'rgba(74, 144, 226, 0.08)',
    shadow: 'rgba(0,0,0,0.08)',
    overlay: 'rgba(0,0,0,0.4)',
} as const;

// Alias backwards-compat
export const DARK_COLORS = COLORS;

export type ThemeColors = {
    [K in keyof typeof COLORS]: string;
};
