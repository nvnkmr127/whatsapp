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
import { Platform, Alert } from 'react-native';
import * as Notifications from 'expo-notifications';
import * as Device from 'expo-device';
import { api } from '@/services/api';

// ── Foreground display behaviour ─────────────────────────────────────────────
// Show banner + sound even when the app is open (like WhatsApp).
Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowAlert: true,
    shouldPlaySound: true,
    shouldSetBadge: true,
    shouldShowBanner: true,
    shouldShowList: true,
  }),
});

// ── Android notification channel ─────────────────────────────────────────────
export async function setupAndroidChannel() {
  if (Platform.OS !== 'android') return;
  await Notifications.setNotificationChannelAsync('default', {
    name: 'Watxio',
    importance: Notifications.AndroidImportance.MAX,
    vibrationPattern: [0, 250, 250, 250],
    lightColor: '#2F8F6F',
    sound: 'default',
    enableVibrate: true,
    showBadge: true,
  });
  // Separate high-importance channel for incoming call alerts
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
  // Must be a physical device (simulators don't get push tokens)
  if (!Device.isDevice) {
    console.log('[FCM] Skipping — not a real device');
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
    console.log('[FCM] Permission denied');
    return null;
  }

  try {
    // getDevicePushTokenAsync returns the raw FCM token (Android) or APNs token (iOS).
    // This is what you send to your own backend — NOT the Expo push token.
    const tokenData = await Notifications.getDevicePushTokenAsync();
    const fcmToken = tokenData.data as string;

    console.log('[FCM] Token:', fcmToken.substring(0, 20) + '...');

    // Register token with backend
    await sendTokenToBackend(fcmToken);

    return fcmToken;
  } catch (e) {
    console.error('[FCM] Failed to get token:', e);
    return null;
  }
}

// ── Send token to Laravel backend ────────────────────────────────────────────
async function sendTokenToBackend(token: string) {
  try {
    await api.post('/v1/mobile/auth/fcm-token', {
      token,
      platform: Platform.OS, // 'ios' or 'android'
      device_id: Device.modelName ?? undefined,
    });
    console.log('[FCM] Token registered with backend');
  } catch (e) {
    console.error('[FCM] Failed to register token with backend:', e);
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
      (notification) => {
        console.log('[FCM] Foreground notification:', notification.request.content.data);
      }
    );

    // User tapped a notification (works from background or killed state)
    responseListener.current = Notifications.addNotificationResponseReceivedListener(
      (response) => {
        const data = response.notification.request.content.data as NotificationPayload;
        handleNotificationTap(data, navRef);
      }
    );

    // Check if app was opened from a killed-state notification tap
    Notifications.getLastNotificationResponseAsync().then((response) => {
      if (response) {
        const data = response.notification.request.content.data as NotificationPayload;
        handleNotificationTap(data, navRef);
      }
    });

    return () => {
      notificationListener.current?.remove();
      responseListener.current?.remove();
    };
  }, [navRef]);
}

// ── Navigate based on notification type ─────────────────────────────────────
function handleNotificationTap(data: NotificationPayload, navRef: any) {
  if (!data || !navRef?.isReady?.()) return;

  switch (data.type) {
    case 'new_message':
    case 'new_conversation':
      if (data.conversation_id) {
        navRef.navigate('Chat', {
          conversationId: Number(data.conversation_id),
          contactName: data.contact_name ?? 'Chat',
        });
      }
      break;

    case 'call_incoming':
      // CallOverlayManager already polls active calls — the overlay
      // will surface automatically. Just bring the app to home.
      navRef.navigate('Main');
      break;

    default:
      navRef.navigate('Main');
      break;
  }
}
