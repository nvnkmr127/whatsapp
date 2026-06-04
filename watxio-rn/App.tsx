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

  // Register for push notifications on session restore (app opened while already logged in).
  // Fresh-login FCM registration is handled directly in LoginScreen after login succeeds.
  const hasRegisteredRef = React.useRef(false);
  React.useEffect(() => {
    if (globalState.token && !hasRegisteredRef.current) {
      hasRegisteredRef.current = true;
      console.log('[FCM DEBUG] App.tsx: session restore — registering push notifications...');
      registerForPushNotifications()
        .then((token) => {
          if (token) {
            console.log('[FCM DEBUG] ✅ Session-restore registration complete.');
          } else {
            console.warn('[FCM DEBUG] ⚠️ Session-restore registration returned null.');
          }
        })
        .catch((e) => console.error('[FCM DEBUG] ❌ Session-restore registration threw:', e));
    }
    if (!globalState.token) {
      hasRegisteredRef.current = false; // reset on logout so next login re-registers
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
