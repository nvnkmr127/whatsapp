/**
 * notifications.ts — Firebase push notification service for Expo.
 *
 * • Requests permission and gets the native FCM / APNs device token.
 * • Registers that token with the Watxio backend.
 * • Sets up foreground notification display behaviour.
 * • Provides a hook to listen for notification taps (background / killed state).
 *
 * Usage (call once after login, inside a component that has navigation):
 *   await registerForPushNotifications(api, token);
 *   useNotificationNavigation(navigation);
 */

import { useEffect, useRef } from 'react';
import { Platform } from 'react-native';
import * as Notifications from 'expo-notifications';
import * as Device from 'expo-device';
import { api } from '@/services/api';
import { securityService } from '@/services/security';

// ── Foreground display behaviour ─────────────────────────────────────────────
// Show banner + sound even when the app is open (like WhatsApp).
Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldPlaySound: true,
    shouldSetBadge: true,
    shouldShowBanner: true,
    shouldShowList: true,
  }),
});

// ── Android notification channel ─────────────────────────────────────────────
export async function setupAndroidChannel() {
  if (Platform.OS !== 'android') return;

  // Delete old channels first — Android ignores setNotificationChannelAsync
  // updates to sound/vibration if the channel already exists with stale settings.
  await Notifications.deleteNotificationChannelAsync('default').catch(() => {});
  await Notifications.deleteNotificationChannelAsync('calls').catch(() => {});

  const defaultChannel = await Notifications.setNotificationChannelAsync('default', {
    name: 'Watxio Messages',
    importance: Notifications.AndroidImportance.MAX,
    vibrationPattern: [0, 250, 250, 250],
    lightColor: '#2F8F6F',
    sound: 'default',
    enableVibrate: true,
    showBadge: true,
  });
  console.log('[FCM DEBUG] default channel:', JSON.stringify(defaultChannel));

  const callsChannel = await Notifications.setNotificationChannelAsync('calls', {
    name: 'Incoming Calls',
    importance: Notifications.AndroidImportance.MAX,
    vibrationPattern: [0, 500, 500, 500],
    lightColor: '#2F8F6F',
    sound: 'default',
    enableVibrate: true,
    showBadge: false,
    bypassDnd: true,
  });
  console.log('[FCM DEBUG] calls channel:', JSON.stringify(callsChannel));

  // Log all existing channels for verification
  const allChannels = await Notifications.getNotificationChannelsAsync();
  console.log('[FCM DEBUG] All notification channels on device:', JSON.stringify(allChannels?.map((c: any) => ({ id: c.id, importance: c.importance, sound: c.sound }))));
}

// ── Permission + token registration ─────────────────────────────────────────
export async function registerForPushNotifications(): Promise<string | null> {
  console.log('[FCM DEBUG] registerForPushNotifications() called');
  console.log('[FCM DEBUG] Platform:', Platform.OS);
  console.log('[FCM DEBUG] Is physical device:', Device.isDevice);
  console.log('[FCM DEBUG] Device model:', Device.modelName);
  console.log('[FCM DEBUG] OS version:', Device.osVersion);

  // Must be a physical device for iOS (simulators < iOS 16.4 don't get push tokens reliably). Android emulators work fine.
  if (!Device.isDevice && Platform.OS === 'ios') {
    console.log('[FCM DEBUG] Skipping — not a real device (iOS Simulator)');
    return null;
  }

  // Setup Android channels first
  console.log('[FCM DEBUG] Setting up Android notification channels...');
  await setupAndroidChannel();
  console.log('[FCM DEBUG] Android channels setup done');

  // Ask for permission
  const { status: existingStatus } = await Notifications.getPermissionsAsync();
  console.log('[FCM DEBUG] Existing notification permission status:', existingStatus);
  let finalStatus = existingStatus;

  if (existingStatus !== 'granted') {
    console.log('[FCM DEBUG] Requesting notification permission...');
    const { status } = await Notifications.requestPermissionsAsync();
    finalStatus = status;
    console.log('[FCM DEBUG] Permission request result:', status);
  }

  if (finalStatus !== 'granted') {
    console.warn('[FCM DEBUG] ❌ Permission DENIED — notifications will not work.');
    return null;
  }

  console.log('[FCM DEBUG] ✅ Permission granted');

  try {
    console.log('[FCM DEBUG] Calling getDevicePushTokenAsync()...');
    const tokenData = await Notifications.getDevicePushTokenAsync();
    console.log('[FCM DEBUG] Token type:', tokenData.type);
    console.log('[FCM DEBUG] Raw token data:', JSON.stringify(tokenData.data));

    // On Android tokenData.data is a string (FCM token).
    // On iOS tokenData.data is an object { deviceToken: string }.
    const fcmToken: string = typeof tokenData.data === 'string'
      ? tokenData.data
      : (tokenData.data as any)?.deviceToken ?? '';

    if (!fcmToken) {
      console.error('[FCM DEBUG] ❌ Could not extract token string from tokenData:', JSON.stringify(tokenData));
      return null;
    }

    console.log('[FCM DEBUG] ✅ Token obtained (first 30 chars):', fcmToken.substring(0, 30) + '...');

    // Register token with backend if user is authenticated
    if (api.getToken()) {
      const backendOk = await sendTokenToBackend(fcmToken);
      if (!backendOk) {
        console.warn('[FCM DEBUG] ⚠️ Device token was generated but could not be saved to the server.');
      }
    } else {
      console.log('[FCM DEBUG] User not authenticated yet — backend token registration deferred until login.');
    }

    return fcmToken;
  } catch (e: any) {
    console.error('[FCM DEBUG] ❌ Failed to get device push token:', e?.message ?? e);
    const errorMessage = e?.message || JSON.stringify(e);
    
    // Ignore transient SERVICE_NOT_AVAILABLE errors as push notifications 
    // often still work due to previous successful token registrations.
    if (!errorMessage.includes('SERVICE_NOT_AVAILABLE')) {
      console.warn('[FCM DEBUG] ⚠️ Push notification token generation error:', errorMessage);
    }
    return null;
  }
}

