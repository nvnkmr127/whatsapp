// src/components/Avatar.tsx — initials disc, optional online dot + ring.

import React from 'react';
import { View, Text } from 'react-native';
import { useTokens } from '@/theme';

const HUES = [18, 35, 80, 140, 165, 195, 220, 260, 320];

function hueFor(s: string): number {
  let h = 0;
  for (let i = 0; i < (s ?? '').length; i++) h = ((h << 5) - h + s.charCodeAt(i)) | 0;
  return HUES[Math.abs(h) % HUES.length];
}

function initials(s: string): string {
  if (!s) return '?';
  const parts = s.trim().split(/\s+/).filter(Boolean);
  if (parts.length === 0) return '?';
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

interface Props {
  name: string;
  size?: number;
  showColor?: boolean;
  dot?: string | null;
  ring?: string | null;
}

export function Avatar({ name, size = 40, showColor = true, dot, ring }: Props) {
  const { tokens, scheme } = useTokens();
  const h = hueFor(name);
  const isDark = scheme === 'dark';

  const bg = showColor
    ? `hsl(${h} ${isDark ? 18 : 24}% ${isDark ? 28 : 88}%)`
    : tokens.surface2;
  const fg = showColor
    ? `hsl(${h} ${isDark ? 30 : 35}% ${isDark ? 78 : 28}%)`
    : tokens.ink2;

  return (
    <View style={{ position: 'relative', width: size, height: size }}>
      <View
        style={{
          width: size, height: size, borderRadius: size,
          backgroundColor: bg,
          alignItems: 'center', justifyContent: 'center',
          ...(ring ? { borderWidth: 2, borderColor: ring } : {}),
        }}
      >
        <Text
          style={{
            color: fg, fontSize: size * 0.36, fontWeight: '600', letterSpacing: 0.3,
          }}
        >
          {initials(name)}
        </Text>
      </View>
      {dot ? (
        <View
          style={{
            position: 'absolute', right: -1, bottom: -1,
            width: Math.max(8, size * 0.26),
            height: Math.max(8, size * 0.26),
            borderRadius: 999, backgroundColor: dot,
            borderWidth: 2, borderColor: tokens.bg,
          }}
        />
      ) : null}
    </View>
  );
}
