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
import { store } from '@/store';
import { securityService } from '@/services/security';

// ── Foreground display behaviour ─────────────────────────────────────────────
// Show banner + sound ONLY when the user is logged in (like native WhatsApp).
Notifications.setNotificationHandler({
  handleNotification: async () => {
    const hasAuth = !!api.getToken() || !!store.get().token;
    if (!hasAuth) {
      console.log('[FCM] Notification suppressed — user is not logged in.');
      return {
        shouldPlaySound: false,
        shouldSetBadge: false,
        shouldShowBanner: false,
        shouldShowList: false,
      };
    }
    return {
      shouldPlaySound: true,
      shouldSetBadge: true,
      shouldShowBanner: true,
      shouldShowList: true,
    };
  },
});

// ── Android notification channel ─────────────────────────────────────────────
export async function setupAndroidChannel() {
  if (Platform.OS !== 'android') return;

  // Delete old channels first — Android ignores setNotificationChannelAsync
  // updates to sound/vibration if the channel already exists with stale settings.
  await Notifications.deleteNotificationChannelAsync('default').catch(() => {});
  await Notifications.deleteNotificationChannelAsync('calls').catch(() => {});

  await Notifications.setNotificationChannelAsync('default', {
    name: 'Watxio Messages',
    importance: Notifications.AndroidImportance.MAX,
    vibrationPattern: [0, 250, 250, 250],
    lightColor: '#2F8F6F',
    sound: 'default',
    enableVibrate: true,
    showBadge: true,
  });

  await Notifications.setNotificationChannelAsync('calls', {
    name: 'Incoming Calls',
    importance: Notifications.AndroidImportance.MAX,
    vibrationPattern: [0, 500, 500, 500],
    lightColor: '#2F8F6F',
    sound: 'default',
    enableVibrate: true,
    showBadge: false,
    bypassDnd: true,
  });
}

// ── Permission + token registration ─────────────────────────────────────────
export async function registerForPushNotifications(): Promise<string | null> {
  // Must be a physical device for iOS (simulators < iOS 16.4 don't get push tokens reliably). Android emulators work fine.
  if (!Device.isDevice && Platform.OS === 'ios') {
    return null;
  }

  // Setup Android channels first
  await setupAndroidChannel();

  // Ask for permission
  const { status: existingStatus } = await Notifications.getPermissionsAsync();
  let finalStatus = existingStatus;

  if (existingStatus !== 'granted') {
    const { status } = await Notifications.requestPermissionsAsync();
    finalStatus = status;
  }

  if (finalStatus !== 'granted') {
    console.warn('[FCM] Permission denied — notifications will not work.');
    return null;
  }

  try {
    const tokenData = await Notifications.getDevicePushTokenAsync();

    // On Android tokenData.data is a string (FCM token).
    // On iOS tokenData.data is an object { deviceToken: string }.
    const fcmToken: string = typeof tokenData.data === 'string'
      ? tokenData.data
      : (tokenData.data as any)?.deviceToken ?? '';

    if (!fcmToken) {
      console.error('[FCM] Could not extract token string from tokenData:', JSON.stringify(tokenData));
      return null;
    }

    // Save to global state so we know the token during session / logout
    store.set({ fcmToken });

    // Register token with backend if user is authenticated
    if (api.getToken()) {
      const backendOk = await sendTokenToBackend(fcmToken);
      if (!backendOk) {
        console.warn('[FCM] Device token was generated but could not be saved to the server.');
      }
    }

    return fcmToken;
  } catch (e: any) {
    const errorMessage = e?.message || JSON.stringify(e);

    // Ignore transient SERVICE_NOT_AVAILABLE errors as push notifications
    // often still work due to previous successful token registrations.
    if (!errorMessage.includes('SERVICE_NOT_AVAILABLE')) {
      console.warn('[FCM] Push notification token generation error:', errorMessage);
    }
    return null;
  }
}

// ── Send token to Laravel backend ────────────────────────────────────────────
// Returns true on success, false on failure. Never throws.
async function sendTokenToBackend(token: string): Promise<boolean> {
  const authToken = api.getToken();
  if (!authToken) {
    return false;
  }

  const payload = {
    token,
    platform: Platform.OS,
    device_id: Device.modelName ?? undefined,
  };
  try {
    await api.post('/v1/mobile/auth/fcm-token', payload);
    return true;
  } catch (e: any) {
    console.error('[FCM] Failed to register token with backend:', e?.message ?? e);
    return false;
  }
}

// ── Remove token on logout ───────────────────────────────────────────────────
export async function unregisterPushNotifications(token?: string | null) {
  const tokenToUnregister = token || store.get().fcmToken;
  if (tokenToUnregister) {
    try {
      await api.post('/v1/mobile/auth/fcm-token/remove', { token: tokenToUnregister });
    } catch (_) {}
  }
  try {
    await Notifications.dismissAllNotificationsAsync();
    await Notifications.setBadgeCountAsync(0);
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
    // Foreground notification received — handler above shows it.
    notificationListener.current = Notifications.addNotificationReceivedListener(
      () => {}
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
