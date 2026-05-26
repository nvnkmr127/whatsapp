// src/navigation/AppNavigator.tsx — bottom tabs + native stack.

import React from 'react';
import { NavigationContainer, DefaultTheme, DarkTheme } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createBottomTabNavigator, BottomTabBarProps } from '@react-navigation/bottom-tabs';
import { View, Text, Pressable } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import {
  Inbox, FileText, BarChart3, Bot, Settings as SettingsIcon,
} from 'lucide-react-native';
import type { LucideIcon } from 'lucide-react-native';

import { useTokens } from '@/theme';
import type { RootStackParamList, MainTabParamList } from '@/types';
import { navigationRef } from './navigationRef';

import InboxScreen from '@/screens/InboxScreen';
import ChatScreen from '@/screens/ChatScreen';
import ContactScreen from '@/screens/ContactScreen';
import BroadcastScreen from '@/screens/BroadcastScreen';
import TemplatesScreen from '@/screens/TemplatesScreen';
import AnalyticsScreen from '@/screens/AnalyticsScreen';
import AutomationsScreen from '@/screens/AutomationsScreen';
import LoginScreen from '@/screens/LoginScreen';
import SettingsScreen from '@/screens/SettingsScreen';
import OnboardingScreen from '@/screens/OnboardingScreen';
import ActivitiesScreen from '@/screens/ActivitiesScreen';
import ActivityDetailScreen from '@/screens/ActivityDetailScreen';
import CampaignDetailScreen from '@/screens/CampaignDetailScreen';
import CallsScreen from '@/screens/CallsScreen';
import BotsScreen from '@/screens/BotsScreen';
import AiSettingsScreen from '@/screens/AiSettingsScreen';
import StarredMessagesScreen from '@/screens/StarredMessagesScreen';

const Stack = createNativeStackNavigator<RootStackParamList>();
const Tab = createBottomTabNavigator<MainTabParamList>();

const TAB_ICONS: Record<keyof MainTabParamList, LucideIcon> = {
  Inbox: Inbox,
  Templates: FileText,
  Analytics: BarChart3,
  Automations: Bot,
  Settings: SettingsIcon,
};

const TAB_LABELS: Record<keyof MainTabParamList, string> = {
  Inbox: 'Inbox',
  Templates: 'Templates',
  Analytics: 'Analytics',
  Automations: 'Flows',
  Settings: 'Me',
};

function CustomTabBar({ state, navigation }: BottomTabBarProps) {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  return (
    <View
      className="flex-row pt-1.5 border-t"
      style={{
        backgroundColor: tokens.surface,
        borderColor: tokens.hairline,
        paddingBottom: 4 + insets.bottom,
      }}
    >
      {state.routes.map((route, idx) => {
        const focused = state.index === idx;
        const Icon = TAB_ICONS[route.name as keyof MainTabParamList];
        const label = TAB_LABELS[route.name as keyof MainTabParamList];
        return (
          <Pressable
            key={route.key}
            onPress={() => {
              const ev = navigation.emit({ type: 'tabPress', target: route.key, canPreventDefault: true });
              if (!focused && !ev.defaultPrevented) navigation.navigate(route.name);
            }}
            className="flex-1 py-2 items-center gap-1"
          >
            <Icon
              size={22}
              color={focused ? tokens.accent : tokens.muted}
              strokeWidth={focused ? 2 : 1.6}
            />
            <Text
              style={{
                color: focused ? tokens.accent : tokens.muted,
              }}
              className={`text-[10.5px] ${
                focused ? 'font-semibold' : 'font-medium'
              }`}
            >
              {label}
            </Text>
          </Pressable>
        );
      })}
    </View>
  );
}

function MainTabs() {
  return (
    <Tab.Navigator
      screenOptions={{ headerShown: false }}
      tabBar={(props) => <CustomTabBar {...props} />}
    >
      <Tab.Screen name="Inbox"       component={InboxScreen} />
      <Tab.Screen name="Templates"   component={TemplatesScreen} />
      <Tab.Screen name="Analytics"   component={AnalyticsScreen} />
      <Tab.Screen name="Automations" component={AutomationsScreen} />
      <Tab.Screen name="Settings"    component={SettingsScreen} />
    </Tab.Navigator>
  );
}

export default function AppNavigator() {
  const { tokens, scheme } = useTokens();

  // Build a React Navigation theme from our tokens so push transitions and
  // screen backgrounds don't flash white on dark mode.
  const navTheme = {
    ...(scheme === 'dark' ? DarkTheme : DefaultTheme),
    colors: {
      ...(scheme === 'dark' ? DarkTheme.colors : DefaultTheme.colors),
      background: tokens.bg,
      card: tokens.bg,
      text: tokens.ink,
      primary: tokens.accent,
      border: tokens.hairline,
    },
  };

  return (
    <NavigationContainer theme={navTheme} ref={navigationRef}>
      <Stack.Navigator screenOptions={{ headerShown: false }} initialRouteName="Onboarding">
        <Stack.Screen name="Onboarding" component={OnboardingScreen} />
        <Stack.Screen name="Main"      component={MainTabs} />
        <Stack.Screen name="Chat"      component={ChatScreen} />
        <Stack.Screen name="Contact"   component={ContactScreen} />
        <Stack.Screen
          name="Broadcast"
          component={BroadcastScreen}
          options={{ presentation: 'modal', animation: 'slide_from_bottom' }}
        />
        <Stack.Screen
          name="Login"
          component={LoginScreen}
          options={{ presentation: 'modal' }}
        />
        <Stack.Screen name="Activities" component={ActivitiesScreen} />
        <Stack.Screen name="ActivityDetail" component={ActivityDetailScreen} />
        <Stack.Screen name="CampaignDetail" component={CampaignDetailScreen} />
        <Stack.Screen name="Calls" component={CallsScreen} />
        <Stack.Screen name="Bots" component={BotsScreen} />
        <Stack.Screen name="AiSettings" component={AiSettingsScreen} />
        <Stack.Screen name="StarredMessages" component={StarredMessagesScreen} />
      </Stack.Navigator>
    </NavigationContainer>
  );
}
