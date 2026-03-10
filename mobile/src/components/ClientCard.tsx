// Composant carte client réutilisable
import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import Icon from '@expo/vector-icons/MaterialCommunityIcons';
import { COLORS } from '../theme/colors';
import { SPACING, BORDER_RADIUS, SHADOWS } from '../theme/spacing';
import { Client } from '../types/client';

interface Props {
    client: Client;
    onPress: () => void;
}

const ClientCard: React.FC<Props> = ({ client, onPress }) => {
    const initials = client.name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .substring(0, 2)
        .toUpperCase();

    return (
        <TouchableOpacity style={styles.card} onPress={onPress} activeOpacity={0.7}>
            <View style={styles.avatar}>
                <Text style={styles.avatarText}>{initials}</Text>
            </View>
            <View style={styles.info}>
                <Text style={styles.name} numberOfLines={1}>{client.name}</Text>
                {client.email && (
                    <View style={styles.row}>
                        <Icon name="email-outline" size={13} color={COLORS.textSecondary} />
                        <Text style={styles.detail} numberOfLines={1}>{client.email}</Text>
                    </View>
                )}
                {client.phone && (
                    <View style={styles.row}>
                        <Icon name="phone-outline" size={13} color={COLORS.textSecondary} />
                        <Text style={styles.detail}>{client.phone}</Text>
                    </View>
                )}
            </View>
            {client.documentsCount !== undefined && (
                <View style={styles.badge}>
                    <Text style={styles.badgeText}>{client.documentsCount}</Text>
                    <Text style={styles.badgeLabel}>docs</Text>
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
        flexDirection: 'row',
        alignItems: 'center',
        ...SHADOWS.small,
    },
    avatar: {
        width: 44,
        height: 44,
        borderRadius: 22,
        backgroundColor: COLORS.primaryBg,
        alignItems: 'center',
        justifyContent: 'center',
        marginRight: SPACING.md,
    },
    avatarText: {
        fontSize: 16,
        fontWeight: '700',
        color: COLORS.primary,
    },
    info: {
        flex: 1,
    },
    name: {
        fontSize: 15,
        fontWeight: '600',
        color: COLORS.text,
        marginBottom: 2,
    },
    row: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 4,
        marginTop: 1,
    },
    detail: {
        fontSize: 12,
        color: COLORS.textSecondary,
        flex: 1,
    },
    badge: {
        alignItems: 'center',
        marginLeft: SPACING.sm,
    },
    badgeText: {
        fontSize: 16,
        fontWeight: '700',
        color: COLORS.primary,
    },
    badgeLabel: {
        fontSize: 10,
        color: COLORS.textLight,
    },
});

export default ClientCard;
