import React from 'react';
import { View } from 'react-native';
import { Skeleton } from './Skeleton';

// ----------------------------------------------------
// DEFAULT INBOX LIST SKELETON
// ----------------------------------------------------
export function ListItemSkeleton({ divider = true }: { divider?: boolean }) {
  return (
    <View className={`flex-row items-center gap-3 py-[11px] px-[18px] ${divider ? 'border-b border-hairline dark:border-d-hairline' : ''}`}>
      <Skeleton width={44} height={44} borderRadius={22} />
      <View className="flex-1 justify-center gap-2">
        <View className="flex-row justify-between items-center">
          <Skeleton width={120} height={14} borderRadius={4} />
          <Skeleton width={40} height={12} borderRadius={4} />
        </View>
        <View className="flex-row items-center gap-2">
          <Skeleton width="70%" height={12} borderRadius={4} />
        </View>
      </View>
    </View>
  );
}

export function ListSkeleton({ count = 8 }: { count?: number }) {
  return (
    <View className="flex-1 overflow-hidden">
      {Array.from({ length: count }).map((_, i) => (
        <ListItemSkeleton key={i} divider={i < count - 1} />
      ))}
    </View>
  );
}

// ----------------------------------------------------
// BOTS LIST SKELETON
// ----------------------------------------------------
export function BotItemSkeleton() {
  return (
    <View className="bg-surface dark:bg-d-surface rounded-xl p-[14px] mb-3 border border-hairline dark:border-d-hairline shadow-sm mx-[18px]">
      <View className="flex-row justify-between items-start gap-2 mb-2">
        <View className="flex-1 min-w-0">
          <View className="flex-row items-center gap-1.5 mb-1.5">
            <Skeleton width={10} height={10} borderRadius={5} />
            <Skeleton width={100} height={14} borderRadius={4} />
          </View>
          <Skeleton width={80} height={10} borderRadius={4} />
        </View>
        <Skeleton width={40} height={24} borderRadius={12} />
      </View>

      <View className="flex-row flex-wrap gap-1.25 mb-2.5">
        <Skeleton width={50} height={18} borderRadius={9} />
        <Skeleton width={40} height={18} borderRadius={9} />
        <Skeleton width={60} height={18} borderRadius={9} />
      </View>

      <View className="bg-surface2 dark:bg-d-surface2 py-2.5 px-3 rounded-md mb-3.5 border border-hairline dark:border-d-hairline gap-1.5">
        <Skeleton width="100%" height={12} borderRadius={4} />
        <Skeleton width="90%" height={12} borderRadius={4} />
        <Skeleton width="60%" height={12} borderRadius={4} />
      </View>

      <View className="flex-row justify-between items-center pt-2.5 border-t border-hairline dark:border-d-hairline">
        <Skeleton width={90} height={12} borderRadius={4} />
        <View className="flex-row gap-2">
          <Skeleton width={50} height={24} borderRadius={4} />
          <Skeleton width={50} height={24} borderRadius={4} />
        </View>
      </View>
    </View>
  );
}

export function BotsListSkeleton({ count = 4 }: { count?: number }) {
  return (
    <View className="flex-1 overflow-hidden pt-3">
      {Array.from({ length: count }).map((_, i) => (
        <BotItemSkeleton key={i} />
      ))}
    </View>
  );
}

// ----------------------------------------------------
// CALLS LIST SKELETON
// ----------------------------------------------------
export function CallItemSkeleton() {
  return (
    <View className="mx-[18px] mb-2 px-3 py-3 rounded-lg bg-surface dark:bg-d-surface border border-hairline dark:border-d-hairline flex-row items-center gap-3">
      <Skeleton width={38} height={38} borderRadius={19} />
      <View className="flex-1 min-w-0 gap-1.5">
        <View className="flex-row items-center gap-1.5">
          <Skeleton width={100} height={14} borderRadius={4} />
          <Skeleton width={12} height={12} borderRadius={6} />
        </View>
        <Skeleton width={80} height={10} borderRadius={4} />
      </View>
      <View className="items-end gap-1.5">
        <Skeleton width={44} height={14} borderRadius={7} />
        <Skeleton width={30} height={10} borderRadius={4} />
        <Skeleton width={24} height={10} borderRadius={4} />
      </View>
    </View>
  );
}

export function CallsListSkeleton({ count = 6 }: { count?: number }) {
  return (
    <View className="flex-1 overflow-hidden pt-2">
      {Array.from({ length: count }).map((_, i) => (
        <CallItemSkeleton key={i} />
      ))}
    </View>
  );
}

// ----------------------------------------------------
// STARRED MESSAGES SKELETON
// ----------------------------------------------------
export function StarredMessageSkeleton() {
  return (
    <View className="flex-row items-start gap-3 py-3 pl-4 pr-3 border-b border-hairline dark:border-d-hairline active:bg-surface2 dark:active:bg-d-surface2">
      <Skeleton width={34} height={34} borderRadius={17} />
      <View className="flex-1 min-w-0 pb-1">
        <View className="flex-row items-center justify-between mb-1.5">
          <View className="flex-1 flex-row items-center gap-1.5 pr-2">
            <Skeleton width={90} height={12} borderRadius={4} />
          </View>
          <Skeleton width={40} height={10} borderRadius={4} />
        </View>
        
        <View className="mt-2 pl-3 py-1.5 border-l-2 border-hairline dark:border-d-hairline rounded-r-md gap-1.5">
          <Skeleton width="100%" height={12} borderRadius={4} />
          <Skeleton width="80%" height={12} borderRadius={4} />
        </View>

        <View className="mt-2.5">
          <Skeleton width={110} height={10} borderRadius={4} />
        </View>
      </View>
      <View className="justify-center h-full pl-1">
        <Skeleton width={12} height={16} borderRadius={4} />
      </View>
    </View>
  );
}

