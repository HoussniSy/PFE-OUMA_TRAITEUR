// Composant carte statistique pour le dashboard
import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import Icon from '@expo/vector-icons/MaterialCommunityIcons';
import { COLORS } from '../theme/colors';
import { SPACING, BORDER_RADIUS, SHADOWS } from '../theme/spacing';

interface Props {
    title: string;
    value: string;
    icon: string;
    color?: string;
    subtitle?: string;
}

const StatCard: React.FC<Props> = ({
    title,
    value,
    icon,
    color = COLORS.primary,
    subtitle,
}) => {
    return (
        <View style={styles.card}>
            <View style={[styles.iconContainer, { backgroundColor: color + '15' }]}>
                <Icon name={icon as any} size={22} color={color} />
            </View>
            <Text style={styles.title}>{title}</Text>
            <Text style={styles.value}>{value}</Text>
            {subtitle && <Text style={styles.subtitle}>{subtitle}</Text>}
        </View>
    );
};

const styles = StyleSheet.create({
    card: {
        backgroundColor: COLORS.card,
        borderRadius: BORDER_RADIUS.md,
        padding: SPACING.md,
        flex: 1,
        minWidth: '45%',
        ...SHADOWS.small,
    },
    iconContainer: {
        width: 40,
        height: 40,
        borderRadius: BORDER_RADIUS.sm,
        alignItems: 'center',
        justifyContent: 'center',
        marginBottom: SPACING.sm,
    },
    title: {
        fontSize: 12,
        color: COLORS.textSecondary,
        marginBottom: 2,
    },
    value: {
        fontSize: 20,
        fontWeight: '700',
        color: COLORS.text,
    },
    subtitle: {
        fontSize: 11,
        color: COLORS.textLight,
        marginTop: 2,
    },
});

export default StatCard;
