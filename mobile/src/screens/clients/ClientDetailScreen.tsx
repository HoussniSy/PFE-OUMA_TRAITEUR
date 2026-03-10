// Détail d'un client avec historique documents
import React, { useState, useEffect } from 'react';
import {
    View, Text, StyleSheet, ScrollView, TouchableOpacity, Alert,
} from 'react-native';
import Icon from '@expo/vector-icons/MaterialCommunityIcons';
import { COLORS } from '../../theme/colors';
import { SPACING, BORDER_RADIUS, SHADOWS } from '../../theme/spacing';
import { clientsApi } from '../../api/clients';
import { documentsApi } from '../../api/documents';
import { formatMoney, formatDate } from '../../utils/formatters';
import { Client } from '../../types/client';
import { DocumentListItem } from '../../types/document';
import DocumentCard from '../../components/DocumentCard';
import LoadingSpinner from '../../components/LoadingSpinner';

const ClientDetailScreen: React.FC<{ navigation: any; route: any }> = ({ navigation, route }) => {
    const { id } = route.params;
    const [client, setClient] = useState<Client | null>(null);
    const [documents, setDocuments] = useState<DocumentListItem[]>([]);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        loadData();
    }, [id]);

    const loadData = async () => {
        try {
            const [clientRes, docsRes] = await Promise.all([
                clientsApi.getOne(id),
                documentsApi.getAll(1),
            ]);
            setClient(clientRes.data);

            // Filtrer les docs de ce client
            const clientDocs = (docsRes.data.data || []).filter(
                (d: DocumentListItem) => d.clientId === id,
            );
            setDocuments(clientDocs);
        } catch (error) {
            Alert.alert('Erreur', 'Impossible de charger le client.');
            navigation.goBack();
        } finally {
            setIsLoading(false);
        }
    };

    if (isLoading || !client) return <LoadingSpinner message="Chargement..." />;

    // Calculer le CA total
    const totalRevenue = documents
        .filter((d) => d.type === 'invoice')
        .reduce((sum, d) => sum + parseFloat(d.totalTtc || '0'), 0);

    const initials = client.name.split(' ').map((w) => w[0]).join('').substring(0, 2).toUpperCase();

    return (
        <ScrollView style={styles.container}>
            {/* En-tête client */}
            <View style={styles.headerCard}>
                <View style={styles.avatar}>
                    <Text style={styles.avatarText}>{initials}</Text>
                </View>
                <Text style={styles.name}>{client.name}</Text>

                {client.email && (
                    <View style={styles.infoRow}>
                        <Icon name="email-outline" size={16} color={COLORS.textSecondary} />
                        <Text style={styles.infoText}>{client.email}</Text>
                    </View>
                )}
                {client.phone && (
                    <View style={styles.infoRow}>
                        <Icon name="phone-outline" size={16} color={COLORS.textSecondary} />
                        <Text style={styles.infoText}>{client.phone}</Text>
                    </View>
                )}
                {client.address && (
                    <View style={styles.infoRow}>
                        <Icon name="map-marker-outline" size={16} color={COLORS.textSecondary} />
                        <Text style={styles.infoText}>{client.address}</Text>
                    </View>
                )}
            </View>

            {/* Statistiques */}
            <View style={styles.statsRow}>
                <View style={styles.statItem}>
                    <Text style={styles.statValue}>{documents.length}</Text>
                    <Text style={styles.statLabel}>Documents</Text>
                </View>
                <View style={styles.statDivider} />
                <View style={styles.statItem}>
                    <Text style={styles.statValue}>{formatMoney(totalRevenue)}</Text>
                    <Text style={styles.statLabel}>CA Total</Text>
                </View>
            </View>

            {/* Actions */}
            <View style={styles.actions}>
                <TouchableOpacity
                    style={[styles.actionBtn, { backgroundColor: COLORS.primary }]}
                    onPress={() => navigation.navigate('ClientForm', { id: client.id })}
                >
                    <Icon name="pencil" size={18} color={COLORS.white} />
                    <Text style={styles.actionBtnText}>Modifier</Text>
                </TouchableOpacity>
                <TouchableOpacity
                    style={[styles.actionBtn, { backgroundColor: COLORS.quote }]}
                    onPress={() => navigation.navigate('DocumentForm', { type: 'quote', clientId: client.id })}
                >
                    <Icon name="plus" size={18} color={COLORS.white} />
                    <Text style={styles.actionBtnText}>Nouveau doc</Text>
                </TouchableOpacity>
            </View>

            {/* Historique documents */}
            <View style={styles.section}>
                <Text style={styles.sectionTitle}>Historique des documents</Text>
                {documents.length > 0 ? (
                    documents.map((doc) => (
                        <DocumentCard
                            key={doc.id}
                            document={doc}
                            onPress={() => navigation.navigate('DocumentDetail', { id: doc.id })}
                        />
                    ))
                ) : (
                    <Text style={styles.emptyText}>Aucun document pour ce client.</Text>
                )}
            </View>

            <View style={{ height: 40 }} />
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
        width: 64, height: 64, borderRadius: 32, backgroundColor: COLORS.primaryBg,
        alignItems: 'center', justifyContent: 'center', marginBottom: SPACING.md,
    },
    avatarText: { fontSize: 24, fontWeight: '700', color: COLORS.primary },
    name: { fontSize: 20, fontWeight: '700', color: COLORS.text, marginBottom: SPACING.sm },
    infoRow: { flexDirection: 'row', alignItems: 'center', gap: 6, marginTop: 4 },
    infoText: { fontSize: 14, color: COLORS.textSecondary },
    statsRow: {
        flexDirection: 'row', backgroundColor: COLORS.card, marginHorizontal: SPACING.md,
        borderRadius: BORDER_RADIUS.md, padding: SPACING.md, ...SHADOWS.small,
    },
    statItem: { flex: 1, alignItems: 'center' },
    statValue: { fontSize: 18, fontWeight: '700', color: COLORS.primary },
    statLabel: { fontSize: 12, color: COLORS.textSecondary, marginTop: 2 },
    statDivider: { width: 1, backgroundColor: COLORS.border },
    actions: { flexDirection: 'row', gap: SPACING.sm, padding: SPACING.md },
    actionBtn: {
        flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
        paddingVertical: SPACING.sm + 2, borderRadius: BORDER_RADIUS.sm, gap: 6,
    },
    actionBtnText: { color: COLORS.white, fontSize: 13, fontWeight: '600' },
    section: { padding: SPACING.md },
    sectionTitle: { fontSize: 16, fontWeight: '700', color: COLORS.text, marginBottom: SPACING.md },
    emptyText: { fontSize: 14, color: COLORS.textSecondary, textAlign: 'center', padding: SPACING.lg },
});

export default ClientDetailScreen;
