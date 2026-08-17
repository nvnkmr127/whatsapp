// App.tsx — root. Loads NativeWind global styles, applies safe areas, and
// mounts the navigator.

import 'react-native-gesture-handler';
import './global.css';

import React from 'react';
import { StatusBar } from 'expo-status-bar';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { KeyboardProvider } from 'react-native-keyboard-controller';

import { NavigationContainer, DefaultTheme, DarkTheme } from '@react-navigation/native';
import * as Clarity from '@microsoft/react-native-clarity';
import { navigationRef } from '@/navigation/navigationRef';
import AppNavigator from '@/navigation/AppNavigator';
import { useTokens, ThemeProvider } from '@/theme';
import { useColorScheme as useNWColorScheme } from 'nativewind';
import { CallOverlayManager } from '@/components/CallOverlayManager';
import { DeveloperModeGuard } from '@/components/DeveloperModeGuard';
import { ErrorBoundary } from '@/components/ErrorBoundary';
import { useGlobalState } from '@/store';
import { registerForPushNotifications } from '@/services/notifications';

Clarity.initialize('y245efnvkv', {
  logLevel: Clarity.LogLevel.None, // Note: Use "LogLevel.Verbose" value while testing to debug initialization issues.
});

function Root() {
  const { scheme, tokens } = useTokens();
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

  const navTheme = {
    ...(scheme === 'dark' ? DarkTheme : DefaultTheme),
    colors: {
      ...(scheme === 'dark' ? DarkTheme.colors : DefaultTheme.colors),
      background: tokens.bg,
      card: tokens.bg,
      text: tokens.ink,
      primary: tokens.accent,
      border: tokens.hairline,
    },
  };

  return (
    <NavigationContainer theme={navTheme} ref={navigationRef}>
      <StatusBar style={scheme === 'dark' ? 'light' : 'dark'} />
      <AppNavigator />
      <CallOverlayManager />
      <DeveloperModeGuard />
    </NavigationContainer>
  );
}

export default function App() {
  return (
    <ErrorBoundary>
      <ThemeProvider>
        <GestureHandlerRootView style={{ flex: 1 }}>
          <SafeAreaProvider>
            <KeyboardProvider>
              <Root />
            </KeyboardProvider>
          </SafeAreaProvider>
        </GestureHandlerRootView>
      </ThemeProvider>
    </ErrorBoundary>
  );
}
