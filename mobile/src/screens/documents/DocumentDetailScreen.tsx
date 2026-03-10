// Détail d'un document
import React, { useState, useEffect } from 'react';
import {
    View, Text, StyleSheet, ScrollView, TouchableOpacity,
    Alert, ActivityIndicator,
} from 'react-native';
import Icon from '@expo/vector-icons/MaterialCommunityIcons';
import { COLORS } from '../../theme/colors';
import { SPACING, BORDER_RADIUS, SHADOWS } from '../../theme/spacing';
import { documentsApi } from '../../api/documents';
import {
    formatMoney, formatDate, getStatusColor, getTypeColor,
} from '../../utils/formatters';
import { Document } from '../../types/document';
import LoadingSpinner from '../../components/LoadingSpinner';

const DocumentDetailScreen: React.FC<{ navigation: any; route: any }> = ({ navigation, route }) => {
    const { id } = route.params;
    const [doc, setDoc] = useState<Document | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [sending, setSending] = useState(false);

    useEffect(() => {
        loadDocument();
    }, [id]);

    const loadDocument = async () => {
        try {
            const res = await documentsApi.getOne(id);
            setDoc(res.data);
        } catch (error) {
            Alert.alert('Erreur', 'Impossible de charger le document.');
            navigation.goBack();
        } finally {
            setIsLoading(false);
        }
    };

    const handleSendEmail = async () => {
        if (!doc) return;
        setSending(true);
        try {
            await documentsApi.sendByEmail(doc.id);
            Alert.alert('Succès', 'Document envoyé par email.');
            loadDocument();
        } catch (error) {
            Alert.alert('Erreur', "Impossible d'envoyer le document.");
        } finally {
            setSending(false);
        }
    };

    const handleConvert = async () => {
        if (!doc) return;
        Alert.alert(
            'Convertir en facture',
            'Voulez-vous convertir ce devis en facture ?',
            [
                { text: 'Annuler', style: 'cancel' },
                {
                    text: 'Convertir',
                    onPress: async () => {
                        try {
                            const res = await documentsApi.convertToInvoice(doc.id);
                            Alert.alert('Succès', 'Devis converti en facture.');
                            navigation.replace('DocumentDetail', { id: res.data.id });
                        } catch (error) {
                            Alert.alert('Erreur', 'Impossible de convertir le devis.');
                        }
                    },
                },
            ],
        );
    };

    if (isLoading || !doc) return <LoadingSpinner message="Chargement..." />;

    const typeColor = getTypeColor(doc.type);
    const statusColor = getStatusColor(doc.status);

    return (
        <ScrollView style={styles.container}>
            {/* En-tête */}
            <View style={styles.headerCard}>
                <View style={styles.headerRow}>
                    <View style={[styles.typeBadge, { backgroundColor: typeColor + '15' }]}>
                        <Text style={[styles.typeText, { color: typeColor }]}>{doc.typeLabel}</Text>
                    </View>
                    <View style={[styles.statusBadge, { backgroundColor: statusColor + '15' }]}>
                        <View style={[styles.statusDot, { backgroundColor: statusColor }]} />
                        <Text style={[styles.statusText, { color: statusColor }]}>{doc.statusLabel}</Text>
                    </View>
                </View>
                <Text style={styles.docNumber}>{doc.number}</Text>
                <Text style={styles.docDate}>{formatDate(doc.date)}</Text>
                {doc.location && <Text style={styles.location}>📍 {doc.location}</Text>}
            </View>

            {/* Client */}
            <TouchableOpacity
                style={styles.section}
                onPress={() => navigation.navigate('ClientDetail', { id: doc.clientId })}
            >
                <Text style={styles.sectionTitle}>Client</Text>
                <View style={styles.clientRow}>
                    <View style={styles.clientAvatar}>
                        <Icon name="account" size={20} color={COLORS.primary} />
                    </View>
                    <View style={{ flex: 1 }}>
                        <Text style={styles.clientName}>{doc.clientName}</Text>
                        {doc.clientEmail && <Text style={styles.clientDetail}>{doc.clientEmail}</Text>}
                        {doc.clientPhone && <Text style={styles.clientDetail}>{doc.clientPhone}</Text>}
                    </View>
                    <Icon name="chevron-right" size={20} color={COLORS.textLight} />
                </View>
            </TouchableOpacity>

            {/* Prestations */}
            <View style={styles.section}>
                <Text style={styles.sectionTitle}>Prestations ({doc.items.length})</Text>
                {doc.items.map((item, idx) => (
                    <View key={item.id || idx} style={styles.itemRow}>
                        <View style={{ flex: 1 }}>
                            <Text style={styles.itemName}>{item.designation}</Text>
                            <Text style={styles.itemDetail}>
                                {item.numberOfDays}j × {item.numberOfPersons}p × {item.numberOfServices}s × {formatMoney(item.unitPrice, doc.currency)}
                            </Text>
                        </View>
                        <Text style={styles.itemTotal}>{formatMoney(item.totalAmount, doc.currency)}</Text>
                    </View>
                ))}
            </View>

            {/* Totaux */}
            <View style={styles.section}>
                <View style={styles.totalRow}>
                    <Text style={styles.totalLabel}>Total HT</Text>
                    <Text style={styles.totalValue}>{formatMoney(doc.totalHt, doc.currency)}</Text>
                </View>
                <View style={styles.totalRow}>
                    <Text style={styles.totalLabel}>TVA ({doc.taxRate}%)</Text>
                    <Text style={styles.totalValue}>
                        {formatMoney(parseFloat(doc.totalTtc) - parseFloat(doc.totalHt), doc.currency)}
                    </Text>
                </View>
                <View style={[styles.totalRow, styles.totalRowFinal]}>
                    <Text style={styles.totalLabelFinal}>Total TTC</Text>
                    <Text style={styles.totalValueFinal}>{formatMoney(doc.totalTtc, doc.currency)}</Text>
                </View>
                {doc.type === 'invoice' && (
                    <>
                        <View style={styles.totalRow}>
                            <Text style={styles.totalLabel}>Payé</Text>
                            <Text style={[styles.totalValue, { color: COLORS.success }]}>
                                {formatMoney(doc.totalPaid, doc.currency)}
                            </Text>
                        </View>
                        <View style={styles.totalRow}>
                            <Text style={styles.totalLabel}>Reste à payer</Text>
                            <Text style={[styles.totalValue, { color: COLORS.error }]}>
                                {formatMoney(doc.remainingAmount, doc.currency)}
                            </Text>
                        </View>
                    </>
                )}
            </View>

            {/* Paiements (si facture) */}
            {doc.type === 'invoice' && doc.payments && doc.payments.length > 0 && (
                <View style={styles.section}>
                    <Text style={styles.sectionTitle}>Paiements ({doc.payments.length})</Text>
                    {doc.payments.map((p) => (
                        <View key={p.id} style={styles.paymentRow}>
                            <View>
                                <Text style={styles.paymentMode}>{p.modePaiementLabel}</Text>
                                <Text style={styles.paymentDate}>{formatDate(p.datePaiement)}</Text>
                            </View>
                            <Text style={styles.paymentAmount}>{formatMoney(p.montant, doc.currency)}</Text>
                        </View>
                    ))}
                </View>
            )}

            {/* Actions */}
            <View style={styles.actions}>
                <TouchableOpacity
                    style={[styles.actionBtn, { backgroundColor: COLORS.primary }]}
                    onPress={() => navigation.navigate('DocumentForm', { id: doc.id })}
                >
                    <Icon name="pencil" size={18} color={COLORS.white} />
                    <Text style={styles.actionBtnText}>Modifier</Text>
                </TouchableOpacity>

                <TouchableOpacity
                    style={[styles.actionBtn, { backgroundColor: COLORS.info }]}
                    onPress={handleSendEmail}
                    disabled={sending}
                >
                    {sending ? (
                        <ActivityIndicator color={COLORS.white} size="small" />
                    ) : (
                        <Icon name="email-send-outline" size={18} color={COLORS.white} />
                    )}
                    <Text style={styles.actionBtnText}>Envoyer</Text>
                </TouchableOpacity>

                {doc.type === 'quote' && (
                    <TouchableOpacity
                        style={[styles.actionBtn, { backgroundColor: COLORS.invoice }]}
                        onPress={handleConvert}
                    >
                        <Icon name="swap-horizontal" size={18} color={COLORS.white} />
                        <Text style={styles.actionBtnText}>Convertir</Text>
                    </TouchableOpacity>
                )}
            </View>

            <View style={{ height: 40 }} />
        </ScrollView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: COLORS.background },
    headerCard: {
        backgroundColor: COLORS.card,
        padding: SPACING.lg,
        margin: SPACING.md,
        borderRadius: BORDER_RADIUS.md,
        ...SHADOWS.small,
    },
    headerRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: SPACING.sm },
    typeBadge: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: BORDER_RADIUS.sm },
    typeText: { fontSize: 12, fontWeight: '700' },
    statusBadge: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 10, paddingVertical: 4, borderRadius: BORDER_RADIUS.sm, gap: 4 },
    statusDot: { width: 6, height: 6, borderRadius: 3 },
    statusText: { fontSize: 12, fontWeight: '600' },
    docNumber: { fontSize: 20, fontWeight: '700', color: COLORS.text },
    docDate: { fontSize: 14, color: COLORS.textSecondary, marginTop: 2 },
    location: { fontSize: 13, color: COLORS.textSecondary, marginTop: 4 },
    section: {
        backgroundColor: COLORS.card,
        padding: SPACING.md,
        marginHorizontal: SPACING.md,
        marginBottom: SPACING.sm,
        borderRadius: BORDER_RADIUS.md,
        ...SHADOWS.small,
    },
    sectionTitle: { fontSize: 16, fontWeight: '700', color: COLORS.text, marginBottom: SPACING.md },
    clientRow: { flexDirection: 'row', alignItems: 'center' },
    clientAvatar: { width: 40, height: 40, borderRadius: 20, backgroundColor: COLORS.primaryBg, alignItems: 'center', justifyContent: 'center', marginRight: SPACING.md },
    clientName: { fontSize: 15, fontWeight: '600', color: COLORS.text },
    clientDetail: { fontSize: 12, color: COLORS.textSecondary, marginTop: 1 },
    itemRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: SPACING.sm, borderBottomWidth: 1, borderBottomColor: COLORS.border },
    itemName: { fontSize: 14, fontWeight: '600', color: COLORS.text },
    itemDetail: { fontSize: 12, color: COLORS.textSecondary, marginTop: 2 },
    itemTotal: { fontSize: 14, fontWeight: '700', color: COLORS.text, marginLeft: SPACING.sm },
    totalRow: { flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 4 },
    totalLabel: { fontSize: 14, color: COLORS.textSecondary },
    totalValue: { fontSize: 14, fontWeight: '600', color: COLORS.text },
    totalRowFinal: { borderTopWidth: 1, borderTopColor: COLORS.border, marginTop: SPACING.sm, paddingTop: SPACING.sm },
    totalLabelFinal: { fontSize: 16, fontWeight: '700', color: COLORS.text },
    totalValueFinal: { fontSize: 18, fontWeight: '800', color: COLORS.primary },
    paymentRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: SPACING.sm, borderBottomWidth: 1, borderBottomColor: COLORS.border },
    paymentMode: { fontSize: 14, fontWeight: '600', color: COLORS.text },
    paymentDate: { fontSize: 12, color: COLORS.textSecondary },
    paymentAmount: { fontSize: 14, fontWeight: '700', color: COLORS.success },
    actions: { flexDirection: 'row', gap: SPACING.sm, paddingHorizontal: SPACING.md, marginTop: SPACING.sm },
    actionBtn: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', paddingVertical: SPACING.sm + 2, borderRadius: BORDER_RADIUS.sm, gap: 6 },
    actionBtnText: { color: COLORS.white, fontSize: 13, fontWeight: '600' },
});

export default DocumentDetailScreen;
