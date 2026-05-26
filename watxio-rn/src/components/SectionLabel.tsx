// src/components/SectionLabel.tsx — sentence-case, regular-weight section
// header with an optional right-aligned action link.

import React from 'react';
import { View, Text, Pressable } from 'react-native';

interface Props {
  children: React.ReactNode;
  action?: string;
  onActionPress?: () => void;
}

export function SectionLabel({ children, action, onActionPress }: Props) {
  return (
    <View className="pt-5 pb-2 px-5 flex-row justify-between items-baseline">
      <Text className="text-muted dark:text-d-muted text-xs font-medium">
        {children}
      </Text>
      {action ? (
        <Pressable onPress={onActionPress}>
          <Text className="text-accent dark:text-d-accent text-xs font-medium">{action}</Text>
        </Pressable>
      ) : null}
    </View>
  );
}

