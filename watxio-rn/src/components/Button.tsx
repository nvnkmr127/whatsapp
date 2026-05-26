// src/components/Button.tsx — Primary (sage fill), Ghost (surface fill),
// IconButton (transparent square hit-target).

import React from 'react';
import { Pressable, Text, PressableProps } from 'react-native';
import { useTokens } from '@/theme';
import type { LucideIcon } from 'lucide-react-native';

type Size = 'sm' | 'md' | 'lg';

interface PrimaryProps extends PressableProps {
  label: string;
  full?: boolean;
  size?: Size;
  icon?: LucideIcon;
}

export function PrimaryButton({ label, full, size = 'md', icon: Icon, onPress, ...rest }: PrimaryProps) {
  const { tokens } = useTokens();
  const pad = size === 'lg' ? { paddingVertical: 14, paddingHorizontal: 22 }
            : size === 'sm' ? { paddingVertical: 8,  paddingHorizontal: 14 }
            :                  { paddingVertical: 11, paddingHorizontal: 18 };
  return (
    <Pressable
      onPress={onPress}
      {...rest}
      className={`bg-accent dark:bg-d-accent rounded-lg flex-row items-center justify-center gap-2 active:opacity-85 ${
        full ? 'self-stretch' : 'self-start'
      }`}
      style={pad}
    >
      {Icon ? <Icon size={16} color={tokens.accentInk} strokeWidth={2} /> : null}
      <Text className="text-accent-ink dark:text-d-accent-ink text-sm font-semibold">{label}</Text>
    </Pressable>
  );
}

interface GhostProps extends PressableProps {
  label: string;
  full?: boolean;
  icon?: LucideIcon;
}

export function GhostButton({ label, full, icon: Icon, onPress, ...rest }: GhostProps) {
  const { tokens } = useTokens();
  return (
    <Pressable
      onPress={onPress}
      {...rest}
      className={`bg-surface2 dark:bg-d-surface2 rounded-lg flex-row items-center justify-center gap-2 py-[11px] px-4 active:opacity-85 ${
        full ? 'self-stretch' : 'self-start'
      }`}
    >
      {Icon ? <Icon size={16} color={tokens.ink} strokeWidth={1.6} /> : null}
      <Text className="text-ink dark:text-d-ink text-sm font-medium">{label}</Text>
    </Pressable>
  );
}

interface IconButtonProps extends PressableProps {
  icon: LucideIcon;
  size?: number;
  color?: string;
}

export function IconButton({ icon: Icon, size = 36, color, onPress, ...rest }: IconButtonProps) {
  const { tokens } = useTokens();
  return (
    <Pressable
      onPress={onPress}
      {...rest}
      className="items-center justify-center active:opacity-60"
      style={{
        width: size,
        height: size,
        borderRadius: size,
      }}
      hitSlop={8}
    >
      <Icon size={Math.round(size * 0.55)} color={color ?? tokens.ink} strokeWidth={1.6} />
    </Pressable>
  );
}

