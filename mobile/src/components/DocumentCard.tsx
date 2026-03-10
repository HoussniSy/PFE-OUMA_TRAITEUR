// Composant carte de document réutilisable
import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import Icon from '@expo/vector-icons/MaterialCommunityIcons';
import { COLORS } from '../theme/colors';
import { SPACING, BORDER_RADIUS, SHADOWS } from '../theme/spacing';
import { DocumentListItem } from '../types/document';
import { formatMoney, formatDate, getStatusColor, getTypeColor } from '../utils/formatters';

interface Props {
    document: DocumentListItem;
    onPress: () => void;
}

const DocumentCard: React.FC<Props> = ({ document, onPress }) => {
    const typeColor = getTypeColor(document.type);
    const statusColor = getStatusColor(document.status);

    return (
        <TouchableOpacity style={styles.card} onPress={onPress} activeOpacity={0.7}>
            {/* En-tête : badge type + numéro */}
            <View style={styles.header}>
                <View style={[styles.typeBadge, { backgroundColor: typeColor + '15' }]}>
                    <Icon
                        name={document.type === 'quote' ? 'file-document-outline' : 'receipt'}
                        size={14}
                        color={typeColor}
                    />
                    <Text style={[styles.typeText, { color: typeColor }]}>
                        {document.typeLabel}
                    </Text>
                </View>
                <Text style={styles.number}>{document.number}</Text>
            </View>

            {/* Client */}
            <Text style={styles.clientName} numberOfLines={1}>
                {document.clientName}
            </Text>

            {/* Pied : montant + statut + date */}
            <View style={styles.footer}>
                <Text style={styles.amount}>
                    {formatMoney(document.totalTtc, document.currency)}
                </Text>
                <View style={styles.footerRight}>
                    <View style={[styles.statusBadge, { backgroundColor: statusColor + '15' }]}>
                        <View style={[styles.statusDot, { backgroundColor: statusColor }]} />
                        <Text style={[styles.statusText, { color: statusColor }]}>
                            {document.statusLabel}
                        </Text>
                    </View>
                </View>
            </View>

            <Text style={styles.date}>{formatDate(document.date)}</Text>

            {/* Indicateur retard */}
            {document.isOverdue && (
                <View style={styles.overdueBar}>
                    <Icon name="alert-circle" size={12} color={COLORS.error} />
                    <Text style={styles.overdueText}>En retard</Text>
                </View>
            )}
        </TouchableOpacity>
    );
};

const styles = StyleSheet.create({
    card: {
        backgroundColor: COLORS.card,
        borderRadius: BORDER_RADIUS.md,
        padding: SPACING.md,
        marginBottom: SPACING.sm,
        ...SHADOWS.small,
    },
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        marginBottom: SPACING.xs,
    },
    typeBadge: {
        flexDirection: 'row',
        alignItems: 'center',
        paddingHorizontal: SPACING.sm,
        paddingVertical: 3,
        borderRadius: BORDER_RADIUS.sm,
        gap: 4,
    },
    typeText: {
        fontSize: 12,
        fontWeight: '600',
    },
    number: {
        fontSize: 13,
        fontWeight: '600',
        color: COLORS.textSecondary,
    },
    clientName: {
        fontSize: 15,
        fontWeight: '600',
        color: COLORS.text,
        marginBottom: SPACING.sm,
    },
    footer: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
    },
    footerRight: {
        flexDirection: 'row',
        alignItems: 'center',
    },
    amount: {
        fontSize: 16,
        fontWeight: '700',
        color: COLORS.text,
    },
    statusBadge: {
        flexDirection: 'row',
        alignItems: 'center',
        paddingHorizontal: SPACING.sm,
        paddingVertical: 3,
        borderRadius: BORDER_RADIUS.sm,
        gap: 4,
    },
    statusDot: {
        width: 6,
        height: 6,
        borderRadius: 3,
    },
    statusText: {
        fontSize: 11,
        fontWeight: '600',
    },
    date: {
        fontSize: 12,
        color: COLORS.textLight,
        marginTop: SPACING.xs,
    },
    overdueBar: {
        flexDirection: 'row',
        alignItems: 'center',
        marginTop: SPACING.xs,
        gap: 4,
    },
    overdueText: {
        fontSize: 11,
        color: COLORS.error,
        fontWeight: '500',
    },
});

export default DocumentCard;
