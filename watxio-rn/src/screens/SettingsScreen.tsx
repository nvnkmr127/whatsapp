// src/screens/SettingsScreen.tsx — profile, workspace, prefs, devices.

import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, ScrollView, Pressable, Modal, TextInput, ActivityIndicator } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation, useFocusEffect } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import type { RootStackParamList } from '@/types';
import {
  Search, ChevronRight, Phone, Users, CreditCard, Bell, Smile, Globe, Shield,
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
    }
  };

  return (
    <View className="flex-1 bg-bg dark:bg-d-bg" style={{ paddingTop: insets.top }}>
      {/* Header */}
      <View className="flex-row items-center justify-between px-[18px] pt-3.5 pb-2">
        <Text className="text-2xl font-bold tracking-[-0.3px] text-ink dark:text-d-ink">
          Settings
        </Text>
        <IconButton icon={Search} onPress={() => showDialog('Search Settings', 'Search functionality is not implemented in the settings demo.')} />
      </View>

      {loading && !globalState.user ? (
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator size="large" color={tokens.accent} />
        </View>
      ) : (
        <ScrollView contentContainerStyle={{ paddingHorizontal: 18, paddingBottom: 32 }}>
          {/* Profile card */}
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

          {/* Workspace */}
          <SectionLabel>
            Active Workspace
          </SectionLabel>
          <Card pad={0}>
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
            <SettingRow tokens={tokens} Icon={Phone}      label="WhatsApp number" value={globalState.waNumber} onPress={() => showDialog('WhatsApp Number', 'This is your active WhatsApp Display Number.')} />
            <SettingRow tokens={tokens} Icon={Users}      label="Team workspaces" value={`${globalState.teams?.length || 1} joined`} onPress={() => showDialog('Workspaces', 'You belong to these teams. Switch workspaces on the Inbox tab.')} last />
          </Card>

          <SectionLabel>Preferences</SectionLabel>
          <Card pad={0}>
            <SettingRow tokens={tokens} Icon={Bell}   label="Notifications" value="Heads-up" onPress={() => showDialog('Notifications', 'Notification preferences changed.')} />
            <SettingRow tokens={tokens} Icon={Smile}  label="Theme"         value={`Sage · ${scheme === 'dark' ? 'Dark' : 'Light'}`} onPress={toggleTheme} />
            <SettingRow tokens={tokens} Icon={Globe}  label="Language"      value="English (US)" onPress={() => showDialog('Language', 'Language preferences changed.')} />
            <SettingRow tokens={tokens} Icon={Shield} label="Privacy"       value="" onPress={() => showDialog('Privacy', 'Privacy settings and policy.')} last />
          </Card>

          <SectionLabel>Devices</SectionLabel>
          <Card pad={0}>
            <SettingRow tokens={tokens} Icon={Phone} label="Active Device" value="This app" valColor={tokens.ok} onPress={() => showDialog('Current Device', 'This device is currently active.')} last />
          </Card>

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

          <Text className="text-center text-[11px] text-muted dark:text-d-muted p-2">
            Watxio Mobile · v3.4.1
          </Text>
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
