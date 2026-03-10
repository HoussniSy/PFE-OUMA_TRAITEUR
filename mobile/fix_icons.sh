#!/bin/bash
cd /home/hakeem/symfony/PFE/mobile

# Replace react-native-vector-icons imports with @expo/vector-icons
find src -type f \( -name '*.tsx' -o -name '*.ts' -o -name '*.js' \) | while read -r file; do
    sed -i "s|from 'react-native-vector-icons/MaterialCommunityIcons'|from '@expo/vector-icons/MaterialCommunityIcons'|g" "$file"
    sed -i "s|from 'react-native-vector-icons/Ionicons'|from '@expo/vector-icons/Ionicons'|g" "$file"
    sed -i "s|from 'react-native-vector-icons/MaterialIcons'|from '@expo/vector-icons/MaterialIcons'|g" "$file"
    sed -i "s|from 'react-native-vector-icons/FontAwesome'|from '@expo/vector-icons/FontAwesome'|g" "$file"
    sed -i "s|from 'react-native-vector-icons/FontAwesome5'|from '@expo/vector-icons/FontAwesome5'|g" "$file"
done

echo "Done replacing vector icons imports"

# Verify the changes
echo "--- Remaining react-native-vector-icons references: ---"
grep -r "react-native-vector-icons" src/ || echo "None found - all replaced!"
