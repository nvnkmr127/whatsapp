import { useState, useEffect } from 'react';
import { Platform, NativeModules } from 'react-native';
import { api } from './services/api';

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
  },
  subscribe: (listener: () => void) => {
    listeners.add(listener);
    return () => {
      listeners.delete(listener);
    };
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

