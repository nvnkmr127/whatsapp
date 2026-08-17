// src/services/__tests__/security.test.ts

const mockSecurityCheckModule = {
  isDeveloperModeEnabled: jest.fn(),
  openDeveloperSettings: jest.fn(),
};

const mockBackHandler = {
  exitApp: jest.fn(),
  addEventListener: jest.fn(),
};

const mockPlatform = {
  OS: 'android',
};

jest.mock('react-native', () => ({
  Platform: mockPlatform,
  NativeModules: {
    SecurityCheckModule: mockSecurityCheckModule,
  },
  BackHandler: mockBackHandler,
}));

import { securityService } from '../security';

describe('securityService', () => {
  beforeEach(() => {
    securityService.setTestModeOverride(null);
    jest.clearAllMocks();
    mockPlatform.OS = 'android';
  });

  afterEach(() => {
    securityService.setTestModeOverride(null);
  });

  it('returns false for non-Android platforms', async () => {
    mockPlatform.OS = 'ios';
    const isDevMode = await securityService.isDeveloperModeEnabled();
    expect(isDevMode).toBe(false);
  });

  it('queries NativeModules.SecurityCheckModule on Android', async () => {
    mockPlatform.OS = 'android';
    mockSecurityCheckModule.isDeveloperModeEnabled.mockResolvedValueOnce(true);

    const isDevMode = await securityService.isDeveloperModeEnabled();
    expect(isDevMode).toBe(true);
    expect(mockSecurityCheckModule.isDeveloperModeEnabled).toHaveBeenCalledTimes(1);
  });

  it('handles bridge exceptions gracefully without crashing', async () => {
    mockPlatform.OS = 'android';
    mockSecurityCheckModule.isDeveloperModeEnabled.mockRejectedValueOnce(new Error('Bridge failure'));

    const isDevMode = await securityService.isDeveloperModeEnabled();
    expect(isDevMode).toBe(false);
  });

  it('should not block execution in __DEV__ environment unless overridden', async () => {
    (global as any).__DEV__ = true;
    mockPlatform.OS = 'android';
    mockSecurityCheckModule.isDeveloperModeEnabled.mockResolvedValueOnce(true);

    const blocked = await securityService.shouldBlockExecution();
    expect(blocked).toBe(false);
  });

  it('blocks execution when test override is set to true', async () => {
    securityService.setTestModeOverride(true);
    const blocked = await securityService.shouldBlockExecution();
    expect(blocked).toBe(true);
  });

  it('invokes openDeveloperSettings on NativeModules', async () => {
    mockPlatform.OS = 'android';
    mockSecurityCheckModule.openDeveloperSettings.mockResolvedValueOnce(true);

    const result = await securityService.openDeveloperSettings();
    expect(result).toBe(true);
    expect(mockSecurityCheckModule.openDeveloperSettings).toHaveBeenCalledTimes(1);
  });

  it('invokes BackHandler.exitApp on exitApp()', () => {
    securityService.exitApp();
    expect(mockBackHandler.exitApp).toHaveBeenCalledTimes(1);
  });
});
