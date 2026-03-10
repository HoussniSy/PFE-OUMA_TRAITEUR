// Formulaire de création/modification de document (multi-étapes)
import React, { useState, useEffect } from 'react';
import {
    View, Text, StyleSheet, ScrollView, TouchableOpacity,
    TextInput, Alert, ActivityIndicator,
} from 'react-native';
import Icon from '@expo/vector-icons/MaterialCommunityIcons';
import { COLORS } from '../../theme/colors';
import { SPACING, BORDER_RADIUS, SHADOWS } from '../../theme/spacing';
import { documentsApi } from '../../api/documents';
import { clientsApi } from '../../api/clients';
import { formatMoney } from '../../utils/formatters';
import { Client } from '../../types/client';
import { DocumentType } from '../../types/document';

interface ItemForm {
    designation: string;
    numberOfDays: string;
    numberOfPersons: string;
    numberOfServices: string;
    unitPrice: string;
}

const DocumentFormScreen: React.FC<{ navigation: any; route: any }> = ({ navigation, route }) => {
    const editId = route.params?.id;
    const initialType: DocumentType = route.params?.type || 'quote';

    const [step, setStep] = useState(1);
    const [isLoading, setIsLoading] = useState(false);
    const [saving, setSaving] = useState(false);

    // Étape 1 — Infos générales
    const [type, setType] = useState<DocumentType>(initialType);
    const [clientId, setClientId] = useState<number | null>(null);
    const [clientSearch, setClientSearch] = useState('');
    const [clients, setClients] = useState<Client[]>([]);
    const [showClients, setShowClients] = useState(false);
    const [selectedClient, setSelectedClient] = useState<Client | null>(null);
    const [date, setDate] = useState(new Date().toISOString().split('T')[0]);
    const [location, setLocation] = useState('');

    // Étape 2 — Prestations
    const [items, setItems] = useState<ItemForm[]>([
        { designation: '', numberOfDays: '1', numberOfPersons: '1', numberOfServices: '1', unitPrice: '' },
    ]);

    // Charger les clients pour l'autocomplete
    useEffect(() => {
        const searchClients = async () => {
            if (clientSearch.length < 1) { setClients([]); return; }
            try {
                const res = await clientsApi.getAll(1, clientSearch);
                setClients(res.data.data || []);
                setShowClients(true);
            } catch (e) { /* ignore */ }
        };
        const timer = setTimeout(searchClients, 300);
        return () => clearTimeout(timer);
    }, [clientSearch]);

    // Charger le document si édition
    useEffect(() => {
        if (editId) {
            loadDocument();
        }
    }, [editId]);

    const loadDocument = async () => {
        setIsLoading(true);
        try {
            const res = await documentsApi.getOne(editId);
            const doc = res.data;
            setType(doc.type);
            setClientId(doc.clientId);
            setSelectedClient({ id: doc.clientId, name: doc.clientName, createdAt: '' });
            setClientSearch(doc.clientName);
            setDate(doc.date);
            setLocation(doc.location || '');
            setItems(doc.items.map((i) => ({
                designation: i.designation,
                numberOfDays: String(i.numberOfDays),
                numberOfPersons: String(i.numberOfPersons),
                numberOfServices: String(i.numberOfServices),
                unitPrice: String(i.unitPrice),
            })));
        } catch (e) {
            Alert.alert('Erreur', 'Impossible de charger le document.');
            navigation.goBack();
        } finally {
            setIsLoading(false);
        }
    };

    const selectClient = (client: Client) => {
        setClientId(client.id);
        setSelectedClient(client);
        setClientSearch(client.name);
        setShowClients(false);
    };

    const addItem = () => {
        setItems([...items, { designation: '', numberOfDays: '1', numberOfPersons: '1', numberOfServices: '1', unitPrice: '' }]);
    };

    const removeItem = (index: number) => {
        if (items.length <= 1) return;
        setItems(items.filter((_, i) => i !== index));
    };

    const updateItem = (index: number, field: keyof ItemForm, value: string) => {
        const updated = [...items];
        updated[index] = { ...updated[index], [field]: value };
        setItems(updated);
    };

    const calculateItemTotal = (item: ItemForm): number => {
        return (parseInt(item.numberOfDays) || 0) *
            (parseInt(item.numberOfPersons) || 0) *
            (parseInt(item.numberOfServices) || 0) *
            (parseFloat(item.unitPrice) || 0);
    };

    const totalHt = items.reduce((sum, item) => sum + calculateItemTotal(item), 0);
    const taxRate = 16;
    const totalTtc = totalHt * (1 + taxRate / 100);

    const handleSubmit = async () => {
        if (!clientId) { Alert.alert('Erreur', 'Veuillez sélectionner un client.'); setStep(1); return; }
        if (items.some((i) => !i.designation || !i.unitPrice)) {
            Alert.alert('Erreur', 'Veuillez remplir toutes les prestations.'); setStep(2); return;
        }

        setSaving(true);
        try {
            const payload = {
                type,
                clientId,
                date,
                location: location || undefined,
                items: items.map((i, idx) => ({
                    designation: i.designation,
                    numberOfDays: parseInt(i.numberOfDays) || 1,
                    numberOfPersons: parseInt(i.numberOfPersons) || 1,
                    numberOfServices: parseInt(i.numberOfServices) || 1,
                    unitPrice: i.unitPrice,
                    position: idx,
                })),
            };

            if (editId) {
                await documentsApi.update(editId, payload);
                Alert.alert('Succès', 'Document modifié avec succès.');
            } else {
                const res = await documentsApi.create(payload);
                Alert.alert('Succès', 'Document créé avec succès.');
                navigation.replace('DocumentDetail', { id: res.data.id });
                return;
            }
            navigation.goBack();
        } catch (error) {
            Alert.alert('Erreur', "Impossible d'enregistrer le document.");
        } finally {
            setSaving(false);
        }
    };

    if (isLoading) {
        return <View style={styles.loadingContainer}><ActivityIndicator size="large" color={COLORS.primary} /></View>;
    }

    return (
        <View style={styles.container}>
            {/* Indicateur d'étapes */}
            <View style={styles.stepper}>
                {[1, 2, 3].map((s) => (
                    <TouchableOpacity key={s} style={styles.stepItem} onPress={() => setStep(s)}>
                        <View style={[styles.stepCircle, step >= s && styles.stepCircleActive]}>
                            <Text style={[styles.stepNum, step >= s && styles.stepNumActive]}>{s}</Text>
                        </View>
                        <Text style={[styles.stepLabel, step >= s && styles.stepLabelActive]}>
                            {s === 1 ? 'Infos' : s === 2 ? 'Prestations' : 'Résumé'}
                        </Text>
                    </TouchableOpacity>
                ))}
            </View>

            <ScrollView style={styles.content} keyboardShouldPersistTaps="handled">
                {/* ÉTAPE 1 — Infos générales */}
                {step === 1 && (
                    <View style={styles.stepContent}>
                        <Text style={styles.sectionTitle}>Type de document</Text>
                        <View style={styles.typeRow}>
                            <TouchableOpacity
                                style={[styles.typeBtn, type === 'quote' && { backgroundColor: COLORS.quote + '15', borderColor: COLORS.quote }]}
                                onPress={() => setType('quote')}
                            >
                                <Icon name="file-document-outline" size={20} color={type === 'quote' ? COLORS.quote : COLORS.textLight} />
                                <Text style={[styles.typeText, type === 'quote' && { color: COLORS.quote }]}>Devis</Text>
                            </TouchableOpacity>
                            <TouchableOpacity
                                style={[styles.typeBtn, type === 'invoice' && { backgroundColor: COLORS.invoice + '15', borderColor: COLORS.invoice }]}
                                onPress={() => setType('invoice')}
                            >
                                <Icon name="receipt" size={20} color={type === 'invoice' ? COLORS.invoice : COLORS.textLight} />
                                <Text style={[styles.typeText, type === 'invoice' && { color: COLORS.invoice }]}>Facture</Text>
                            </TouchableOpacity>
                        </View>

                        <Text style={styles.label}>Client *</Text>
                        <TextInput
                            style={styles.input}
                            placeholder="Rechercher un client..."
                            placeholderTextColor={COLORS.textLight}
                            value={clientSearch}
                            onChangeText={(t) => { setClientSearch(t); setClientId(null); setSelectedClient(null); }}
                        />
                        {showClients && clients.length > 0 && (
                            <View style={styles.dropdown}>
                                {clients.map((c) => (
                                    <TouchableOpacity key={c.id} style={styles.dropdownItem} onPress={() => selectClient(c)}>
                                        <Text style={styles.dropdownText}>{c.name}</Text>
                                    </TouchableOpacity>
                                ))}
                            </View>
                        )}
                        {selectedClient && (
                            <View style={styles.selectedClient}>
                                <Icon name="check-circle" size={16} color={COLORS.success} />
                                <Text style={styles.selectedClientText}>{selectedClient.name}</Text>
                            </View>
                        )}

                        <Text style={styles.label}>Date</Text>
                        <TextInput style={styles.input} value={date} onChangeText={setDate} placeholder="YYYY-MM-DD" placeholderTextColor={COLORS.textLight} />

                        <Text style={styles.label}>Lieu</Text>
                        <TextInput style={styles.input} value={location} onChangeText={setLocation} placeholder="Ex: Nouakchott" placeholderTextColor={COLORS.textLight} />
                    </View>
                )}

                {/* ÉTAPE 2 — Prestations */}
                {step === 2 && (
                    <View style={styles.stepContent}>
                        {items.map((item, idx) => (
                            <View key={idx} style={styles.itemCard}>
                                <View style={styles.itemHeader}>
                                    <Text style={styles.itemTitle}>Prestation {idx + 1}</Text>
                                    {items.length > 1 && (
                                        <TouchableOpacity onPress={() => removeItem(idx)}>
                                            <Icon name="close-circle" size={22} color={COLORS.error} />
                                        </TouchableOpacity>
                                    )}
                                </View>
                                <TextInput style={styles.input} placeholder="Désignation *" placeholderTextColor={COLORS.textLight}
                                    value={item.designation} onChangeText={(v) => updateItem(idx, 'designation', v)} />
                                <View style={styles.row}>
                                    <View style={styles.rowField}>
                                        <Text style={styles.miniLabel}>Jours</Text>
                                        <TextInput style={styles.miniInput} keyboardType="numeric"
                                            value={item.numberOfDays} onChangeText={(v) => updateItem(idx, 'numberOfDays', v)} />
                                    </View>
                                    <View style={styles.rowField}>
                                        <Text style={styles.miniLabel}>Pers.</Text>
                                        <TextInput style={styles.miniInput} keyboardType="numeric"
                                            value={item.numberOfPersons} onChangeText={(v) => updateItem(idx, 'numberOfPersons', v)} />
                                    </View>
                                    <View style={styles.rowField}>
                                        <Text style={styles.miniLabel}>Serv.</Text>
                                        <TextInput style={styles.miniInput} keyboardType="numeric"
                                            value={item.numberOfServices} onChangeText={(v) => updateItem(idx, 'numberOfServices', v)} />
                                    </View>
                                    <View style={styles.rowField}>
                                        <Text style={styles.miniLabel}>Prix unit.</Text>
                                        <TextInput style={styles.miniInput} keyboardType="numeric"
                                            value={item.unitPrice} onChangeText={(v) => updateItem(idx, 'unitPrice', v)} />
                                    </View>
                                </View>
                                <Text style={styles.itemTotalText}>
                                    Total: {formatMoney(calculateItemTotal(item))}
                                </Text>
                            </View>
                        ))}
                        <TouchableOpacity style={styles.addItemBtn} onPress={addItem}>
                            <Icon name="plus-circle" size={20} color={COLORS.primary} />
                            <Text style={styles.addItemText}>Ajouter une prestation</Text>
                        </TouchableOpacity>
                    </View>
                )}

                {/* ÉTAPE 3 — Récapitulatif */}
                {step === 3 && (
                    <View style={styles.stepContent}>
                        <View style={styles.summaryCard}>
                            <Text style={styles.sectionTitle}>Récapitulatif</Text>
                            <View style={styles.summaryRow}>
                                <Text style={styles.summaryLabel}>Type</Text>
                                <Text style={styles.summaryValue}>{type === 'quote' ? 'Devis' : 'Facture'}</Text>
                            </View>
                            <View style={styles.summaryRow}>
                                <Text style={styles.summaryLabel}>Client</Text>
                                <Text style={styles.summaryValue}>{selectedClient?.name || '-'}</Text>
                            </View>
                            <View style={styles.summaryRow}>
                                <Text style={styles.summaryLabel}>Date</Text>
                                <Text style={styles.summaryValue}>{date}</Text>
                            </View>
                            <View style={styles.summaryRow}>
                                <Text style={styles.summaryLabel}>Prestations</Text>
                                <Text style={styles.summaryValue}>{items.length}</Text>
                            </View>
                            <View style={styles.divider} />
                            <View style={styles.summaryRow}>
                                <Text style={styles.summaryLabel}>Total HT</Text>
                                <Text style={styles.summaryValue}>{formatMoney(totalHt)}</Text>
                            </View>
                            <View style={styles.summaryRow}>
                                <Text style={styles.summaryLabel}>TVA ({taxRate}%)</Text>
                                <Text style={styles.summaryValue}>{formatMoney(totalTtc - totalHt)}</Text>
                            </View>
                            <View style={[styles.summaryRow, { marginTop: SPACING.sm }]}>
                                <Text style={styles.summaryLabelBig}>Total TTC</Text>
                                <Text style={styles.summaryValueBig}>{formatMoney(totalTtc)}</Text>
                            </View>
                        </View>
                    </View>
                )}
            </ScrollView>

            {/* Navigation étapes */}
            <View style={styles.footer}>
                {step > 1 && (
                    <TouchableOpacity style={styles.prevBtn} onPress={() => setStep(step - 1)}>
                        <Icon name="arrow-left" size={20} color={COLORS.textSecondary} />
                        <Text style={styles.prevBtnText}>Précédent</Text>
                    </TouchableOpacity>
                )}
                <View style={{ flex: 1 }} />
                {step < 3 ? (
                    <TouchableOpacity style={styles.nextBtn} onPress={() => setStep(step + 1)}>
                        <Text style={styles.nextBtnText}>Suivant</Text>
                        <Icon name="arrow-right" size={20} color={COLORS.white} />
                    </TouchableOpacity>
                ) : (
                    <TouchableOpacity style={styles.submitBtn} onPress={handleSubmit} disabled={saving}>
                        {saving ? <ActivityIndicator color={COLORS.white} /> : (
                            <>
                                <Icon name="check" size={20} color={COLORS.white} />
                                <Text style={styles.nextBtnText}>{editId ? 'Modifier' : 'Créer'}</Text>
                            </>
                        )}
                    </TouchableOpacity>
                )}
            </View>
        </View>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: COLORS.background },
    loadingContainer: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: COLORS.background },
    stepper: { flexDirection: 'row', justifyContent: 'center', padding: SPACING.md, backgroundColor: COLORS.card, gap: SPACING.xl },
    stepItem: { alignItems: 'center' },
    stepCircle: { width: 32, height: 32, borderRadius: 16, backgroundColor: COLORS.border, alignItems: 'center', justifyContent: 'center', marginBottom: 4 },
    stepCircleActive: { backgroundColor: COLORS.primary },
    stepNum: { fontSize: 14, fontWeight: '700', color: COLORS.textLight },
    stepNumActive: { color: COLORS.white },
    stepLabel: { fontSize: 11, color: COLORS.textLight },
    stepLabelActive: { color: COLORS.primary, fontWeight: '600' },
    content: { flex: 1 },
    stepContent: { padding: SPACING.md },
    sectionTitle: { fontSize: 16, fontWeight: '700', color: COLORS.text, marginBottom: SPACING.md },
    label: { fontSize: 13, fontWeight: '600', color: COLORS.textSecondary, marginBottom: 4, marginTop: SPACING.md },
    input: { backgroundColor: COLORS.card, borderRadius: BORDER_RADIUS.sm, paddingHorizontal: SPACING.md, height: 46, fontSize: 14, color: COLORS.text, ...SHADOWS.small },
    typeRow: { flexDirection: 'row', gap: SPACING.md, marginBottom: SPACING.sm },
    typeBtn: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', padding: SPACING.md, borderRadius: BORDER_RADIUS.sm, borderWidth: 1.5, borderColor: COLORS.border, gap: 8 },
    typeText: { fontSize: 14, fontWeight: '600', color: COLORS.textLight },
    dropdown: { backgroundColor: COLORS.card, borderRadius: BORDER_RADIUS.sm, marginTop: 4, ...SHADOWS.medium },
    dropdownItem: { padding: SPACING.md, borderBottomWidth: 1, borderBottomColor: COLORS.border },
    dropdownText: { fontSize: 14, color: COLORS.text },
    selectedClient: { flexDirection: 'row', alignItems: 'center', gap: 6, marginTop: SPACING.xs },
    selectedClientText: { fontSize: 13, color: COLORS.success, fontWeight: '600' },
    itemCard: { backgroundColor: COLORS.card, borderRadius: BORDER_RADIUS.md, padding: SPACING.md, marginBottom: SPACING.md, ...SHADOWS.small },
    itemHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: SPACING.sm },
    itemTitle: { fontSize: 14, fontWeight: '700', color: COLORS.text },
    row: { flexDirection: 'row', gap: SPACING.sm, marginTop: SPACING.sm },
    rowField: { flex: 1 },
    miniLabel: { fontSize: 11, color: COLORS.textSecondary, marginBottom: 2 },
    miniInput: { backgroundColor: COLORS.background, borderRadius: BORDER_RADIUS.sm, paddingHorizontal: SPACING.sm, height: 38, fontSize: 14, color: COLORS.text, textAlign: 'center' },
    itemTotalText: { fontSize: 14, fontWeight: '700', color: COLORS.primary, textAlign: 'right', marginTop: SPACING.sm },
    addItemBtn: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', padding: SPACING.md, gap: 8, borderWidth: 1.5, borderColor: COLORS.primary, borderRadius: BORDER_RADIUS.sm, borderStyle: 'dashed' },
    addItemText: { fontSize: 14, fontWeight: '600', color: COLORS.primary },
    summaryCard: { backgroundColor: COLORS.card, borderRadius: BORDER_RADIUS.md, padding: SPACING.lg, ...SHADOWS.small },
    summaryRow: { flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 6 },
    summaryLabel: { fontSize: 14, color: COLORS.textSecondary },
    summaryValue: { fontSize: 14, fontWeight: '600', color: COLORS.text },
    summaryLabelBig: { fontSize: 16, fontWeight: '700', color: COLORS.text },
    summaryValueBig: { fontSize: 20, fontWeight: '800', color: COLORS.primary },
    divider: { height: 1, backgroundColor: COLORS.border, marginVertical: SPACING.sm },
    footer: { flexDirection: 'row', padding: SPACING.md, backgroundColor: COLORS.card, ...SHADOWS.medium },
    prevBtn: { flexDirection: 'row', alignItems: 'center', gap: 4, paddingVertical: SPACING.sm },
    prevBtnText: { fontSize: 14, color: COLORS.textSecondary },
    nextBtn: { flexDirection: 'row', alignItems: 'center', backgroundColor: COLORS.primary, paddingHorizontal: SPACING.lg, paddingVertical: SPACING.sm + 2, borderRadius: BORDER_RADIUS.sm, gap: 6 },
    submitBtn: { flexDirection: 'row', alignItems: 'center', backgroundColor: COLORS.success, paddingHorizontal: SPACING.lg, paddingVertical: SPACING.sm + 2, borderRadius: BORDER_RADIUS.sm, gap: 6 },
    nextBtnText: { fontSize: 14, fontWeight: '600', color: COLORS.white },
});

export default DocumentFormScreen;
