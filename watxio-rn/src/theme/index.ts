// src/theme/index.ts — design tokens + colorScheme helpers.
// Mirrors the Tailwind config so logic that needs raw hex (SVGs, gradients,
// react-navigation themes) can read the same values.

import { useColorScheme } from 'react-native';

export const palette = {
  light: {
    bg:          '#FAFAF7',
    surface:     '#FFFFFF',
    surface2:    '#F4F3EE',
    ink:         '#15201C',
    ink2:        '#3F4945',
    muted:       '#83898A',
    faint:       '#C1C5C2',
    divider:     'rgba(21,32,28,0.06)',
    hairline:    'rgba(21,32,28,0.04)',
    accent:      '#2F8F6F',
    accentInk:   '#FFFFFF',
    accentSoft:  '#E9F2EC',
    tint:        '#F2F0EA',
    ok:          '#2F8F6F',
    warn:        '#9A7234',
    danger:      '#B4584C',
    bubbleIn:    '#F4F3EE',
    bubbleOut:   '#E5EFE9',
  },
  dark: {
    bg:          '#0F1614',
    surface:     '#171F1C',
    surface2:    '#1C2421',
    ink:         '#EEF1EE',
    ink2:        '#C4CAC7',
    muted:       '#828B87',
    faint:       '#525A57',
    divider:     'rgba(255,255,255,0.05)',
    hairline:    'rgba(255,255,255,0.03)',
    accent:      '#6CBFA0',
    accentInk:   '#0F1614',
    accentSoft:  '#1B2A24',
    tint:        '#1C2421',
    ok:          '#6CBFA0',
    warn:        '#C9A672',
    danger:      '#D58F84',
    bubbleIn:    '#1C2421',
    bubbleOut:   '#243A33',
  },
} as const;

import React, { createContext, useContext, useState, useEffect } from 'react';

export type ThemeName = 'light' | 'dark';
export type Tokens = Record<keyof typeof palette.light, string>;

export const ThemeContext = createContext<{
  scheme: ThemeName;
  toggleTheme: () => void;
}>({
  scheme: 'light',
  toggleTheme: () => {},
});

export function ThemeProvider({ children }: { children: React.ReactNode }) {
  const systemScheme = useColorScheme() === 'dark' ? 'dark' : 'light';
  const [scheme, setScheme] = useState<ThemeName>(systemScheme);

  useEffect(() => {
    setScheme(systemScheme);
  }, [systemScheme]);

  const toggleTheme = () => {
    setScheme((prev) => (prev === 'dark' ? 'light' : 'dark'));
  };

  return React.createElement(ThemeContext.Provider, { value: { scheme, toggleTheme } }, children);
}

/**
 * Returns the active palette + name. Driven by the theme provider context.
 */
export function useTokens(): { tokens: Tokens; scheme: ThemeName; toggleTheme: () => void } {
  const { scheme, toggleTheme } = useContext(ThemeContext);
  return { tokens: palette[scheme] as Tokens, scheme, toggleTheme };
}
