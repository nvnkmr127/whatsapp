import React from 'react';
import { View, Text } from 'react-native';
import { WifiOff } from 'lucide-react-native';
import { useNetworkStatus } from '@/hooks/useNetworkStatus';

export function OfflineBanner() {
  const isOnline = useNetworkStatus();
  if (isOnline) return null;
  return (
    <View className="flex-row items-center justify-center gap-2 bg-amber-500 py-2 px-4">
      <WifiOff size={14} color="#fff" />
      <Text className="text-white text-xs font-bold">No internet · Showing cached data</Text>
    </View>
  );
}
