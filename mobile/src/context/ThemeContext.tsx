// ThemeContext — dark-first theme provider for Ouma Traiteur mobile
import React, { createContext, useContext, useState, useCallback, useMemo } from 'react';
import { COLORS, LIGHT_COLORS, ThemeColors } from '../theme/colors';

interface ThemeContextType {
    colors: ThemeColors;
    isDark: boolean;
    toggleTheme: () => void;
}

const ThemeContext = createContext<ThemeContextType>({
    colors: COLORS,
    isDark: true,
    toggleTheme: () => { },
});

export const ThemeProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const [isDark, setIsDark] = useState(true); // Dark by default

    const toggleTheme = useCallback(() => {
        setIsDark(prev => !prev);
    }, []);

    const value = useMemo(() => ({
        colors: isDark ? COLORS : LIGHT_COLORS,
        isDark,
        toggleTheme,
    }), [isDark, toggleTheme]);

    return (
        <ThemeContext.Provider value={value}>
            {children}
        </ThemeContext.Provider>
    );
};

export const useTheme = () => useContext(ThemeContext);

export default ThemeContext;
