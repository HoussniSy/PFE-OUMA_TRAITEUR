// Formulaire création/modification client
import React, { useState, useEffect } from 'react';
import {
    View, Text, StyleSheet, ScrollView, TouchableOpacity,
    TextInput, Alert, ActivityIndicator,
} from 'react-native';
import Icon from '@expo/vector-icons/MaterialCommunityIcons';
import { COLORS } from '../../theme/colors';
import { SPACING, BORDER_RADIUS, SHADOWS } from '../../theme/spacing';
import { clientsApi } from '../../api/clients';

const ClientFormScreen: React.FC<{ navigation: any; route: any }> = ({ navigation, route }) => {
    const editId = route.params?.id;

    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [phone, setPhone] = useState('');
    const [address, setAddress] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (editId) loadClient();
    }, [editId]);

    const loadClient = async () => {
        setIsLoading(true);
        try {
            const res = await clientsApi.getOne(editId);
            const c = res.data;
            setName(c.name || '');
            setEmail(c.email || '');
            setPhone(c.phone || '');
            setAddress(c.address || '');
        } catch (e) {
            Alert.alert('Erreur', 'Impossible de charger le client.');
            navigation.goBack();
        } finally {
            setIsLoading(false);
        }
    };

    const handleSubmit = async () => {
        if (!name.trim()) {
            Alert.alert('Erreur', 'Le nom est obligatoire.');
            return;
        }

        setSaving(true);
        try {
            const payload = {
                name: name.trim(),
                email: email.trim() || undefined,
                phone: phone.trim() || undefined,
                address: address.trim() || undefined,
            };

            if (editId) {
                await clientsApi.update(editId, payload);
                Alert.alert('Succès', 'Client modifié avec succès.');
            } else {
                await clientsApi.create(payload);
                Alert.alert('Succès', 'Client créé avec succès.');
            }
            navigation.goBack();
        } catch (error) {
            Alert.alert('Erreur', "Impossible d'enregistrer le client.");
        } finally {
            setSaving(false);
        }
    };

    if (isLoading) {
        return <View style={styles.loadingContainer}><ActivityIndicator size="large" color={COLORS.primary} /></View>;
    }

    return (
        <ScrollView style={styles.container} keyboardShouldPersistTaps="handled">
            <View style={styles.form}>
                <Text style={styles.label}>Nom *</Text>
                <View style={styles.inputContainer}>
                    <Icon name="account-outline" size={20} color={COLORS.textSecondary} />
                    <TextInput
                        style={styles.input}
                        placeholder="Nom du client"
                        placeholderTextColor={COLORS.textLight}
                        value={name}
                        onChangeText={setName}
                    />
                </View>

                <Text style={styles.label}>Email</Text>
                <View style={styles.inputContainer}>
                    <Icon name="email-outline" size={20} color={COLORS.textSecondary} />
                    <TextInput
                        style={styles.input}
                        placeholder="email@exemple.com"
                        placeholderTextColor={COLORS.textLight}
                        value={email}
                        onChangeText={setEmail}
                        keyboardType="email-address"
                        autoCapitalize="none"
                    />
                </View>

                <Text style={styles.label}>Téléphone</Text>
                <View style={styles.inputContainer}>
                    <Icon name="phone-outline" size={20} color={COLORS.textSecondary} />
                    <TextInput
                        style={styles.input}
                        placeholder="+222 XX XX XX XX"
                        placeholderTextColor={COLORS.textLight}
                        value={phone}
                        onChangeText={setPhone}
                        keyboardType="phone-pad"
                    />
                </View>

                <Text style={styles.label}>Adresse</Text>
                <View style={[styles.inputContainer, { height: 80, alignItems: 'flex-start', paddingTop: SPACING.sm }]}>
                    <Icon name="map-marker-outline" size={20} color={COLORS.textSecondary} style={{ marginTop: 2 }} />
                    <TextInput
                        style={[styles.input, { height: 60, textAlignVertical: 'top' }]}
                        placeholder="Adresse complète"
                        placeholderTextColor={COLORS.textLight}
                        value={address}
                        onChangeText={setAddress}
                        multiline
                    />
                </View>

                <TouchableOpacity
                    style={[styles.submitBtn, saving && { opacity: 0.7 }]}
                    onPress={handleSubmit}
                    disabled={saving}
                >
                    {saving ? (
                        <ActivityIndicator color={COLORS.white} />
                    ) : (
                        <>
                            <Icon name="check" size={20} color={COLORS.white} />
                            <Text style={styles.submitText}>{editId ? 'Modifier le client' : 'Créer le client'}</Text>
                        </>
                    )}
                </TouchableOpacity>
            </View>
        </ScrollView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: COLORS.background },
    loadingContainer: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: COLORS.background },
    form: { padding: SPACING.lg },
    label: { fontSize: 13, fontWeight: '600', color: COLORS.textSecondary, marginBottom: 6, marginTop: SPACING.md },
    inputContainer: {
        flexDirection: 'row', alignItems: 'center', backgroundColor: COLORS.card,
        borderRadius: BORDER_RADIUS.sm, paddingHorizontal: SPACING.md, height: 50,
        gap: 10, ...SHADOWS.small,
    },
    input: { flex: 1, fontSize: 15, color: COLORS.text },
    submitBtn: {
        flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
        backgroundColor: COLORS.primary, height: 50, borderRadius: BORDER_RADIUS.sm,
        marginTop: SPACING.xl, gap: 8,
    },
    submitText: { color: COLORS.white, fontSize: 16, fontWeight: '600' },
});

export default ClientFormScreen;
