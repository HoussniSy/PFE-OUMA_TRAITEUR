// Composant indicateur de chargement
import React from 'react';
import { View, ActivityIndicator, Text, StyleSheet } from 'react-native';
import { COLORS } from '../theme/colors';

interface Props {
    message?: string;
    fullScreen?: boolean;
}

const LoadingSpinner: React.FC<Props> = ({ message, fullScreen = true }) => {
    return (
        <View style={[styles.container, fullScreen && styles.fullScreen]}>
            <ActivityIndicator size="large" color={COLORS.primary} />
            {message && <Text style={styles.message}>{message}</Text>}
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        alignItems: 'center',
        justifyContent: 'center',
        padding: 20,
    },
    fullScreen: {
        flex: 1,
        backgroundColor: COLORS.background,
    },
    message: {
        marginTop: 12,
        fontSize: 14,
        color: COLORS.textSecondary,
    },
});

export default LoadingSpinner;
