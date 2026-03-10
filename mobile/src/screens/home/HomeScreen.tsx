// Écran d'accueil — Dashboard
import React, { useEffect, useState } from 'react';
import {
    View, Text, StyleSheet, ScrollView, TouchableOpacity,
    RefreshControl, ActivityIndicator,
} from 'react-native';
import Icon from '@expo/vector-icons/MaterialCommunityIcons';
import { COLORS } from '../../theme/colors';
import { SPACING, BORDER_RADIUS, SHADOWS } from '../../theme/spacing';
import StatCard from '../../components/StatCard';
import DocumentCard from '../../components/DocumentCard';
import { documentsApi } from '../../api/documents';
import { clientsApi } from '../../api/clients';
import { useAuthStore } from '../../store/authStore';
import { formatMoney } from '../../utils/formatters';
import { DocumentListItem } from '../../types/document';

const HomeScreen: React.FC<{ navigation: any }> = ({ navigation }) => {
    const user = useAuthStore((s) => s.user);
    const [isLoading, setIsLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [stats, setStats] = useState({
        quotes: 0,
        invoices: 0,
        revenue: 0,
        clients: 0,
    });
    const [recentDocs, setRecentDocs] = useState<DocumentListItem[]>([]);

    const loadData = async () => {
        try {
            const [quotesRes, invoicesRes, clientsRes] = await Promise.all([
                documentsApi.getAll(1, 'quote'),
                documentsApi.getAll(1, 'invoice'),
                clientsApi.getAll(1),
            ]);

            // Calculer le CA (somme des factures)
            const invoiceList = invoicesRes.data.data || [];
            const revenue = invoiceList.reduce(
                (sum: number, d: DocumentListItem) => sum + parseFloat(d.totalTtc || '0'),
                0,
            );

            setStats({
                quotes: quotesRes.data.total || 0,
                invoices: invoicesRes.data.total || 0,
                revenue,
                clients: clientsRes.data.total || 0,
            });

            // Derniers documents (5)
            const allDocsRes = await documentsApi.getAll(1);
            setRecentDocs((allDocsRes.data.data || []).slice(0, 5));
        } catch (error) {
            console.error('Erreur chargement dashboard:', error);
        } finally {
            setIsLoading(false);
            setRefreshing(false);
        }
    };

    useEffect(() => {
        loadData();
    }, []);

    const onRefresh = () => {
        setRefreshing(true);
        loadData();
    };

    if (isLoading) {
        return (
            <View style={styles.loadingContainer}>
                <ActivityIndicator size="large" color={COLORS.primary} />
            </View>
        );
    }

    return (
        <ScrollView
            style={styles.container}
            refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={[COLORS.primary]} />}
        >
            {/* En-tête */}
            <View style={styles.header}>
                <View>
                    <Text style={styles.greeting}>Bonjour,</Text>
                    <Text style={styles.userName}>
                        {user?.prenom || user?.nom || user?.email || 'Utilisateur'}
                    </Text>
                </View>
                <TouchableOpacity
                    style={styles.profileButton}
                    onPress={() => navigation.navigate('Profile')}
                >
                    <Icon name="account-circle-outline" size={32} color={COLORS.primary} />
                </TouchableOpacity>
            </View>

            {/* Statistiques */}
            <View style={styles.statsGrid}>
                <StatCard title="Devis" value={String(stats.quotes)} icon="file-document-outline" color={COLORS.quote} />
                <StatCard title="Factures" value={String(stats.invoices)} icon="receipt" color={COLORS.invoice} />
                <StatCard title="CA Total" value={formatMoney(stats.revenue)} icon="chart-line" color={COLORS.primary} />
                <StatCard title="Clients" value={String(stats.clients)} icon="account-group" color="#8e44ad" />
            </View>

            {/* Boutons rapides */}
            <View style={styles.quickActions}>
                <TouchableOpacity
                    style={styles.quickActionBtn}
                    onPress={() => navigation.navigate('DocumentForm', { type: 'quote' })}
                >
                    <View style={[styles.quickActionIcon, { backgroundColor: COLORS.quote + '15' }]}>
                        <Icon name="plus" size={20} color={COLORS.quote} />
                    </View>
                    <Text style={styles.quickActionText}>Nouveau devis</Text>
                </TouchableOpacity>

                <TouchableOpacity
                    style={styles.quickActionBtn}
                    onPress={() => navigation.navigate('DocumentForm', { type: 'invoice' })}
                >
                    <View style={[styles.quickActionIcon, { backgroundColor: COLORS.invoice + '15' }]}>
                        <Icon name="plus" size={20} color={COLORS.invoice} />
                    </View>
                    <Text style={styles.quickActionText}>Nouvelle facture</Text>
                </TouchableOpacity>

                <TouchableOpacity
                    style={styles.quickActionBtn}
                    onPress={() => navigation.navigate('ClientForm')}
                >
                    <View style={[styles.quickActionIcon, { backgroundColor: '#8e44ad15' }]}>
                        <Icon name="account-plus-outline" size={20} color="#8e44ad" />
                    </View>
                    <Text style={styles.quickActionText}>Nouveau client</Text>
                </TouchableOpacity>
            </View>

            {/* Documents récents */}
            <View style={styles.section}>
                <View style={styles.sectionHeader}>
                    <Text style={styles.sectionTitle}>Documents récents</Text>
                    <TouchableOpacity onPress={() => navigation.navigate('Documents')}>
                        <Text style={styles.seeAll}>Voir tout</Text>
                    </TouchableOpacity>
                </View>

                {recentDocs.map((doc) => (
                    <DocumentCard
                        key={doc.id}
                        document={doc}
                        onPress={() => navigation.navigate('DocumentDetail', { id: doc.id })}
                    />
                ))}
            </View>
        </ScrollView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: COLORS.background,
    },
    loadingContainer: {
        flex: 1,
        alignItems: 'center',
        justifyContent: 'center',
        backgroundColor: COLORS.background,
    },
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        padding: SPACING.lg,
        paddingTop: SPACING.xl,
        backgroundColor: COLORS.surface,
        borderBottomLeftRadius: BORDER_RADIUS.xl,
        borderBottomRightRadius: BORDER_RADIUS.xl,
        ...SHADOWS.small,
    },
    greeting: {
        fontSize: 14,
        color: COLORS.textSecondary,
    },
    userName: {
        fontSize: 22,
        fontWeight: '700',
        color: COLORS.text,
    },
    profileButton: {
        padding: SPACING.xs,
    },
    statsGrid: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: SPACING.sm,
        padding: SPACING.md,
        paddingTop: SPACING.lg,
    },
    quickActions: {
        flexDirection: 'row',
        paddingHorizontal: SPACING.md,
        gap: SPACING.sm,
        marginBottom: SPACING.md,
    },
    quickActionBtn: {
        flex: 1,
        backgroundColor: COLORS.card,
        borderRadius: BORDER_RADIUS.md,
        padding: SPACING.md,
        alignItems: 'center',
        ...SHADOWS.small,
    },
    quickActionIcon: {
        width: 36,
        height: 36,
        borderRadius: 18,
        alignItems: 'center',
        justifyContent: 'center',
        marginBottom: SPACING.xs,
    },
    quickActionText: {
        fontSize: 11,
        fontWeight: '600',
        color: COLORS.text,
        textAlign: 'center',
    },
    section: {
        padding: SPACING.md,
    },
    sectionHeader: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        marginBottom: SPACING.md,
    },
    sectionTitle: {
        fontSize: 18,
        fontWeight: '700',
        color: COLORS.text,
    },
    seeAll: {
        fontSize: 14,
        fontWeight: '600',
        color: COLORS.primary,
    },
});

export default HomeScreen;
