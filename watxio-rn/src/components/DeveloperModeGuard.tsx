// src/components/DeveloperModeGuard.tsx — Security Guard that detects Developer Options in production
// and presents a high-priority warning dialog with an Exit button matching Watxio's design language.

import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  Modal,
  Pressable,
  AppState,
  AppStateStatus,
  BackHandler,
  Platform,
  ActivityIndicator,
} from 'react-native';
import { ShieldAlert, LogOut, Settings, AlertTriangle, ArrowRight } from 'lucide-react-native';
import { useTokens } from '@/theme';
import { securityService } from '@/services/security';

interface DeveloperModeGuardProps {
  children?: React.ReactNode;
}

export function DeveloperModeGuard({ children }: DeveloperModeGuardProps) {
  const { tokens, scheme } = useTokens();
  const [isBlocked, setIsBlocked] = useState<boolean>(false);
  const [isChecking, setIsChecking] = useState<boolean>(true);
  const isDark = scheme === 'dark';

  const checkSecurity = useCallback(async () => {
    try {
      const blocked = await securityService.shouldBlockExecution();
      setIsBlocked(blocked);
    } catch (err) {
      console.warn('[DeveloperModeGuard] Security check error:', err);
      setIsBlocked(false);
    } finally {
      setIsChecking(false);
    }
  }, []);

  useEffect(() => {
    // Initial check on mount
    checkSecurity();

    // Re-check automatically whenever the app transitions back to active
    // (e.g., after the user went to Settings and disabled Developer Options)
    const subscription = AppState.addEventListener('change', (nextAppState: AppStateStatus) => {
      if (nextAppState === 'active') {
        checkSecurity();
      }
    });

    return () => {
      subscription.remove();
    };
  }, [checkSecurity]);

  // Intercept Android hardware back button when the security block is active
  useEffect(() => {
    if (!isBlocked) return;

    const backHandler = BackHandler.addEventListener('hardwareBackPress', () => {
      // Exit app directly instead of letting user bypass the modal
      securityService.exitApp();
      return true;
    });

    return () => {
      backHandler.remove();
    };
  }, [isBlocked]);

  const handleExitApp = () => {
    securityService.exitApp();
  };

  const handleOpenSettings = async () => {
    await securityService.openDeveloperSettings();
  };

  return (
    <>
      {children}

      <Modal
        visible={isBlocked}
        transparent
        animationType="fade"
        statusBarTranslucent
        onRequestClose={handleExitApp}
      >
        <View className="flex-1 bg-black/80 items-center justify-center px-5">
          {/* Main Security Card */}
          <View
            className="w-full max-w-[340px] rounded-3xl p-6 bg-surface dark:bg-d-surface border border-hairline dark:border-d-hairline shadow-2xl items-center"
            style={{
              shadowColor: '#000000',
              shadowOffset: { width: 0, height: 10 },
              shadowOpacity: 0.35,
              shadowRadius: 20,
              elevation: 12,
            }}
          >
            {/* Warning Shield Icon with ambient halo */}
            <View className="relative items-center justify-center mb-4">
              <View
                className="w-16 h-16 rounded-full items-center justify-center"
                style={{
                  backgroundColor: isDark ? 'rgba(213, 143, 132, 0.15)' : 'rgba(180, 88, 76, 0.12)',
                }}
              >
                <View
                  className="w-12 h-12 rounded-full items-center justify-center"
                  style={{
                    backgroundColor: isDark ? 'rgba(213, 143, 132, 0.25)' : 'rgba(180, 88, 76, 0.20)',
                  }}
                >
                  <ShieldAlert size={28} color={tokens.danger} />
                </View>
              </View>
            </View>

            {/* Security Pill Badge */}
            <View
              className="px-3 py-1 rounded-full mb-3 flex-row items-center gap-1.5"
              style={{
                backgroundColor: isDark ? 'rgba(213, 143, 132, 0.15)' : 'rgba(180, 88, 76, 0.10)',
              }}
            >
              <AlertTriangle size={12} color={tokens.danger} />
              <Text
                className="text-[11px] font-bold uppercase tracking-wider"
                style={{ color: tokens.danger }}
              >
                Security Warning
              </Text>
            </View>

            {/* Title */}
            <Text className="text-[18px] font-bold text-ink dark:text-d-ink text-center mb-2 leading-snug">
              Developer Options Enabled
            </Text>

            {/* Explanatory Message */}
            <Text className="text-[13px] text-ink2 dark:text-d-ink2 text-center leading-relaxed mb-4">
              For your account safety and to protect sensitive business chats, Watxio cannot run while
              Developer Mode or USB Debugging is active on this device.
            </Text>

            {/* Instruction Checklist Box */}
            <View className="w-full bg-surface2 dark:bg-d-surface2 rounded-xl p-3.5 mb-5 border border-hairline dark:border-d-hairline">
              <Text className="text-[12px] font-semibold text-ink dark:text-d-ink mb-1.5">
                How to resolve:
              </Text>
              <View className="gap-1.5">
                <View className="flex-row items-center gap-2">
                  <View className="w-1.5 h-1.5 rounded-full bg-accent dark:bg-d-accent" />
                  <Text className="text-[11.5px] text-ink2 dark:text-d-ink2 flex-1">
                    Open device Settings
                  </Text>
                </View>
                <View className="flex-row items-center gap-2">
                  <View className="w-1.5 h-1.5 rounded-full bg-accent dark:bg-d-accent" />
                  <Text className="text-[11.5px] text-ink2 dark:text-d-ink2 flex-1">
                    Turn off <Text className="font-semibold text-ink dark:text-d-ink">Developer Options</Text>
                  </Text>
                </View>
                <View className="flex-row items-center gap-2">
                  <View className="w-1.5 h-1.5 rounded-full bg-accent dark:bg-d-accent" />
                  <Text className="text-[11.5px] text-ink2 dark:text-d-ink2 flex-1">
                    Return to Watxio to continue
                  </Text>
                </View>
              </View>
            </View>

            {/* Action Buttons */}
            <View className="w-full gap-2.5">
              {/* Primary Danger Action: Exit App */}
              <Pressable
                onPress={handleExitApp}
                className="w-full py-3 px-4 rounded-xl flex-row items-center justify-center gap-2 active:opacity-90 shadow-sm"
                style={{ backgroundColor: tokens.danger }}
              >
                <LogOut size={16} color="#FFFFFF" />
                <Text className="text-[14px] font-bold text-white">
                  Exit Application
                </Text>
              </Pressable>

              {/* Secondary Helper: Open Device Settings (Android only) */}
              {Platform.OS === 'android' && (
                <Pressable
                  onPress={handleOpenSettings}
                  className="w-full py-2.5 px-4 rounded-xl flex-row items-center justify-center gap-2 active:opacity-80 bg-surface2 dark:bg-d-surface2 border border-hairline dark:border-d-hairline"
                >
                  <Settings size={15} color={tokens.ink2} />
                  <Text className="text-[13px] font-semibold text-ink dark:text-d-ink">
                    Open Device Settings
                  </Text>
                </Pressable>
              )}
            </View>
          </View>
        </View>
      </Modal>
    </>
  );
}
