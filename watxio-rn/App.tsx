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
import { useGlobalState } from '@/store';
import { registerForPushNotifications } from '@/services/notifications';

function Root() {
  const { scheme } = useTokens();
  const { setColorScheme } = useNWColorScheme();
  const [globalState] = useGlobalState();

  React.useEffect(() => {
    setColorScheme(scheme);
  }, [scheme]);

  // Register for push notifications once the user is authenticated
  React.useEffect(() => {
    if (globalState.token) {
      registerForPushNotifications().catch((e) =>
        console.warn('[FCM] Registration error:', e)
      );
    }
  }, [globalState.token]);

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
