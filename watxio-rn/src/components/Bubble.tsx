// src/components/Bubble.tsx — chat bubble (in or out).
// Borderless, single-fill. Tail style by default to match WhatsApp affordance.

import React from 'react';
import { View, Text } from 'react-native';
import { Check, CheckCheck } from 'lucide-react-native';
import { useTokens } from '@/theme';
import type { MessageStatus } from '@/types';

interface Props {
  kind: 'in' | 'out';
  children: React.ReactNode;
  time?: string;
  status?: MessageStatus;
  variant?: 'rounded' | 'squared' | 'tail';
  radius?: number;
}

export function Bubble({ kind, children, time, status, variant = 'tail', radius = 18 }: Props) {
  const { tokens } = useTokens();
  const isOut = kind === 'out';
  const baseR = variant === 'squared' ? Math.min(8, radius * 0.45) : radius;

  const corner = variant === 'tail'
    ? (isOut
        ? { borderTopLeftRadius: baseR, borderTopRightRadius: baseR, borderBottomLeftRadius: baseR, borderBottomRightRadius: 4 }
        : { borderTopLeftRadius: baseR, borderTopRightRadius: baseR, borderBottomLeftRadius: 4, borderBottomRightRadius: baseR })
    : { borderRadius: baseR };

  return (
    <View
      className={`max-w-[78%] py-2 px-3 ${isOut ? 'self-end bg-bubble-out dark:bg-d-bubble-out' : 'self-start bg-bubble-in dark:bg-d-bubble-in'}`}
      style={corner}
    >
      <Text className="text-ink dark:text-d-ink text-[14px] leading-5 font-normal">{children}</Text>
      <View className="mt-[3px] flex-row items-center justify-end gap-1">
        {time ? (
          <Text className="text-muted dark:text-d-muted text-[10.5px] font-medium">{time}</Text>
        ) : null}
        {isOut && status ? (
          status === 'read'
            ? <CheckCheck size={13} color={tokens.accent} strokeWidth={2} />
            : <Check size={13} color={tokens.muted} strokeWidth={2} />
        ) : null}
      </View>
    </View>
  );
}

