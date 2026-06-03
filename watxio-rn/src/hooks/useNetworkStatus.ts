import { useState, useEffect } from 'react';
import NetInfo from '@react-native-community/netinfo';
import type { NetInfoState } from '@react-native-community/netinfo';

export function useNetworkStatus() {
  const [isOnline, setIsOnline] = useState(true);

  useEffect(() => {
    const unsub = NetInfo.addEventListener((state: NetInfoState) => {
      setIsOnline(!!(state.isConnected && state.isInternetReachable !== false));
    });
    NetInfo.fetch().then((state: NetInfoState) => {
      setIsOnline(!!(state.isConnected && state.isInternetReachable !== false));
    });
    return () => unsub();
  }, []);

  return isOnline;
}
