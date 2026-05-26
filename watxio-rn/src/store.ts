import { useState, useEffect } from 'react';
import { Platform, NativeModules } from 'react-native';
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
  const scriptURL = NativeModules.SourceCode?.scriptURL;
  if (scriptURL) {
    const match = scriptURL.match(/^https?:\/\/([^:/]+)/);
    if (match && match[1]) {
      return match[1];
    }
  }
  return Platform.OS === 'android' ? '10.0.2.2' : 'localhost';
};

const defaultBaseUrl = `http://${getDevMachineIp()}:8000/api`;

const state: GlobalState = {
  token: null,
  baseUrl: defaultBaseUrl,
  activeTeamId: null,
  waNumber: '+1 (415) 555-0118',
  businessName: 'Acme Coffee Roasters',
  plan: 'Business · Tier 2',
  userName: 'Naveen A.',
  userRole: 'Founder · Watxio',
  user: null,
  teams: [],
  numbers: [],
};

// Initialize API client with default baseUrl and token
api.setBaseUrl(state.baseUrl);
api.setToken(state.token);

// Global 401 callback
api.onUnauthorized(() => {
  store.set({
    token: null,
    user: null,
    teams: [],
    numbers: [],
    activeTeamId: null,
  });
  if (navigationRef.isReady()) {
    navigationRef.reset({
      index: 0,
      routes: [{ name: 'Onboarding' }],
    });
  }
});

const STORAGE_KEY = '@watxio_session';

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

export const store = {
  get: (): GlobalState => state,
  set: (updates: Partial<GlobalState>) => {
    Object.assign(state, updates);
    
    // Sync to API networking instance
    if (updates.token !== undefined) {
      api.setToken(updates.token);
    }
    if (updates.baseUrl !== undefined) {
      api.setBaseUrl(updates.baseUrl);
    }
    if (updates.activeTeamId !== undefined) {
      api.setTeamId(updates.activeTeamId);
    }

    listeners.forEach((l) => l());

    // Asynchronously persist session
    persistState(state);
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
          store.set({
            token: data.token,
            baseUrl: data.baseUrl || state.baseUrl,
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

