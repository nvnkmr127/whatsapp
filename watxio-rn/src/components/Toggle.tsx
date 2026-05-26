// src/components/Toggle.tsx — animated switch, sage when on.

import React, { useEffect, useRef } from 'react';
import { Pressable, Animated } from 'react-native';
import { useTokens } from '@/theme';

interface Props {
  on: boolean;
  onChange: (next: boolean) => void;
  disabled?: boolean;
}

export function Toggle({ on, onChange, disabled }: Props) {
  const { tokens } = useTokens();
  const anim = useRef(new Animated.Value(on ? 1 : 0)).current;

  useEffect(() => {
    Animated.timing(anim, {
      toValue: on ? 1 : 0,
      duration: 180,
      useNativeDriver: false,
    }).start();
  }, [on, anim]);

  const trackBg = anim.interpolate({
    inputRange: [0, 1],
    outputRange: [tokens.surface2, tokens.accent],
  });
  const knobLeft = anim.interpolate({
    inputRange: [0, 1],
    outputRange: [2, 20],
  });

  return (
    <Pressable onPress={() => !disabled && onChange(!on)} hitSlop={8}>
      <Animated.View
        className="w-[42px] h-6 rounded-full justify-center"
        style={{
          backgroundColor: trackBg,
          opacity: disabled ? 0.5 : 1,
        }}
      >
        <Animated.View
          className="absolute top-[2px] w-5 h-5 rounded-full bg-white"
          style={{
            left: knobLeft,
            shadowColor: '#000',
            shadowOffset: { width: 0, height: 1 },
            shadowOpacity: 0.18, shadowRadius: 2,
            elevation: 2,
          }}
        />
      </Animated.View>
    </Pressable>
  );
}

