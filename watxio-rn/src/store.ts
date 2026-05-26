import { useState, useEffect } from 'react';

export interface GlobalState {
  waNumber: string;
  businessName: string;
  plan: string;
  userName: string;
  userRole: string;
}

const state: GlobalState = {
  waNumber: '+1 (415) 555-0118',
  businessName: 'Acme Coffee Roasters',
  plan: 'Business · Tier 2',
  userName: 'Naveen A.',
  userRole: 'Founder · Watxio',
};

const listeners = new Set<() => void>();

export const store = {
  get: (): GlobalState => state,
  set: (updates: Partial<GlobalState>) => {
    Object.assign(state, updates);
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
