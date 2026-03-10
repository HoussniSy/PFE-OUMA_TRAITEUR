// Couleurs par défaut — seront remplacées par celles de l'API company
export const COLORS = {
    primary: '#00a651',
    primaryDark: '#007a3d',
    primaryLight: '#33b871',
    secondary: '#2c3e50',
    background: '#f5f7fa',
    surface: '#ffffff',
    card: '#ffffff',
    text: '#2c3e50',
    textSecondary: '#7f8c8d',
    textLight: '#bdc3c7',
    border: '#e0e6ed',
    error: '#e74c3c',
    warning: '#f39c12',
    success: '#27ae60',
    info: '#3498db',
    white: '#ffffff',
    black: '#000000',
    overlay: 'rgba(0,0,0,0.5)',
};

export const FONTS = {
    regular: { fontSize: 14, color: COLORS.text },
    medium: { fontSize: 16, fontWeight: '500', color: COLORS.text },
    bold: { fontSize: 16, fontWeight: '700', color: COLORS.text },
    title: { fontSize: 20, fontWeight: '700', color: COLORS.text },
    h1: { fontSize: 28, fontWeight: '800', color: COLORS.text },
    h2: { fontSize: 22, fontWeight: '700', color: COLORS.text },
    h3: { fontSize: 18, fontWeight: '600', color: COLORS.text },
    small: { fontSize: 12, color: COLORS.textSecondary },
    caption: { fontSize: 11, color: COLORS.textLight },
};

export const SPACING = {
    xs: 4,
    sm: 8,
    md: 16,
    lg: 24,
    xl: 32,
    xxl: 48,
};

export const SHADOWS = {
    small: {
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 1 },
        shadowOpacity: 0.08,
        shadowRadius: 4,
        elevation: 2,
    },
    medium: {
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.12,
        shadowRadius: 8,
        elevation: 4,
    },
    large: {
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.15,
        shadowRadius: 12,
        elevation: 8,
    },
};

export const BORDER_RADIUS = {
    sm: 8,
    md: 12,
    lg: 16,
    xl: 20,
    round: 999,
};
