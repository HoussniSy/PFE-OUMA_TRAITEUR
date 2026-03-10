// Écran des paramètres
import React from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, Switch } from 'react-native';
import Icon from '@expo/vector-icons/MaterialCommunityIcons';
import { COLORS } from '../../theme/colors';
import { SPACING, BORDER_RADIUS, SHADOWS } from '../../theme/spacing';
import { useSettingsStore } from '../../store/settingsStore';

const SettingsScreen: React.FC = () => {
    const { isDarkMode, language, toggleDarkMode, setLanguage } = useSettingsStore();

    return (
        <ScrollView style={styles.container}>
            {/* Apparence */}
            <View style={styles.section}>
                <Text style={styles.sectionTitle}>Apparence</Text>

                <View style={styles.settingRow}>
                    <View style={styles.settingInfo}>
                        <Icon name="weather-night" size={22} color={COLORS.text} />
                        <Text style={styles.settingLabel}>Mode sombre</Text>
                    </View>
                    <Switch
                        value={isDarkMode}
                        onValueChange={toggleDarkMode}
                        trackColor={{ false: COLORS.border, true: COLORS.primaryLight }}
                        thumbColor={isDarkMode ? COLORS.primary : COLORS.textLight}
                    />
                </View>
            </View>

            {/* Langue */}
            <View style={styles.section}>
                <Text style={styles.sectionTitle}>Langue</Text>

                <TouchableOpacity
                    style={[styles.langBtn, language === 'fr' && styles.langBtnActive]}
                    onPress={() => setLanguage('fr')}
                >
                    <Text style={styles.langFlag}>🇫🇷</Text>
                    <Text style={[styles.langText, language === 'fr' && styles.langTextActive]}>Français</Text>
                    {language === 'fr' && <Icon name="check" size={18} color={COLORS.primary} />}
                </TouchableOpacity>

                <TouchableOpacity
                    style={[styles.langBtn, language === 'ar' && styles.langBtnActive]}
                    onPress={() => setLanguage('ar')}
                >
                    <Text style={styles.langFlag}>🇲🇷</Text>
                    <Text style={[styles.langText, language === 'ar' && styles.langTextActive]}>العربية</Text>
                    {language === 'ar' && <Icon name="check" size={18} color={COLORS.primary} />}
                </TouchableOpacity>
            </View>

            {/* À propos */}
            <View style={styles.section}>
                <Text style={styles.sectionTitle}>À propos</Text>

                <View style={styles.aboutRow}>
                    <Text style={styles.aboutLabel}>Application</Text>
                    <Text style={styles.aboutValue}>Ouma Traiteur Mobile</Text>
                </View>
                <View style={styles.aboutRow}>
                    <Text style={styles.aboutLabel}>Version</Text>
                    <Text style={styles.aboutValue}>1.0.0</Text>
                </View>
                <View style={styles.aboutRow}>
                    <Text style={styles.aboutLabel}>Build</Text>
                    <Text style={styles.aboutValue}>React Native</Text>
                </View>
            </View>
        </ScrollView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: COLORS.background },
    section: {
        backgroundColor: COLORS.card, marginHorizontal: SPACING.md, marginTop: SPACING.md,
        borderRadius: BORDER_RADIUS.md, padding: SPACING.md, ...SHADOWS.small,
    },
    sectionTitle: { fontSize: 16, fontWeight: '700', color: COLORS.text, marginBottom: SPACING.md },
    settingRow: {
        flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
        paddingVertical: SPACING.xs,
    },
    settingInfo: { flexDirection: 'row', alignItems: 'center', gap: SPACING.md },
    settingLabel: { fontSize: 15, fontWeight: '500', color: COLORS.text },
    langBtn: {
        flexDirection: 'row', alignItems: 'center', padding: SPACING.md,
        borderRadius: BORDER_RADIUS.sm, marginBottom: SPACING.xs, gap: SPACING.md,
        backgroundColor: COLORS.background,
    },
    langBtnActive: { backgroundColor: COLORS.primaryBg, borderWidth: 1, borderColor: COLORS.primary + '30' },
    langFlag: { fontSize: 20 },
    langText: { flex: 1, fontSize: 15, color: COLORS.textSecondary },
    langTextActive: { color: COLORS.primary, fontWeight: '600' },
    aboutRow: {
        flexDirection: 'row', justifyContent: 'space-between', paddingVertical: SPACING.xs,
        borderBottomWidth: 1, borderBottomColor: COLORS.border,
    },
    aboutLabel: { fontSize: 14, color: COLORS.textSecondary },
    aboutValue: { fontSize: 14, fontWeight: '600', color: COLORS.text },
});

export default SettingsScreen;
