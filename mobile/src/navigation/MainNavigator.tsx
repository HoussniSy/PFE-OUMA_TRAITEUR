// Navigation principale (Bottom Tabs + Stacks)
import React from 'react';
import { createStackNavigator } from '@react-navigation/stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import Icon from '@expo/vector-icons/MaterialCommunityIcons';
import { COLORS } from '../theme/colors';

// Écrans
import HomeScreen from '../screens/home/HomeScreen';
import DocumentsScreen from '../screens/documents/DocumentsScreen';
import DocumentDetailScreen from '../screens/documents/DocumentDetailScreen';
import DocumentFormScreen from '../screens/documents/DocumentFormScreen';
import ClientsScreen from '../screens/clients/ClientsScreen';
import ClientDetailScreen from '../screens/clients/ClientDetailScreen';
import ClientFormScreen from '../screens/clients/ClientFormScreen';
import ProfileScreen from '../screens/profile/ProfileScreen';
import SettingsScreen from '../screens/profile/SettingsScreen';

const Tab = createBottomTabNavigator();
const Stack = createStackNavigator();

// Couleurs partagées pour les headers
const headerStyle = {
    backgroundColor: COLORS.surface,
    elevation: 0,
    shadowOpacity: 0,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.border,
};

const headerTintColor = COLORS.text;

// Stack Documents
const DocumentsStack = () => (
    <Stack.Navigator screenOptions={{ headerStyle, headerTintColor, headerTitleStyle: { fontWeight: '600' } }}>
        <Stack.Screen name="DocumentsList" component={DocumentsScreen} options={{ title: 'Documents' }} />
        <Stack.Screen name="DocumentDetail" component={DocumentDetailScreen} options={{ title: 'Détail' }} />
        <Stack.Screen name="DocumentForm" component={DocumentFormScreen} options={({ route }: any) => ({
            title: route.params?.id ? 'Modifier le document' : 'Nouveau document',
        })} />
    </Stack.Navigator>
);

// Stack Clients
const ClientsStack = () => (
    <Stack.Navigator screenOptions={{ headerStyle, headerTintColor, headerTitleStyle: { fontWeight: '600' } }}>
        <Stack.Screen name="ClientsList" component={ClientsScreen} options={{ title: 'Clients' }} />
        <Stack.Screen name="ClientDetail" component={ClientDetailScreen} options={{ title: 'Détail client' }} />
        <Stack.Screen name="ClientForm" component={ClientFormScreen} options={({ route }: any) => ({
            title: route.params?.id ? 'Modifier le client' : 'Nouveau client',
        })} />
    </Stack.Navigator>
);

// Stack Profil
const ProfileStack = () => (
    <Stack.Navigator screenOptions={{ headerStyle, headerTintColor, headerTitleStyle: { fontWeight: '600' } }}>
        <Stack.Screen name="ProfileMain" component={ProfileScreen} options={{ title: 'Profil' }} />
        <Stack.Screen name="Settings" component={SettingsScreen} options={{ title: 'Paramètres' }} />
    </Stack.Navigator>
);

// Stack Home (pour pouvoir naviguer vers DocumentDetail, etc.)
const HomeStack = () => (
    <Stack.Navigator screenOptions={{ headerStyle, headerTintColor, headerTitleStyle: { fontWeight: '600' } }}>
        <Stack.Screen name="HomeMain" component={HomeScreen} options={{ headerShown: false }} />
        <Stack.Screen name="DocumentDetail" component={DocumentDetailScreen} options={{ title: 'Détail' }} />
        <Stack.Screen name="DocumentForm" component={DocumentFormScreen} options={({ route }: any) => ({
            title: route.params?.id ? 'Modifier le document' : 'Nouveau document',
        })} />
        <Stack.Screen name="ClientDetail" component={ClientDetailScreen} options={{ title: 'Détail client' }} />
        <Stack.Screen name="ClientForm" component={ClientFormScreen} options={{ title: 'Nouveau client' }} />
        <Stack.Screen name="Profile" component={ProfileScreen} options={{ title: 'Profil' }} />
    </Stack.Navigator>
);

// Tabs principaux
const MainNavigator: React.FC = () => {
    return (
        <Tab.Navigator
            screenOptions={({ route }) => ({
                headerShown: false,
                tabBarIcon: ({ color, size }) => {
                    let iconName = 'home';
                    if (route.name === 'Home') iconName = 'home';
                    else if (route.name === 'Documents') iconName = 'file-document-outline';
                    else if (route.name === 'Clients') iconName = 'account-group-outline';
                    else if (route.name === 'Profile') iconName = 'account-circle-outline';
                    return <Icon name={iconName as any} size={size} color={color} />;
                },
                tabBarActiveTintColor: COLORS.primary,
                tabBarInactiveTintColor: COLORS.textLight,
                tabBarStyle: {
                    backgroundColor: COLORS.surface,
                    borderTopWidth: 1,
                    borderTopColor: COLORS.border,
                    height: 60,
                    paddingBottom: 8,
                    paddingTop: 4,
                },
                tabBarLabelStyle: { fontSize: 11, fontWeight: '600' },
            })}
        >
            <Tab.Screen name="Home" component={HomeStack} options={{ title: 'Accueil' }} />
            <Tab.Screen name="Documents" component={DocumentsStack} />
            <Tab.Screen name="Clients" component={ClientsStack} />
            <Tab.Screen name="Profile" component={ProfileStack} options={{ title: 'Profil' }} />
        </Tab.Navigator>
    );
};

export default MainNavigator;