// ── Send token to Laravel backend ────────────────────────────────────────────
// Returns true on success, false on failure. Never throws.
async function sendTokenToBackend(token: string): Promise<boolean> {
  const authToken = api.getToken();
  if (!authToken) {
    console.log('[FCM DEBUG] Skipping sendTokenToBackend — no auth token available.');
    return false;
  }

  const payload = {
    token,
    platform: Platform.OS,
    device_id: Device.modelName ?? undefined,
  };
  const endpoint = '/v1/mobile/auth/fcm-token';
  console.log('[FCM DEBUG] Sending token to backend:', api.getBaseUrl() + endpoint);
  console.log('[FCM DEBUG] Payload:', JSON.stringify({ ...payload, token: token.substring(0, 20) + '...' }));
  try {
    const response = await api.post(endpoint, payload);
    console.log('[FCM DEBUG] ✅ Token registered with backend. Response:', JSON.stringify(response));
    return true;
  } catch (e: any) {
    console.error('[FCM DEBUG] ❌ Failed to register token with backend');
    console.error('[FCM DEBUG] Error:', e?.message ?? e);
    console.error('[FCM DEBUG] Status:', e?.status);
    console.error('[FCM DEBUG] Base URL was:', api.getBaseUrl());
    console.error('[FCM DEBUG] Full error:', JSON.stringify(e));
    return false;
  }
}

// ── Remove token on logout ───────────────────────────────────────────────────
export async function unregisterPushNotifications(token?: string | null) {
  if (!token) return;
  try {
    await api.post('/v1/mobile/auth/fcm-token/remove', { token });
  } catch (_) {}
}

// ── Notification data shape sent from Laravel ────────────────────────────────
export interface NotificationPayload {
  type?: 'new_message' | 'new_conversation' | 'call_incoming' | string;
  conversation_id?: string;
  contact_id?: string;
  contact_name?: string;
  call_id?: string;
}

// ── Hook: handle tap on notification (foreground + background + killed) ──────
// Pass the navigationRef from AppNavigator so we can navigate from outside React.
export function useNotificationNavigation(navRef: any) {
  const notificationListener = useRef<Notifications.EventSubscription | null>(null);
  const responseListener = useRef<Notifications.EventSubscription | null>(null);

  useEffect(() => {
    // Foreground notification received (just logging — handler above shows it)
    notificationListener.current = Notifications.addNotificationReceivedListener(
      (notification: any) => {
        console.log('[FCM] Foreground notification:', notification.request.content.data);
      }
    );

    // User tapped a notification (works from background or killed state)
    responseListener.current = Notifications.addNotificationResponseReceivedListener(
      (response: any) => {
        const data = response.notification.request.content.data as NotificationPayload;
        handleNotificationTap(data, navRef);
      }
    );

    // Check if app was opened from a killed-state notification tap.
    // Retry with backoff until nav is ready — it may not be mounted yet.
    Notifications.getLastNotificationResponseAsync().then((response: any) => {
      if (!response) return;
      const data = response.notification.request.content.data as NotificationPayload;
      let attempts = 0;
      const tryNavigate = () => {
        if (navRef?.isReady?.()) {
          handleNotificationTap(data, navRef);
        } else if (attempts < 10) {
          attempts++;
          setTimeout(tryNavigate, 300);
        }
      };
      tryNavigate();
    });

    return () => {
      notificationListener.current?.remove();
      responseListener.current?.remove();
    };
  }, [navRef]);
}

// ── Navigate based on notification type ─────────────────────────────────────
async function handleNotificationTap(data: NotificationPayload, navRef: any) {
  if (!data || !navRef?.isReady?.()) return;

  // Do not navigate to protected screens if developer mode is blocking execution
  const isBlocked = await securityService.shouldBlockExecution();
  if (isBlocked) {
    console.log('[FCM] Developer mode active — skipping notification navigation.');
    return;
  }

  // Do not navigate to protected screens if the user is not authenticated
  const authToken = api.getToken();
  if (!authToken) {
    console.log('[FCM] Skipping notification navigation — user is not authenticated.');
    return;
  }

  switch (data.type) {
    case 'new_message':
    case 'new_conversation':
      if (data.conversation_id) {
        // Chat screen expects { conversation: Conversation } — fetch a minimal
        // stub so the screen can load. It will hydrate from the API on mount.
        navRef.navigate('Chat', {
          conversation: {
            id: Number(data.conversation_id),
            contact_id: Number(data.contact_id ?? 0),
            name: data.contact_name ?? 'Chat',
            last: '',
            time: '',
            unread: 0,
            phone: '',
            status: 'sent' as const,
          },
        });
      }
      break;

    case 'call_incoming':
      // CallOverlayManager already polls active calls — the overlay
      // will surface automatically. Just bring the app to home.
      navRef.navigate('Main');
      break;

    default:
      if (data.type) {
        navRef.navigate('Main');
      }
      break;
  }
}
