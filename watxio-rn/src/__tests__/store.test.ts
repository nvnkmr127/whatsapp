// src/__tests__/store.test.ts

import { store } from '../store';
import { api } from '../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';

// Mocks
jest.mock('react-native', () => ({
  Platform: { OS: 'android' },
  NativeModules: { SourceCode: { scriptURL: 'http://192.168.31.52:8081' } },
}));

jest.mock('expo-constants', () => ({
  __esModule: true,
  default: {
    expoConfig: { hostUri: '192.168.31.52:8081' },
    hostUri: '192.168.31.52:8081',
  },
}));

const mockStorage: Record<string, string> = {};
jest.mock('@react-native-async-storage/async-storage', () => ({
  setItem: jest.fn((key: string, value: string) => {
    mockStorage[key] = value;
    return Promise.resolve();
  }),
  getItem: jest.fn((key: string) => {
    return Promise.resolve(mockStorage[key] || null);
  }),
  removeItem: jest.fn((key: string) => {
    delete mockStorage[key];
    return Promise.resolve();
  }),
}));

describe('Global Store', () => {
  beforeEach(() => {
    Object.keys(mockStorage).forEach((key) => delete mockStorage[key]);
    jest.clearAllMocks();
  });

  test('store.get() returns initial state', () => {
    const currentState = store.get();
    expect(currentState).toBeDefined();
    expect(currentState.baseUrl).toBeDefined();
  });

  test('store.set() updates state and syncs to API client', () => {
    const apiTokenSpy = jest.spyOn(api, 'setToken');
    const apiTeamSpy = jest.spyOn(api, 'setTeamId');

    store.set({ token: 'new-test-token', activeTeamId: 42 });

    expect(store.get().token).toBe('new-test-token');
    expect(store.get().activeTeamId).toBe(42);
    expect(apiTokenSpy).toHaveBeenCalledWith('new-test-token');
    expect(apiTeamSpy).toHaveBeenCalledWith(42);

    apiTokenSpy.mockRestore();
    apiTeamSpy.mockRestore();
  });

  test('store.subscribe notifies listener on state change', () => {
    const listener = jest.fn();
    const unsubscribe = store.subscribe(listener);

    store.set({ businessName: 'Test Coffee Co.' });

    expect(listener).toHaveBeenCalledTimes(1);
    expect(store.get().businessName).toBe('Test Coffee Co.');

    unsubscribe();
    store.set({ businessName: 'Another Co.' });
    expect(listener).toHaveBeenCalledTimes(1); // Not called after unsubscribe
  });

  test('loadSession overrides stale cached URL in __DEV__ mode', async () => {
    mockStorage['@watxio_session'] = JSON.stringify({
      token: 'cached-token',
      baseUrl: 'https://flow.watxio.com/api',
      activeTeamId: 1,
    });

    const hasSession = await store.loadSession();

    expect(hasSession).toBe(true);
    expect(store.get().token).toBe('cached-token');
    // In __DEV__, flow.watxio.com is overridden with http://192.168.31.52:8000/api
    expect(store.get().baseUrl).toContain('192.168.31.52');
  });

  test('clearSession resets token and removes storage key', async () => {
    store.set({ token: 'active-token', activeTeamId: 123 });
    mockStorage['@watxio_session'] = JSON.stringify({ token: 'active-token' });

    await store.clearSession();

    expect(store.get().token).toBeNull();
    expect(store.get().activeTeamId).toBeNull();
    expect(AsyncStorage.removeItem).toHaveBeenCalledWith('@watxio_session');
  });
});
