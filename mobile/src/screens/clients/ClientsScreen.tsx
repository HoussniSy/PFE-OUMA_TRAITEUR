// Liste des clients avec recherche
import React, { useState, useEffect, useCallback } from 'react';
import {
    View, Text, StyleSheet, FlatList, TouchableOpacity,
    RefreshControl, TextInput,
} from 'react-native';
import Icon from '@expo/vector-icons/MaterialCommunityIcons';
import { COLORS } from '../../theme/colors';
import { SPACING, BORDER_RADIUS } from '../../theme/spacing';
import ClientCard from '../../components/ClientCard';
import LoadingSpinner from '../../components/LoadingSpinner';
import EmptyState from '../../components/EmptyState';
import { clientsApi } from '../../api/clients';
import { Client } from '../../types/client';

const ClientsScreen: React.FC<{ navigation: any }> = ({ navigation }) => {
    const [clients, setClients] = useState<Client[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [loadingMore, setLoadingMore] = useState(false);

    const loadClients = useCallback(async (pageNum = 1, append = false) => {
        try {
            if (pageNum === 1) setIsLoading(true);
            else setLoadingMore(true);

            const res = await clientsApi.getAll(pageNum, search || undefined);
            const newClients = res.data.data || [];

            if (append) {
                setClients((prev) => [...prev, ...newClients]);
            } else {
                setClients(newClients);
            }
            setTotalPages(res.data.totalPages || 1);
            setPage(pageNum);
        } catch (error) {
            console.error('Erreur chargement clients:', error);
        } finally {
            setIsLoading(false);
            setRefreshing(false);
            setLoadingMore(false);
        }
    }, [search]);

    useEffect(() => {
        loadClients(1);
    }, [loadClients]);

    const onRefresh = () => {
        setRefreshing(true);
        loadClients(1);
    };

    const loadMore = () => {
        if (!loadingMore && page < totalPages) {
            loadClients(page + 1, true);
        }
    };

    if (isLoading) return <LoadingSpinner message="Chargement des clients..." />;

    return (
        <View style={styles.container}>
            {/* Barre de recherche */}
            <View style={styles.searchBar}>
                <Icon name="magnify" size={20} color={COLORS.textSecondary} />
                <TextInput
                    style={styles.searchInput}
                    placeholder="Rechercher un client..."
                    placeholderTextColor={COLORS.textLight}
                    value={search}
                    onChangeText={setSearch}
                    onSubmitEditing={() => loadClients(1)}
                    returnKeyType="search"
                />
                {search.length > 0 && (
                    <TouchableOpacity onPress={() => setSearch('')}>
                        <Icon name="close-circle" size={18} color={COLORS.textLight} />
                    </TouchableOpacity>
                )}
            </View>

            {/* Liste */}
            <FlatList
                data={clients}
                keyExtractor={(item) => String(item.id)}
                renderItem={({ item }) => (
                    <ClientCard
                        client={item}
                        onPress={() => navigation.navigate('ClientDetail', { id: item.id })}
                    />
                )}
                contentContainerStyle={styles.list}
                refreshControl={
                    <RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={[COLORS.primary]} />
                }
                onEndReached={loadMore}
                onEndReachedThreshold={0.3}
                ListEmptyComponent={
                    <EmptyState
                        icon="account-group"
                        title="Aucun client"
                        message="Ajoutez votre premier client."
                        actionLabel="Ajouter un client"
                        onAction={() => navigation.navigate('ClientForm')}
                    />
                }
                ListFooterComponent={loadingMore ? <LoadingSpinner fullScreen={false} /> : null}
            />

            {/* Bouton flottant */}
            <TouchableOpacity
                style={styles.fab}
                onPress={() => navigation.navigate('ClientForm')}
            >
                <Icon name="plus" size={28} color={COLORS.white} />
            </TouchableOpacity>
        </View>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: COLORS.background },
    searchBar: {
        flexDirection: 'row', alignItems: 'center', backgroundColor: COLORS.card,
        margin: SPACING.md, paddingHorizontal: SPACING.md, borderRadius: BORDER_RADIUS.sm,
        height: 44, gap: 8,
    },
    searchInput: { flex: 1, fontSize: 14, color: COLORS.text },
    list: { padding: SPACING.md, paddingBottom: 80 },
    fab: {
        position: 'absolute', right: SPACING.lg, bottom: SPACING.lg,
        width: 56, height: 56, borderRadius: 28, backgroundColor: COLORS.primary,
        alignItems: 'center', justifyContent: 'center', elevation: 6,
        shadowColor: COLORS.primary, shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.3, shadowRadius: 8,
    },
});

export default ClientsScreen;
