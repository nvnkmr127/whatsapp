// src/services/security.ts — Mobile security integrity & developer mode checks
import { NativeModules, Platform, BackHandler } from 'react-native';

const { SecurityCheckModule } = NativeModules;

let testModeOverride: boolean | null = null;

export const securityService = {
  /**
   * Checks if Developer Options or USB Debugging is turned on at the device OS level.
   * Returns false on non-Android platforms or in case of bridge error.
   */
  async isDeveloperModeEnabled(): Promise<boolean> {
    if (testModeOverride !== null) {
      return testModeOverride;
    }

    if (Platform.OS !== 'android') {
      return false;
    }

    try {
      if (SecurityCheckModule && typeof SecurityCheckModule.isDeveloperModeEnabled === 'function') {
        const isEnabled = await SecurityCheckModule.isDeveloperModeEnabled();
        return Boolean(isEnabled);
      }
    } catch (err) {
      console.warn('[SecurityService] Failed to check developer mode status:', err);
    }
    return false;
  },

  /**
   * Determines if the developer mode security check should be actively enforced.
   * By default, it is enforced in production builds (!__DEV__).
   * If a test mode override is active, it respects the override for testing purposes.
   */
  async shouldBlockExecution(): Promise<boolean> {
    if (testModeOverride !== null) {
      return testModeOverride;
    }

    // In local development (__DEV__ === true), developers require USB debugging/ADB.
    // In production (!__DEV__), we enforce strict security checks.
    if (__DEV__) {
      return false;
    }

    return await this.isDeveloperModeEnabled();
  },

  /**
   * Opens Android Developer Options or Device System Settings directly
   * so the user can easily turn off Developer Options.
   */
  async openDeveloperSettings(): Promise<boolean> {
    if (Platform.OS !== 'android') {
      return false;
    }

    try {
      if (SecurityCheckModule && typeof SecurityCheckModule.openDeveloperSettings === 'function') {
        return await SecurityCheckModule.openDeveloperSettings();
      }
    } catch (err) {
      console.warn('[SecurityService] Failed to open developer settings:', err);
    }
    return false;
  },

  /**
   * Terminates/exits the application safely.
   */
  exitApp(): void {
    try {
      BackHandler.exitApp();
    } catch (err) {
      console.warn('[SecurityService] Failed to exit app via BackHandler:', err);
    }
  },

  /**
   * For automated testing or debug preview only: set an override for developer mode detection.
   */
  setTestModeOverride(override: boolean | null): void {
    testModeOverride = override;
  },
};
