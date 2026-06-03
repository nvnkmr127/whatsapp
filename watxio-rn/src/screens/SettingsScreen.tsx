// src/screens/SettingsScreen.tsx — profile, workspace, prefs, devices.

import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, ScrollView, Pressable, Modal, TextInput, ActivityIndicator } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation, useFocusEffect } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import type { RootStackParamList } from '@/types';
import {
  Search, ChevronRight, Phone, Users, CreditCard, Bell, Smile, Globe, Shield, History, Bot, Sparkles, Star, X,
} from 'lucide-react-native';
import type { LucideIcon } from 'lucide-react-native';

import { useTokens, type Tokens } from '@/theme';
import { Avatar } from '@/components/Avatar';
import { Card } from '@/components/Card';
import { SectionLabel } from '@/components/SectionLabel';
import { IconButton } from '@/components/Button';
import { useGlobalState, store } from '@/store';
import { CustomDialog } from '@/components/Dialog';
import { api } from '@/services/api';

export default function SettingsScreen({ navigation }: any) {
  const { tokens, scheme, toggleTheme } = useTokens();
  const insets = useSafeAreaInsets();
  const nav = navigation;
  const [globalState, setGlobalState] = useGlobalState();
  const [loading, setLoading] = useState(false);
  const [isSearchActive, setIsSearchActive] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');

  // Editor states
  const [showProfileModal, setShowProfileModal] = useState(false);
  const [editUserName, setEditUserName] = useState(globalState.userName);
  const [editUserPhone, setEditUserPhone] = useState(globalState.user?.phone || '');

  // Dialog State
  const [dialogConfig, setDialogConfig] = useState<{
    visible: boolean;
    title: string;
    message: string;
    buttons: any[];
  }>({
    visible: false,
    title: '',
    message: '',
    buttons: [],
  });

  const showDialog = (title: string, message: string, buttons: any[] = [{ text: 'OK' }]) => {
    setDialogConfig({ visible: true, title, message, buttons });
  };

  const fetchProfileDetails = useCallback(async () => {
    if (!globalState.token) return;
    try {
      const response = await api.get('/v1/mobile/auth/me');
      setGlobalState({
        user: response.user,
        teams: response.teams || [],
        numbers: response.numbers || [],
        userName: response.user.name,
        userRole: response.user.role || 'Member',
      });
      setEditUserName(response.user.name);
      setEditUserPhone(response.user.phone || '');
    } catch (err: any) {
      console.warn('Failed to load profile settings:', err);
    }
  }, [globalState.token]);

  useFocusEffect(
    useCallback(() => {
      fetchProfileDetails();
    }, [fetchProfileDetails])
  );

  const handleSaveProfile = async () => {
    if (!editUserName.trim()) {
      showDialog('Required Fields', 'Please enter your Full Name.');
      return;
    }
    setLoading(true);
    try {
      const response = await api.post('/v1/mobile/auth/profile', {
        name: editUserName.trim(),
        phone: editUserPhone.trim() || null,
      });

      setGlobalState({
        userName: response.user.name,
        user: response.user,
      });

      setShowProfileModal(false);
      showDialog('Success', 'Profile changes saved on server.');
    } catch (err: any) {
      showDialog('Failed to Update', err.message || 'Error occurred while saving changes.');
    } finally {
      setLoading(false);
    }
  };

  const handleSignOut = async () => {
    setLoading(true);
    try {
      await api.post('/v1/mobile/auth/logout');
    } catch (err) {
      console.warn('Logout endpoint mismatch or already unauthenticated:', err);
    } finally {
      // Clear token and memory storage
      store.set({
        token: null,
        user: null,
        teams: [],
        numbers: [],
        activeTeamId: null,
      });
      setLoading(false);
      nav.reset({
        index: 0,
        routes: [{ name: 'Onboarding' }],
      });
      setLoading(false);
    }
  };

  const settingSections: {
    title: string;
    items: {
      label: string;
      Icon: any;
      value: string;
      valColor?: string;
      onPress: () => void;
    }[];
  }[] = [
    {
      title: 'Active Workspace',
      items: [
        {
          label: 'WhatsApp number',
          Icon: Phone,
          value: globalState.waNumber,
          onPress: () => showDialog('WhatsApp Number', 'This is your active WhatsApp Display Number.'),
        },
        {
          label: 'Team workspaces',
          Icon: Users,
          value: `${globalState.teams?.length || 1} joined`,
          onPress: () => showDialog('Workspaces', 'You belong to these teams. Switch workspaces on the Inbox tab.'),
        },
      ],
    },
    {
      title: 'Preferences',
      items: [
        {
          label: 'Notifications',
          Icon: Bell,
          value: 'Heads-up',
          onPress: () => showDialog('Notifications', 'Notification preferences changed.'),
        },
        {
          label: 'Theme',
          Icon: Smile,
          value: `Sage · ${scheme === 'dark' ? 'Dark' : 'Light'}`,
          onPress: toggleTheme,
        },
        {
          label: 'Language',
          Icon: Globe,
          value: 'English (US)',
          onPress: () => showDialog('Language', 'Language preferences changed.'),
        },
        {
          label: 'Privacy',
          Icon: Shield,
          value: '',
          onPress: () => showDialog('Privacy', 'Privacy settings and policy.'),
        },
        {
          label: 'Message Bots',
          Icon: Bot,
          value: '',
          onPress: () => nav.navigate('Bots'),
        },
        {
          label: 'AI Assistant',
          Icon: Sparkles,
          value: '',
          onPress: () => nav.navigate('AiSettings'),
        },
        {
          label: 'Call History',
          Icon: Phone,
          value: '',
          onPress: () => nav.navigate('Calls'),
        },
        {
          label: 'Starred Messages',
          Icon: Star,
          value: '',
          onPress: () => nav.navigate('StarredMessages'),
        },
        {
          label: 'Activity Logs',
          Icon: History,
          value: '',
          onPress: () => nav.navigate('Activities'),
        },
      ],
    },
    {
      title: 'Devices',
      items: [
        {
          label: 'Active Device',
          Icon: Phone,
          value: 'This app',
          valColor: tokens.ok,
          onPress: () => showDialog('Current Device', 'This device is currently active.'),
        },
      ],
    },
  ];

  const filteredSections = searchQuery.trim()
    ? settingSections
        .map((section) => ({
          ...section,
          items: section.items.filter((item) =>
            item.label.toLowerCase().includes(searchQuery.toLowerCase())
          ),
        }))
        .filter((section) => section.items.length > 0)
    : settingSections;

  return (
    <View className="flex-1 bg-bg dark:bg-d-bg" style={{ paddingTop: insets.top }}>
      {/* Header */}
      <View className="flex-row items-center justify-between px-[18px] pt-3.5 pb-2 h-14">
        {isSearchActive ? (
          <View className="flex-1 flex-row items-center gap-3 bg-surface2 dark:bg-d-surface2 px-3 py-1.5 rounded-xl border border-hairline dark:border-d-hairline">
            <Search size={16} color={tokens.muted} />
            <TextInput
              value={searchQuery}
              onChangeText={setSearchQuery}
              placeholder="Search settings..."
              placeholderTextColor={tokens.faint}
              autoFocus
              className="flex-1 text-sm text-ink dark:text-d-ink font-medium p-0"
            />
            {searchQuery.length > 0 && (
              <Pressable onPress={() => setSearchQuery('')} className="p-0.5">
                <X size={14} color={tokens.muted} />
              </Pressable>
            )}
            <Pressable 
              onPress={() => {
                setIsSearchActive(false);
                setSearchQuery('');
              }}
              className="pl-1"
            >
              <Text className="text-accent dark:text-d-accent text-sm font-semibold">Cancel</Text>
            </Pressable>
          </View>
        ) : (
          <>
            <Text className="text-2xl font-bold tracking-[-0.3px] text-ink dark:text-d-ink">
              Settings
            </Text>
            <IconButton icon={Search} onPress={() => setIsSearchActive(true)} />
          </>
        )}
      </View>

      {loading && !globalState.user ? (
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator size="large" color={tokens.accent} />
        </View>
      ) : (
        <ScrollView contentContainerStyle={{ paddingHorizontal: 18, paddingBottom: 32 }}>
          {/* Profile card - hide during active search */}
          {!searchQuery.trim() && (
            <Pressable
              onPress={() => {
                setEditUserName(globalState.userName);
                setEditUserPhone(globalState.user?.phone || '');
                setShowProfileModal(true);
              }}
              className="bg-surface dark:bg-d-surface rounded-lg p-3.5 flex-row items-center gap-3 active:bg-surface2 dark:active:bg-d-surface2"
            >
              <Avatar name={globalState.userName} size={44} />
              <View className="flex-1">
                <Text className="text-[15px] font-semibold text-ink dark:text-d-ink">{globalState.userName}</Text>
                <Text className="text-xs text-muted dark:text-d-muted mt-0.5">{globalState.user?.email || globalState.userRole}</Text>
              </View>
              <ChevronRight size={16} color={tokens.muted} strokeWidth={1.6} />
            </Pressable>
          )}

          {/* Setting Sections */}
          {filteredSections.map((section) => {
            const isFirst = section.title === 'Active Workspace';
            return (
              <React.Fragment key={section.title}>
                <SectionLabel>{section.title}</SectionLabel>
                <Card pad={0}>
                  {/* Show business name row ONLY in Active Workspace section when NOT searching */}
                  {isFirst && !searchQuery.trim() && (
                    <View className="flex-row items-center gap-3 px-4 py-3.5 border-b border-hairline dark:border-d-hairline">
                      <View className="w-9 h-9 rounded-md bg-accent dark:bg-d-accent items-center justify-center">
                        <Text className="text-accent-ink dark:text-d-accent-ink text-[13px] font-bold">
                          {globalState.businessName.substring(0, 2).toUpperCase()}
                        </Text>
                      </View>
                      <View className="flex-1">
                        <Text className="text-[13.5px] font-semibold text-ink dark:text-d-ink">{globalState.businessName}</Text>
                        <Text className="text-[11.5px] text-muted dark:text-d-muted mt-0.5">{globalState.plan}</Text>
                      </View>
                      <View className="flex-row items-center gap-1.25">
                        <View className="w-1.5 h-1.5 rounded-full bg-ok dark:bg-d-ok" />
                        <Text className="text-[11.5px] text-ok dark:text-d-ok font-medium">Verified</Text>
                      </View>
                    </View>
                  )}
                  {section.items.map((item, idx) => (
                    <SettingRow
                      key={item.label}
                      tokens={tokens}
                      Icon={item.Icon}
                      label={item.label}
                      value={item.value}
                      valColor={item.valColor}
                      onPress={item.onPress}
                      last={idx === section.items.length - 1}
                    />
                  ))}
                </Card>
              </React.Fragment>
            );
          })}

          {/* Empty Results state */}
          {filteredSections.length === 0 && (
            <View className="py-20 items-center justify-center">
              <Search size={40} color={tokens.faint} strokeWidth={1.2} className="mb-2" />
              <Text className="text-sm font-semibold text-ink dark:text-d-ink">No Results Found</Text>
              <Text className="text-xs text-muted dark:text-d-muted text-center mt-1">
                No setting options match "{searchQuery}"
              </Text>
            </View>
          )}

          {/* Sign Out Button - hide during active search */}
          {!searchQuery.trim() && (
            <Pressable
              onPress={() => {
                showDialog(
                  'Sign Out',
                  'Are you sure you want to sign out of this device?',
                  [
                    { text: 'Cancel', style: 'cancel' },
                    {
                      text: 'Sign Out',
                      style: 'destructive',
                      onPress: handleSignOut,
                    },
                  ]
                );
              }}
              className="mt-7 py-3.5 rounded-lg items-center justify-center active:bg-surface2 dark:active:bg-d-surface2"
            >
              <Text className="text-danger dark:text-d-danger text-sm font-medium">Sign out of this account</Text>
            </Pressable>
          )}

          {!searchQuery.trim() && (
            <Text className="text-center text-[11px] text-muted dark:text-d-muted pb-1 px-2">
              Watxio Mobile · v3.4.1
            </Text>
          )}
          {!searchQuery.trim() && (
            <Text className="text-center text-[10px] text-muted dark:text-d-muted pb-4 px-2" selectable>
              {`Server: ${globalState.baseUrl}`}
            </Text>
          )}
        </ScrollView>
      )}

      {/* Edit Profile Modal */}
      <Modal transparent visible={showProfileModal} animationType="slide">
        <View className="flex-1 bg-black/40 justify-end">
          <View className="bg-surface dark:bg-d-surface rounded-t-2xl p-5 gap-4" style={{ paddingBottom: insets.bottom + 16 }}>
            <View className="flex-row items-center justify-between border-b border-hairline dark:border-d-hairline pb-2.5">
              <Text className="text-base font-bold text-ink dark:text-d-ink">Edit Profile Info</Text>
              <Pressable onPress={() => setShowProfileModal(false)}>
                <Text className="text-muted dark:text-d-muted font-semibold text-sm">Cancel</Text>
              </Pressable>
            </View>
            <View className="gap-3">
              <View>
                <Text className="text-xs font-semibold text-muted dark:text-d-muted mb-1.5 uppercase tracking-wide">Full Name</Text>
                <TextInput
                  value={editUserName}
                  onChangeText={setEditUserName}
                  className="bg-surface2 dark:bg-d-surface2 p-3 text-sm rounded-lg text-ink dark:text-d-ink font-medium"
                />
              </View>
              <View>
                <Text className="text-xs font-semibold text-muted dark:text-d-muted mb-1.5 uppercase tracking-wide">Phone Number</Text>
                <TextInput
                  value={editUserPhone}
                  onChangeText={setEditUserPhone}
                  placeholder="e.g. +14155550118"
                  placeholderTextColor={tokens.faint}
                  className="bg-surface2 dark:bg-d-surface2 p-3 text-sm rounded-lg text-ink dark:text-d-ink font-medium"
                />
              </View>
            </View>
            <Pressable
              onPress={handleSaveProfile}
              className="bg-accent dark:bg-d-accent py-3.5 rounded-xl items-center active:opacity-90 mt-2"
            >
              <Text className="text-accent-ink dark:text-d-accent-ink font-bold text-sm">Save Profile Changes</Text>
            </Pressable>
          </View>
        </View>
      </Modal>

      <CustomDialog
        visible={dialogConfig.visible}
        title={dialogConfig.title}
        message={dialogConfig.message}
        buttons={dialogConfig.buttons}
        onClose={() => setDialogConfig((c) => ({ ...c, visible: false }))}
      />
    </View>
  );
}

interface RowProps {
  tokens: Tokens;
  Icon: LucideIcon;
  label: string;
  value?: string;
  valColor?: string;
  last?: boolean;
  onPress?: () => void;
}

function SettingRow({ tokens, Icon, label, value, valColor, last, onPress }: RowProps) {
  return (
    <Pressable
      onPress={onPress}
      className={`flex-row items-center gap-3 px-4 py-3.5 active:bg-surface2 dark:active:bg-d-surface2 ${
        last ? '' : 'border-b border-hairline dark:border-d-hairline'
      }`}
    >
      <Icon size={16} color={tokens.muted} strokeWidth={1.6} />
      <Text className="flex-1 text-[13.5px] font-medium text-ink dark:text-d-ink">{label}</Text>
      {value ? (
        <Text
          style={valColor ? { color: valColor } : undefined}
          className={`text-[12.5px] font-medium ${valColor ? '' : 'text-muted dark:text-d-muted'}`}
        >
          {value}
        </Text>
      ) : null}
      <ChevronRight size={14} color={tokens.faint} strokeWidth={1.6} />
    </Pressable>
  );
}
