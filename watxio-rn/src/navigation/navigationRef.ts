import { createNavigationContainerRef } from '@react-navigation/native';
import type { RootStackParamList } from '@/types';

export const navigationRef = createNavigationContainerRef<RootStackParamList>();

export function safeGoBack(nav?: any, fallbackRoute: keyof RootStackParamList = 'Main') {
  if (nav && typeof nav.canGoBack === 'function' && nav.canGoBack()) {
    nav.goBack();
    return;
  }
  if (navigationRef.isReady() && navigationRef.canGoBack()) {
    navigationRef.goBack();
    return;
  }
  if (nav && typeof nav.navigate === 'function') {
    nav.navigate(fallbackRoute as any);
    return;
  }
  if (navigationRef.isReady()) {
    navigationRef.navigate(fallbackRoute as any);
  }
}
