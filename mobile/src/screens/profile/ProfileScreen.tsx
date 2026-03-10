// Écran de profil utilisateur
import React from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, Alert } from 'react-native';
import Icon from '@expo/vector-icons/MaterialCommunityIcons';
import { COLORS } from '../../theme/colors';
import { SPACING, BORDER_RADIUS, SHADOWS } from '../../theme/spacing';
import { useAuthStore } from '../../store/authStore';

const ProfileScreen: React.FC<{ navigation: any }> = ({ navigation }) => {
    const user = useAuthStore((s) => s.user);
    const logout = useAuthStore((s) => s.logout);

    const handleLogout = () => {
        Alert.alert(
            'Déconnexion',
            'Voulez-vous vraiment vous déconnecter ?',
            [
                { text: 'Annuler', style: 'cancel' },
                { text: 'Déconnecter', style: 'destructive', onPress: logout },
            ],
        );
    };

    const initials = user
        ? ((user.prenom?.[0] || '') + (user.nom?.[0] || '')).toUpperCase() || user.email[0].toUpperCase()
        : '?';

    const roleName = user?.roles?.includes('ROLE_ADMIN')
        ? 'Administrateur'
        : user?.roles?.includes('ROLE_COMPTABLE')
            ? 'Comptable'
            : 'Utilisateur';

    return (
        <ScrollView style={styles.container}>
            {/* En-tête profil */}
            <View style={styles.headerCard}>
                <View style={styles.avatar}>
                    <Text style={styles.avatarText}>{initials}</Text>
                </View>
                <Text style={styles.userName}>
                    {user?.prenom && user?.nom ? `${user.prenom} ${user.nom}` : user?.email}
                </Text>
                <View style={styles.roleBadge}>
                    <Text style={styles.roleText}>{roleName}</Text>
                </View>
            </View>

            {/* Informations */}
            <View style={styles.section}>
                <Text style={styles.sectionTitle}>Informations</Text>

                <View style={styles.infoRow}>
                    <Icon name="email-outline" size={20} color={COLORS.textSecondary} />
                    <View style={styles.infoContent}>
                        <Text style={styles.infoLabel}>Email</Text>
                        <Text style={styles.infoValue}>{user?.email || '-'}</Text>
                    </View>
                </View>

                {user?.phone && (
                    <View style={styles.infoRow}>
                        <Icon name="phone-outline" size={20} color={COLORS.textSecondary} />
                        <View style={styles.infoContent}>
                            <Text style={styles.infoLabel}>Téléphone</Text>
                            <Text style={styles.infoValue}>{user.phone}</Text>
                        </View>
                    </View>
                )}

                {user?.poste && (
                    <View style={styles.infoRow}>
                        <Icon name="briefcase-outline" size={20} color={COLORS.textSecondary} />
                        <View style={styles.infoContent}>
                            <Text style={styles.infoLabel}>Poste</Text>
                            <Text style={styles.infoValue}>{user.poste}</Text>
                        </View>
                    </View>
                )}

                {user?.company && (
                    <View style={styles.infoRow}>
                        <Icon name="domain" size={20} color={COLORS.textSecondary} />
                        <View style={styles.infoContent}>
                            <Text style={styles.infoLabel}>Entreprise</Text>
                            <Text style={styles.infoValue}>{user.company.name}</Text>
                        </View>
                    </View>
                )}
            </View>

            {/* Menu */}
            <View style={styles.section}>
                <TouchableOpacity style={styles.menuItem} onPress={() => navigation.navigate('Settings')}>
                    <Icon name="cog-outline" size={22} color={COLORS.text} />
                    <Text style={styles.menuText}>Paramètres</Text>
                    <Icon name="chevron-right" size={20} color={COLORS.textLight} />
                </TouchableOpacity>
            </View>

            {/* Déconnexion */}
            <TouchableOpacity style={styles.logoutBtn} onPress={handleLogout}>
                <Icon name="logout" size={20} color={COLORS.error} />
                <Text style={styles.logoutText}>Se déconnecter</Text>
            </TouchableOpacity>

            <Text style={styles.version}>Ouma Traiteur Mobile v1.0</Text>
        </ScrollView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: COLORS.background },
    headerCard: {
        backgroundColor: COLORS.card, padding: SPACING.lg, margin: SPACING.md,
        borderRadius: BORDER_RADIUS.md, alignItems: 'center', ...SHADOWS.small,
    },
    avatar: {
        width: 72, height: 72, borderRadius: 36, backgroundColor: COLORS.primaryBg,
        alignItems: 'center', justifyContent: 'center', marginBottom: SPACING.md,
    },
    avatarText: { fontSize: 28, fontWeight: '700', color: COLORS.primary },
    userName: { fontSize: 20, fontWeight: '700', color: COLORS.text },
    roleBadge: {
        backgroundColor: COLORS.primaryBg, paddingHorizontal: SPACING.md,
        paddingVertical: 4, borderRadius: BORDER_RADIUS.round, marginTop: SPACING.xs,
    },
    roleText: { fontSize: 12, fontWeight: '600', color: COLORS.primary },
    section: {
        backgroundColor: COLORS.card, marginHorizontal: SPACING.md, marginBottom: SPACING.sm,
        borderRadius: BORDER_RADIUS.md, padding: SPACING.md, ...SHADOWS.small,
    },
    sectionTitle: { fontSize: 16, fontWeight: '700', color: COLORS.text, marginBottom: SPACING.md },
    infoRow: {
        flexDirection: 'row', alignItems: 'center', paddingVertical: SPACING.sm,
        borderBottomWidth: 1, borderBottomColor: COLORS.border, gap: SPACING.md,
    },
    infoContent: { flex: 1 },
    infoLabel: { fontSize: 12, color: COLORS.textSecondary },
    infoValue: { fontSize: 14, fontWeight: '600', color: COLORS.text, marginTop: 1 },
    menuItem: {
        flexDirection: 'row', alignItems: 'center', paddingVertical: SPACING.sm, gap: SPACING.md,
    },
    menuText: { flex: 1, fontSize: 15, fontWeight: '500', color: COLORS.text },
    logoutBtn: {
        flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
        marginHorizontal: SPACING.md, marginTop: SPACING.md, padding: SPACING.md,
        backgroundColor: COLORS.card, borderRadius: BORDER_RADIUS.md, gap: 8,
        borderWidth: 1, borderColor: COLORS.error + '30',
    },
    logoutText: { fontSize: 15, fontWeight: '600', color: COLORS.error },
    version: { textAlign: 'center', fontSize: 12, color: COLORS.textLight, marginVertical: SPACING.lg },
});

export default ProfileScreen;
