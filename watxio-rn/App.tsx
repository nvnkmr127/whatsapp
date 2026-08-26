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
import { View } from 'react-native';
import { navigationRef } from '@/navigation/navigationRef';
import AppNavigator from '@/navigation/AppNavigator';
import { useTokens, ThemeProvider } from '@/theme';
import { useColorScheme as useNWColorScheme } from 'nativewind';
import { CallOverlayManager } from '@/components/CallOverlayManager';
import { DeveloperModeGuard } from '@/components/DeveloperModeGuard';
import { ErrorBoundary } from '@/components/ErrorBoundary';
import { store, useGlobalState } from '@/store';
import { api } from '@/services/api';
import { registerForPushNotifications } from '@/services/notifications';
import type { RootStackParamList } from '@/types';

Clarity.initialize('y245efnvkv', {
  logLevel: Clarity.LogLevel.None, // Note: Use "LogLevel.Verbose" value while testing to debug initialization issues.
});

function Root() {
  const { scheme, tokens } = useTokens();
  const { setColorScheme } = useNWColorScheme();
  const [globalState] = useGlobalState();
  const [isReady, setIsReady] = React.useState(false);
  const [initialRoute, setInitialRoute] = React.useState<keyof RootStackParamList>('Onboarding');

  React.useEffect(() => {
    setColorScheme(scheme);
  }, [scheme]);

  // Fast asynchronous boot-time session check
  React.useEffect(() => {
    async function initSession() {
      try {
        const hasSession = await store.loadSession();
        const currentToken = store.get().token;
        if (hasSession && currentToken) {
          setInitialRoute('Main');
          // Silently refresh profile in the background without blocking the UI
          api.get('/v1/mobile/auth/me')
            .then((meResponse) => {
              if (meResponse && meResponse.user) {
                const userTeams = meResponse.teams || [];
                const activeTeam = userTeams[0] || null;
                const teamNumbers = meResponse.numbers || [];
                const activeNumberObj = teamNumbers[0] || null;
                store.set({
                  user: meResponse.user,
                  teams: userTeams,
                  activeTeamId: activeTeam ? activeTeam.id : store.get().activeTeamId,
                  businessName: activeTeam ? activeTeam.name : (store.get().businessName || 'Watxio Workspace'),
                  waNumber: activeNumberObj ? activeNumberObj.display_number : store.get().waNumber,
                  userName: meResponse.user.name,
                  userRole: meResponse.user.role || 'Member',
                  numbers: teamNumbers,
                });
              }
            })
            .catch((err) => {
              // Network offline/delayed errors keep the cached local session active
              console.log('[Auth] Background profile refresh deferred:', err?.message || err);
            });
        } else {
          setInitialRoute('Onboarding');
        }
      } catch (err) {
        console.warn('[Auth] Error restoring session on app boot:', err);
        setInitialRoute('Onboarding');
      } finally {
        setIsReady(true);
      }
    }
    initSession();
  }, []);

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

  if (!isReady) {
    return (
      <View style={{ flex: 1, backgroundColor: tokens.bg }} />
    );
  }

  return (
    <NavigationContainer theme={navTheme} ref={navigationRef}>
      <StatusBar style={scheme === 'dark' ? 'light' : 'dark'} />
      <AppNavigator initialRouteName={initialRoute} />
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
