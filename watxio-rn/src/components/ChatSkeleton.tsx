import React from 'react';
import { View, ScrollView } from 'react-native';
import { Skeleton } from './Skeleton';

export function ChatSkeleton() {
  return (
    <ScrollView className="flex-1 px-3 py-3" contentContainerStyle={{ gap: 12 }} showsVerticalScrollIndicator={false}>
      {/* Date Pill Skeleton */}
      <View className="self-center my-1">
        <Skeleton width={70} height={20} borderRadius={10} />
      </View>

      {/* Inbound Message 1 (Text) */}
      <View className="max-w-[78%] self-start relative">
        <View 
          className="overflow-hidden bg-bubble-in dark:bg-d-bubble-in p-3.5 gap-2 shadow-sm"
          style={{ borderTopLeftRadius: 18, borderTopRightRadius: 18, borderBottomLeftRadius: 4, borderBottomRightRadius: 18 }}
        >
          <Skeleton width={160} height={12} borderRadius={4} />
          <Skeleton width={110} height={12} borderRadius={4} />
          <View className="flex-row justify-end mt-1">
            <Skeleton width={32} height={8} borderRadius={3} />
          </View>
        </View>
      </View>

      {/* Outbound Message 1 (Text) */}
      <View className="max-w-[78%] self-end relative">
        <View 
          className="overflow-hidden bg-bubble-out dark:bg-d-bubble-out p-3.5 gap-2 shadow-sm"
          style={{ borderTopLeftRadius: 18, borderTopRightRadius: 18, borderBottomLeftRadius: 18, borderBottomRightRadius: 4 }}
        >
          <Skeleton width={200} height={12} borderRadius={4} />
          <Skeleton width={140} height={12} borderRadius={4} />
          <View className="flex-row justify-end items-center gap-1 mt-1">
            <Skeleton width={36} height={8} borderRadius={3} />
            <Skeleton width={12} height={8} borderRadius={3} />
          </View>
        </View>
      </View>

      {/* Inbound Message 2 (Photo/Media Skeleton) */}
      <View className="max-w-[78%] self-start relative">
        <View 
          className="overflow-hidden bg-bubble-in dark:bg-d-bubble-in p-1.5 gap-2 shadow-sm"
          style={{ borderTopLeftRadius: 18, borderTopRightRadius: 18, borderBottomLeftRadius: 4, borderBottomRightRadius: 18 }}
        >
          <Skeleton width={240} height={150} borderRadius={14} />
          <View className="px-2 pb-1 gap-1.5">
            <Skeleton width={130} height={11} borderRadius={4} />
            <View className="flex-row justify-end">
              <Skeleton width={32} height={8} borderRadius={3} />
            </View>
          </View>
        </View>
      </View>

      {/* Outbound Message 2 (Short Text) */}
      <View className="max-w-[78%] self-end relative">
        <View 
          className="overflow-hidden bg-bubble-out dark:bg-d-bubble-out p-3.5 gap-2 shadow-sm"
          style={{ borderTopLeftRadius: 18, borderTopRightRadius: 18, borderBottomLeftRadius: 18, borderBottomRightRadius: 4 }}
        >
          <Skeleton width={120} height={12} borderRadius={4} />
          <View className="flex-row justify-end items-center gap-1 mt-1">
            <Skeleton width={36} height={8} borderRadius={3} />
            <Skeleton width={12} height={8} borderRadius={3} />
          </View>
        </View>
      </View>

      {/* Inbound Message 3 (Voice Note / Audio Skeleton) */}
      <View className="max-w-[78%] self-start relative">
        <View 
          className="overflow-hidden bg-bubble-in dark:bg-d-bubble-in p-3 shadow-sm flex-row items-center gap-3"
          style={{ borderTopLeftRadius: 18, borderTopRightRadius: 18, borderBottomLeftRadius: 4, borderBottomRightRadius: 18 }}
        >
          <Skeleton width={38} height={38} borderRadius={19} />
          <View className="gap-2 flex-1">
            <Skeleton width={130} height={14} borderRadius={6} />
            <Skeleton width={40} height={8} borderRadius={3} />
          </View>
        </View>
      </View>

      {/* Outbound Message 3 (Long Text) */}
      <View className="max-w-[78%] self-end relative">
        <View 
          className="overflow-hidden bg-bubble-out dark:bg-d-bubble-out p-3.5 gap-2 shadow-sm"
          style={{ borderTopLeftRadius: 18, borderTopRightRadius: 18, borderBottomLeftRadius: 18, borderBottomRightRadius: 4 }}
        >
          <Skeleton width={210} height={12} borderRadius={4} />
          <Skeleton width={170} height={12} borderRadius={4} />
          <Skeleton width={90} height={12} borderRadius={4} />
          <View className="flex-row justify-end items-center gap-1 mt-1">
            <Skeleton width={36} height={8} borderRadius={3} />
            <Skeleton width={12} height={8} borderRadius={3} />
          </View>
        </View>
      </View>
    </ScrollView>
  );
}
