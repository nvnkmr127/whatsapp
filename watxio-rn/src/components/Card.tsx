// src/components/Card.tsx — quiet surface container. No border, no shadow —
// the contrast between bg and surface alone is enough to read as a group.

import React from 'react';
import { View, ViewProps } from 'react-native';

interface Props extends ViewProps {
  pad?: number;
  children: React.ReactNode;
}

export function Card({ pad = 14, children, style, ...rest }: Props) {
  return (
    <View
      {...rest}
      className="bg-surface dark:bg-d-surface rounded-lg"
      style={[{ padding: pad }, style]}
    >
      {children}
    </View>
  );
}