export function StarredListSkeleton({ count = 6 }: { count?: number }) {
  return (
    <View className="flex-1 overflow-hidden">
      {Array.from({ length: count }).map((_, i) => (
        <StarredMessageSkeleton key={i} />
      ))}
    </View>
  );
}

// ----------------------------------------------------
// ACTIVITIES LIST SKELETON
// ----------------------------------------------------
export function ActivityItemSkeleton() {
  return (
    <View className="flex-row items-center gap-3.5 py-3.5 px-[18px] border-b border-hairline dark:border-d-hairline">
      <Skeleton width={38} height={38} borderRadius={19} />
      <View className="flex-1 min-w-0 gap-2">
        <View className="gap-1.5">
          <Skeleton width="100%" height={12} borderRadius={4} />
          <Skeleton width="60%" height={12} borderRadius={4} />
        </View>
        <Skeleton width={80} height={10} borderRadius={4} />
      </View>
    </View>
  );
}

export function ActivitiesListSkeleton({ count = 8 }: { count?: number }) {
  return (
    <View className="flex-1 overflow-hidden">
      {Array.from({ length: count }).map((_, i) => (
        <ActivityItemSkeleton key={i} />
      ))}
    </View>
  );
}

// ----------------------------------------------------
// TEMPLATES LIST SKELETON
// ----------------------------------------------------
export function TemplateItemSkeleton() {
  return (
    <View className="bg-surface dark:bg-d-surface rounded-xl overflow-hidden mb-3 border border-hairline dark:border-d-hairline shadow-sm mx-[18px]">
      <View className="p-3.5 border-b border-hairline dark:border-d-hairline bg-surface2/50 dark:bg-d-surface2/50">
        <View className="flex-row justify-between items-start mb-2.5">
          <Skeleton width={140} height={16} borderRadius={4} />
          <Skeleton width={50} height={18} borderRadius={9} />
        </View>
        <View className="flex-row items-center gap-1.5">
          <Skeleton width={60} height={12} borderRadius={4} />
          <Skeleton width={4} height={4} borderRadius={2} />
          <Skeleton width={50} height={12} borderRadius={4} />
        </View>
      </View>
      <View className="p-3.5 gap-1.5">
        <Skeleton width="100%" height={12} borderRadius={4} />
        <Skeleton width="90%" height={12} borderRadius={4} />
        <Skeleton width="40%" height={12} borderRadius={4} />
      </View>
      <View className="px-3.5 py-2.5 flex-row items-center gap-2 border-t border-hairline dark:border-d-hairline bg-surface2/30 dark:bg-d-surface2/30">
        <Skeleton width={100} height={28} borderRadius={6} />
        <Skeleton width={80} height={28} borderRadius={6} />
      </View>
    </View>
  );
}

export function TemplatesListSkeleton({ count = 4 }: { count?: number }) {
  return (
    <View className="flex-1 overflow-hidden pt-3">
      {Array.from({ length: count }).map((_, i) => (
        <TemplateItemSkeleton key={i} />
      ))}
    </View>
  );
}

// ----------------------------------------------------
// CAMPAIGNS LIST SKELETON
// ----------------------------------------------------
export function CampaignItemSkeleton() {
  return (
    <View className="bg-surface dark:bg-d-surface rounded-xl p-[14px] mb-3 border border-hairline dark:border-d-hairline shadow-sm mx-[18px]">
      <View className="flex-row justify-between items-start gap-2 mb-3">
        <View className="flex-1 min-w-0 gap-1.5">
          <Skeleton width={130} height={16} borderRadius={4} />
          <Skeleton width={100} height={12} borderRadius={4} />
        </View>
        <Skeleton width={50} height={14} borderRadius={4} />
      </View>

      <View className="flex-row gap-4 mb-3">
        <View className="gap-1">
          <Skeleton width={30} height={20} borderRadius={4} />
          <Skeleton width={40} height={10} borderRadius={4} />
        </View>
        <View className="gap-1">
          <Skeleton width={30} height={20} borderRadius={4} />
          <Skeleton width={40} height={10} borderRadius={4} />
        </View>
        <View className="gap-1">
          <Skeleton width={30} height={20} borderRadius={4} />
          <Skeleton width={40} height={10} borderRadius={4} />
        </View>
      </View>

      <View className="h-1.5 bg-surface2 dark:bg-d-surface2 rounded-full overflow-hidden mb-3">
        <Skeleton width="40%" height="100%" borderRadius={0} />
      </View>

      <View className="flex-row justify-between items-center border-t border-hairline dark:border-d-hairline pt-3 mt-1">
        <Skeleton width={80} height={12} borderRadius={4} />
        <Skeleton width={80} height={12} borderRadius={4} />
      </View>
    </View>
  );
}

export function CampaignsListSkeleton({ count = 4 }: { count?: number }) {
  return (
    <View className="flex-1 overflow-hidden pt-3">
      {Array.from({ length: count }).map((_, i) => (
        <CampaignItemSkeleton key={i} />
      ))}
    </View>
  );
}
