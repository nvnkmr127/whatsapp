import { useState, useEffect } from 'react';
import { Platform, NativeModules } from 'react-native';
import Constants from 'expo-constants';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { api } from './services/api';
import { navigationRef } from './navigation/navigationRef';

export interface TeamInfo {
  id: number;
  name: string;
}

export interface NumberInfo {
  id: number | string;
  display_number: string;
  verified_name: string;
}

export interface UserInfo {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  role: string;
}

export interface WebsocketConfig {
  key: string;
  host: string;
  port: number | string;
  scheme: string;
}

export interface GlobalState {
  token: string | null;
  baseUrl: string;
  activeTeamId: number | null;
  waNumber: string;
  businessName: string;
  plan: string;
  userName: string;
  userRole: string;
  user: UserInfo | null;
  teams: TeamInfo[];
  numbers: NumberInfo[];
  websocket?: WebsocketConfig;
}

const getDevMachineIp = () => {
  const hostUri = Constants.expoConfig?.hostUri || Constants.hostUri;
  if (hostUri) {
    const ip = hostUri.split(':')[0];
    if (ip && ip !== 'localhost' && ip !== '127.0.0.1') {
      return ip;
    }
  }

  const scriptURL = NativeModules.SourceCode?.scriptURL;
  if (scriptURL) {
    const match = scriptURL.match(/^https?:\/\/([^:/]+)/);
    if (match && match[1] && match[1] !== 'localhost' && match[1] !== '127.0.0.1') {
      return match[1];
    }
  }

  return '192.168.31.52';
};

const defaultBaseUrl = 'https://flow.watxio.com/api';

const state: GlobalState = {
  token: null,
  baseUrl: defaultBaseUrl,
  activeTeamId: null,
  waNumber: '',
  businessName: '',
  plan: 'Business Plan',
  userName: '',
  userRole: '',
  user: null,
  teams: [],
  numbers: [],
};

// Initialize API client with default baseUrl and token
api.setBaseUrl(state.baseUrl);
api.setToken(state.token);

const STORAGE_KEY = '@watxio_session';

// Global 401 callback — immediate unauthenticated session wipe and redirect to Onboarding
api.onUnauthorized(() => {
  console.warn('[Auth] 401 Unauthenticated received — clearing session and returning to login.');
  store.clearSession();
  if (navigationRef.isReady()) {
    navigationRef.reset({ index: 0, routes: [{ name: 'Onboarding' }] });
  }
});

async function persistState(data: GlobalState) {
  try {
    if (data.token === null) {
      await AsyncStorage.removeItem(STORAGE_KEY);
    } else {
      const serialized = JSON.stringify({
        token: data.token,
        baseUrl: data.baseUrl,
        activeTeamId: data.activeTeamId,
        waNumber: data.waNumber,
        businessName: data.businessName,
        plan: data.plan,
        userName: data.userName,
        userRole: data.userRole,
        user: data.user,
        teams: data.teams,
        numbers: data.numbers,
        websocket: data.websocket,
      });
      await AsyncStorage.setItem(STORAGE_KEY, serialized);
    }
  } catch (e) {
    console.error('Failed to persist session state:', e);
  }
}

const listeners = new Set<() => void>();

// Debounce persistState so rapid store.set() bursts (e.g. WS message floods)
// only trigger one AsyncStorage write after activity settles.
let persistTimer: ReturnType<typeof setTimeout> | null = null;
function schedulePersist() {
  if (persistTimer) clearTimeout(persistTimer);
  persistTimer = setTimeout(() => {
    persistTimer = null;
    persistState(state);
  }, 1000);
}

export const store = {
  get: (): GlobalState => state,
  set: (updates: Partial<GlobalState>) => {
    if (!updates.baseUrl && (!__DEV__ || updates.token === null)) {
      updates.baseUrl = defaultBaseUrl;
    }
    Object.assign(state, updates);

    // Sync to API networking instance
    if (updates.token !== undefined) {
      api.setToken(updates.token);
      if (updates.token === null) {
        // Synchronously wipe storage on logout/invalidation to avoid race condition with app reload
        AsyncStorage.removeItem(STORAGE_KEY).catch((e) =>
          console.error('Failed to immediately remove session key:', e)
        );
      }
    }
    if (updates.baseUrl !== undefined) {
      api.setBaseUrl(updates.baseUrl);
    }
    if (updates.activeTeamId !== undefined) {
      api.setTeamId(updates.activeTeamId);
    }

    listeners.forEach((l) => l());

    // Debounced persist for active state updates
    schedulePersist();
  },
  clearSession: async () => {
    store.set({
      token: null,
      user: null,
      teams: [],
      numbers: [],
      activeTeamId: null,
      userName: '',
      userRole: '',
      businessName: '',
      waNumber: '',
      websocket: undefined,
    });
    try {
      await AsyncStorage.removeItem(STORAGE_KEY);
    } catch (e) {
      console.error('Failed to clear session storage:', e);
    }
  },
  subscribe: (listener: () => void) => {
    listeners.add(listener);
    return () => {
      listeners.delete(listener);
    };
  },
  loadSession: async (): Promise<boolean> => {
    try {
      const serialized = await AsyncStorage.getItem(STORAGE_KEY);
      if (serialized) {
        const data = JSON.parse(serialized);
        if (data && data.token) {
          // Validate cached websocket config — wipe it if it looks like a dev/stale
          // value (localhost/127.0.0.1 host or null key) so the next login fetches fresh.
          const ws = data.websocket;
          const wsIsStale = !ws?.key
            || ws?.host === '127.0.0.1'
            || ws?.host === 'localhost';
          if (wsIsStale) {
            console.warn('[Session] Stale websocket config detected — clearing cached ws config. Will refresh on next API call.');
            data.websocket = undefined;
          }

          let activeBaseUrl = data.baseUrl || defaultBaseUrl;

          if (__DEV__ && data.baseUrl) {
            const devIp = getDevMachineIp();
            activeBaseUrl = data.baseUrl
              .replace('localhost', devIp)
              .replace('127.0.0.1', devIp)
              .replace('flow.watxio.com', devIp);
            if (!activeBaseUrl.includes(':8000') && !activeBaseUrl.includes(':8081')) {
              activeBaseUrl = `http://${devIp}:8000/api`;
            }
          }

          store.set({
            token: data.token,
            baseUrl: activeBaseUrl,
            activeTeamId: data.activeTeamId !== undefined ? data.activeTeamId : state.activeTeamId,
            waNumber: data.waNumber || state.waNumber,
            businessName: data.businessName || state.businessName,
            plan: data.plan || state.plan,
            userName: data.userName || state.userName,
            userRole: data.userRole || state.userRole,
            user: data.user || state.user,
            teams: data.teams || state.teams,
            numbers: data.numbers || state.numbers,
            websocket: data.websocket || state.websocket,
          });
          return true;
        }
      }
    } catch (e) {
      console.error('Failed to load session state:', e);
    }
    return false;
  },
};

export function useGlobalState() {
  const [data, setData] = useState<GlobalState>({ ...state });
  useEffect(() => {
    return store.subscribe(() => {
      setData({ ...store.get() });
    });
  }, []);
  return [data, store.set] as const;
}

