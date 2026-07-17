import React from 'react';
import { View } from 'react-native';
import { Skeleton } from './Skeleton';

export function ChatSkeleton() {
  return (
    <View className="flex-1 px-4 py-4 gap-4">
      {/* Inbound 1 */}
      <View className="max-w-[78%] self-start relative">
        <View 
          className="overflow-hidden bg-bubble-in dark:bg-d-bubble-in shadow-sm"
          style={{ borderTopLeftRadius: 18, borderTopRightRadius: 18, borderBottomLeftRadius: 4, borderBottomRightRadius: 18 }}
        >
          <View className="py-3 px-3 gap-1.5">
            <Skeleton width={140} height={12} borderRadius={4} />
          </View>
          <View className="flex-row items-center justify-end gap-1 px-3 pb-2">
            <Skeleton width={30} height={8} borderRadius={3} />
          </View>
        </View>
      </View>

      {/* Outbound 1 */}
      <View className="max-w-[78%] self-end relative">
        <View 
          className="overflow-hidden bg-bubble-out dark:bg-d-bubble-out shadow-sm"
          style={{ borderTopLeftRadius: 18, borderTopRightRadius: 18, borderBottomLeftRadius: 18, borderBottomRightRadius: 4 }}
        >
          <View className="py-3 px-3 gap-1.5">
            <Skeleton width={180} height={12} borderRadius={4} />
            <Skeleton width={110} height={12} borderRadius={4} />
          </View>
          <View className="flex-row items-center justify-end gap-1 px-3 pb-2">
            <Skeleton width={45} height={8} borderRadius={3} />
          </View>
        </View>
      </View>

      {/* Inbound 2 */}
      <View className="max-w-[78%] self-start relative">
        <View 
          className="overflow-hidden bg-bubble-in dark:bg-d-bubble-in shadow-sm"
          style={{ borderTopLeftRadius: 18, borderTopRightRadius: 18, borderBottomLeftRadius: 4, borderBottomRightRadius: 18 }}
        >
          <View className="py-3 px-3 gap-1.5">
            <Skeleton width={220} height={12} borderRadius={4} />
            <Skeleton width={160} height={12} borderRadius={4} />
            <Skeleton width={100} height={12} borderRadius={4} />
          </View>
          <View className="flex-row items-center justify-end gap-1 px-3 pb-2">
            <Skeleton width={30} height={8} borderRadius={3} />
          </View>
        </View>
      </View>

      {/* Outbound 2 */}
      <View className="max-w-[78%] self-end relative">
        <View 
          className="overflow-hidden bg-bubble-out dark:bg-d-bubble-out shadow-sm"
          style={{ borderTopLeftRadius: 18, borderTopRightRadius: 18, borderBottomLeftRadius: 18, borderBottomRightRadius: 4 }}
        >
          <View className="py-3 px-3 gap-1.5">
            <Skeleton width={200} height={12} borderRadius={4} />
            <Skeleton width={230} height={12} borderRadius={4} />
          </View>
          <View className="flex-row items-center justify-end gap-1 px-3 pb-2">
            <Skeleton width={45} height={8} borderRadius={3} />
          </View>
        </View>
      </View>

      {/* Inbound 3 */}
      <View className="max-w-[78%] self-start relative">
        <View 
          className="overflow-hidden bg-bubble-in dark:bg-d-bubble-in shadow-sm"
          style={{ borderTopLeftRadius: 18, borderTopRightRadius: 18, borderBottomLeftRadius: 4, borderBottomRightRadius: 18 }}
        >
          <View className="py-3 px-3 gap-1.5">
            <Skeleton width={90} height={12} borderRadius={4} />
          </View>
          <View className="flex-row items-center justify-end gap-1 px-3 pb-2">
            <Skeleton width={30} height={8} borderRadius={3} />
          </View>
        </View>
      </View>
    </View>
  );
}
