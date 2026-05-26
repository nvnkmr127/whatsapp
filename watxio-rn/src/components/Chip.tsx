// src/components/Chip.tsx — quiet filter chip. Inactive = muted text only;
// active = ink-on-bg pill. Optional count rendered as a tabular trailing number.

import React from 'react';
import { Pressable, Text, View } from 'react-native';

interface Props {
  label: string;
  active?: boolean;
  count?: number;
  onPress?: () => void;
}

export function Chip({ label, active, count, onPress }: Props) {
  return (
    <Pressable
      onPress={onPress}
      className={`py-[7px] px-[13px] rounded-full flex-row items-center gap-[5px] ${
        active ? 'bg-ink dark:bg-d-ink' : 'bg-surface2 dark:bg-d-surface2'
      }`}
    >
      <Text
        className={`text-[13px] ${
          active ? 'text-bg dark:text-d-bg font-semibold' : 'text-muted dark:text-d-muted font-medium'
        }`}
      >
        {label}
      </Text>
      {count != null ? (
        <Text
          className={`text-[11px] ${
            active ? 'text-bg dark:text-d-bg opacity-70 font-semibold' : 'text-muted dark:text-d-muted opacity-60 font-medium'
          }`}
          style={{ fontVariant: ['tabular-nums'] }}
        >
          {count}
        </Text>
      ) : null}
    </Pressable>
  );
}
