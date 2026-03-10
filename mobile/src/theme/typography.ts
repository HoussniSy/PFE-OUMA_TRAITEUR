// Typographie système — compatible thème

import { TextStyle } from 'react-native';
import { COLORS } from './colors';

export const FONTS: Record<string, TextStyle> = {
    h1: { fontSize: 28, fontWeight: '800', color: COLORS.text, letterSpacing: -0.5 },
    h2: { fontSize: 22, fontWeight: '700', color: COLORS.text, letterSpacing: -0.3 },
    h3: { fontSize: 18, fontWeight: '600', color: COLORS.text },
    title: { fontSize: 20, fontWeight: '700', color: COLORS.text, letterSpacing: -0.3 },
    bold: { fontSize: 16, fontWeight: '700', color: COLORS.text },
    medium: { fontSize: 16, fontWeight: '500', color: COLORS.text },
    regular: { fontSize: 14, color: COLORS.text },
    small: { fontSize: 12, color: COLORS.textSecondary },
    caption: { fontSize: 11, color: COLORS.textLight },
    button: { fontSize: 16, fontWeight: '600', color: COLORS.white, letterSpacing: 0.3 },
};
