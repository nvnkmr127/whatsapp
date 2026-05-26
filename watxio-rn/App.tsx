// App.tsx — root. Loads NativeWind global styles, applies safe areas, and
// mounts the navigator.

import 'react-native-gesture-handler';
import './global.css';

import React from 'react';
import { StatusBar } from 'expo-status-bar';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { GestureHandlerRootView } from 'react-native-gesture-handler';

import AppNavigator from '@/navigation/AppNavigator';
import { useTokens, ThemeProvider } from '@/theme';
import { useColorScheme as useNWColorScheme } from 'nativewind';
import { CallOverlayManager } from '@/components/CallOverlayManager';

function Root() {
  const { scheme } = useTokens();
  const { setColorScheme } = useNWColorScheme();

  React.useEffect(() => {
    setColorScheme(scheme);
  }, [scheme]);

  return (
    <>
      <StatusBar style={scheme === 'dark' ? 'light' : 'dark'} />
      <AppNavigator />
      <CallOverlayManager />
    </>
  );
}

export default function App() {
  return (
    <ThemeProvider>
      <GestureHandlerRootView style={{ flex: 1 }}>
        <SafeAreaProvider>
          <Root />
        </SafeAreaProvider>
      </GestureHandlerRootView>
    </ThemeProvider>
  );
}
