// Liste des documents avec filtres et pagination
import React, { useState, useEffect, useCallback } from 'react';
import {
    View, Text, StyleSheet, FlatList, TouchableOpacity,
    RefreshControl, TextInput,
} from 'react-native';
import Icon from '@expo/vector-icons/MaterialCommunityIcons';
import { COLORS } from '../../theme/colors';
import { SPACING, BORDER_RADIUS } from '../../theme/spacing';
import DocumentCard from '../../components/DocumentCard';
import LoadingSpinner from '../../components/LoadingSpinner';
import EmptyState from '../../components/EmptyState';
import { documentsApi } from '../../api/documents';
import { DocumentListItem } from '../../types/document';

const FILTERS = [
    { key: '', label: 'Tous' },
    { key: 'quote', label: 'Devis' },
    { key: 'invoice', label: 'Factures' },
];

const DocumentsScreen: React.FC<{ navigation: any }> = ({ navigation }) => {
    const [documents, setDocuments] = useState<DocumentListItem[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [page, setPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [typeFilter, setTypeFilter] = useState('');
    const [search, setSearch] = useState('');
    const [loadingMore, setLoadingMore] = useState(false);

    const loadDocuments = useCallback(async (pageNum = 1, append = false) => {
        try {
            if (pageNum === 1) setIsLoading(true);
            else setLoadingMore(true);

            const res = await documentsApi.getAll(pageNum, typeFilter || undefined, undefined, search || undefined);
            const newDocs = res.data.data || [];

            if (append) {
                setDocuments((prev) => [...prev, ...newDocs]);
            } else {
                setDocuments(newDocs);
            }
            setTotalPages(res.data.totalPages || 1);
            setPage(pageNum);
        } catch (error) {
            console.error('Erreur chargement documents:', error);
        } finally {
            setIsLoading(false);
            setRefreshing(false);
            setLoadingMore(false);
        }
    }, [typeFilter, search]);

    useEffect(() => {
        loadDocuments(1);
    }, [loadDocuments]);

    const onRefresh = () => {
        setRefreshing(true);
        loadDocuments(1);
    };

    const loadMore = () => {
        if (!loadingMore && page < totalPages) {
            loadDocuments(page + 1, true);
        }
    };

    if (isLoading) return <LoadingSpinner message="Chargement des documents..." />;

    return (
        <View style={styles.container}>
            {/* Barre de recherche */}
            <View style={styles.searchBar}>
                <Icon name="magnify" size={20} color={COLORS.textSecondary} />
                <TextInput
                    style={styles.searchInput}
                    placeholder="Rechercher un document..."
                    placeholderTextColor={COLORS.textLight}
                    value={search}
                    onChangeText={setSearch}
                    onSubmitEditing={() => loadDocuments(1)}
                    returnKeyType="search"
                />
                {search.length > 0 && (
                    <TouchableOpacity onPress={() => { setSearch(''); }}>
                        <Icon name="close-circle" size={18} color={COLORS.textLight} />
                    </TouchableOpacity>
                )}
            </View>

            {/* Filtres type */}
            <View style={styles.filters}>
                {FILTERS.map((f) => (
                    <TouchableOpacity
                        key={f.key}
                        style={[styles.filterBtn, typeFilter === f.key && styles.filterBtnActive]}
                        onPress={() => setTypeFilter(f.key)}
                    >
                        <Text style={[styles.filterText, typeFilter === f.key && styles.filterTextActive]}>
                            {f.label}
                        </Text>
                    </TouchableOpacity>
                ))}
            </View>

            {/* Liste */}
            <FlatList
                data={documents}
                keyExtractor={(item) => String(item.id)}
                renderItem={({ item }) => (
                    <DocumentCard
                        document={item}
                        onPress={() => navigation.navigate('DocumentDetail', { id: item.id })}
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
                        icon="file-document-outline"
                        title="Aucun document"
                        message="Créez votre premier devis ou facture."
                        actionLabel="Créer un document"
                        onAction={() => navigation.navigate('DocumentForm', { type: 'quote' })}
                    />
                }
                ListFooterComponent={loadingMore ? <LoadingSpinner fullScreen={false} /> : null}
            />

            {/* Bouton flottant */}
            <TouchableOpacity
                style={styles.fab}
                onPress={() => navigation.navigate('DocumentForm', { type: 'quote' })}
            >
                <Icon name="plus" size={28} color={COLORS.white} />
            </TouchableOpacity>
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: COLORS.background,
    },
    searchBar: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: COLORS.card,
        margin: SPACING.md,
        paddingHorizontal: SPACING.md,
        borderRadius: BORDER_RADIUS.sm,
        height: 44,
        gap: 8,
    },
    searchInput: {
        flex: 1,
        fontSize: 14,
        color: COLORS.text,
    },
    filters: {
        flexDirection: 'row',
        paddingHorizontal: SPACING.md,
        marginBottom: SPACING.sm,
        gap: SPACING.sm,
    },
    filterBtn: {
        paddingHorizontal: SPACING.md,
        paddingVertical: 6,
        borderRadius: BORDER_RADIUS.round,
        backgroundColor: COLORS.card,
    },
    filterBtnActive: {
        backgroundColor: COLORS.primary,
    },
    filterText: {
        fontSize: 13,
        fontWeight: '500',
        color: COLORS.textSecondary,
    },
    filterTextActive: {
        color: COLORS.white,
    },
    list: {
        padding: SPACING.md,
        paddingBottom: 80,
    },
    fab: {
        position: 'absolute',
        right: SPACING.lg,
        bottom: SPACING.lg,
        width: 56,
        height: 56,
        borderRadius: 28,
        backgroundColor: COLORS.primary,
        alignItems: 'center',
        justifyContent: 'center',
        elevation: 6,
        shadowColor: COLORS.primary,
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.3,
        shadowRadius: 8,
    },
});

export default DocumentsScreen;
