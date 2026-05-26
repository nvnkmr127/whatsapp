// src/screens/LoginScreen.tsx — onboarding entry. Brand mark + phone field + QR.

import React, { useState } from 'react';
import { View, Text, TextInput, Pressable, ScrollView, Modal, ActivityIndicator } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { ChevronDown, QrCode } from 'lucide-react-native';
import Svg, { Path } from 'react-native-svg';

import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import type { RootStackParamList } from '@/types';

import { useTokens } from '@/theme';
import { PrimaryButton } from '@/components/Button';
import { store } from '@/store';
import { CustomDialog } from '@/components/Dialog';


export default function LoginScreen() {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  const [phone, setPhone] = useState('');
  const [country, setCountry] = useState({ flag: '🇺🇸', code: '+1' });
  const [loading, setLoading] = useState(false);
  const [showCountryPicker, setShowCountryPicker] = useState(false);
  const nav = useNavigation<NativeStackNavigationProp<RootStackParamList>>();

  // Dialog State
  const [dialogConfig, setDialogConfig] = useState<{
    visible: boolean;
    title: string;
    message: string;
    buttons: { text: string; onPress?: () => void; style?: 'default' | 'cancel' | 'destructive' }[];
  }>({
    visible: false,
    title: '',
    message: '',
    buttons: [],
  });

  const showDialog = (title: string, message: string, buttons: typeof dialogConfig.buttons = [{ text: 'OK' }]) => {
    setDialogConfig({ visible: true, title, message, buttons });
  };

  const countries = [
    { name: 'United States', code: '+1', flag: '🇺🇸' },
    { name: 'United Kingdom', code: '+44', flag: '🇬🇧' },
    { name: 'India', code: '+91', flag: '🇮🇳' },
    { name: 'Germany', code: '+49', flag: '🇩🇪' },
    { name: 'Brazil', code: '+55', flag: '🇧🇷' },
  ];

  const handleContinue = () => {
    const trimmed = phone.trim();
    if (!trimmed) {
      showDialog('Phone Number Required', 'Please enter your business WhatsApp number.');
      return;
    }
    setLoading(true);
    setTimeout(() => {
      setLoading(false);
      store.set({ waNumber: `${country.code} ${trimmed}` });
      nav.replace('Main');
    }, 1200);
  };


  const handleScanQr = () => {
    setLoading(true);
    setTimeout(() => {
      setLoading(false);
      nav.replace('Main');
    }, 1200);
  };

  return (
    <View className="flex-1 bg-bg dark:bg-d-bg">
      <ScrollView
        className="flex-1"
        contentContainerStyle={{
          flexGrow: 1,
          paddingTop: insets.top + 24,
          paddingBottom: insets.bottom + 24,
          paddingHorizontal: 26,
          alignItems: 'center',
        }}
        keyboardShouldPersistTaps="handled"
      >
        {/* Brand mark */}
        <View
          className="w-16 h-16 rounded-xl bg-accent dark:bg-d-accent items-center justify-center mt-8 mb-6"
        >
          <Svg width={32} height={32} viewBox="0 0 24 24" fill="none">
            <Path
              d="M4 12c0-4.4 3.6-8 8-8 4.4 0 8 3.6 8 8 0 4.4-3.6 8-8 8-1.5 0-2.9-.4-4.1-1.1L4 20l1.1-3.9C4.4 14.9 4 13.5 4 12z"
              stroke={tokens.accentInk} strokeWidth={1.7} strokeLinejoin="round"
            />
            <Path d="M9 11l2 2 4-4" stroke={tokens.accentInk} strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round" />
          </Svg>
        </View>

        <Text
          className="text-[26px] font-bold tracking-[-0.6px] text-ink dark:text-d-ink text-center"
        >
          Welcome to Watxio
        </Text>
        <Text
          className="text-sm text-muted dark:text-d-muted text-center mt-2.5 leading-[21px] max-w-[280px]"
        >
          The WhatsApp Business inbox your team has been waiting for.
        </Text>

        {/* Phone field */}
        <View className="w-full mt-8">
          <Text className="text-xs text-muted dark:text-d-muted font-medium mb-2">
            Business WhatsApp number
          </Text>
          <View
            className="flex-row items-center gap-2.5 bg-surface dark:bg-d-surface rounded-lg px-4 py-3.5"
          >
            <Pressable
              onPress={() => setShowCountryPicker(true)}
              className="flex-row items-center gap-1 pr-3 border-r border-hairline dark:border-d-hairline"
            >
              <Text className="text-sm font-medium text-ink dark:text-d-ink">{country.flag} {country.code}</Text>
              <ChevronDown size={12} color={tokens.muted} strokeWidth={1.6} />
            </Pressable>
            <TextInput
              value={phone}
              onChangeText={setPhone}
              placeholder="415 555 0118"
              placeholderTextColor={tokens.muted}
              className="flex-1 text-base text-ink dark:text-d-ink p-0"
              keyboardType="phone-pad"
            />
          </View>
        </View>

        <View className="w-full mt-3">
          <PrimaryButton full size="lg" label="Continue" onPress={handleContinue} />
        </View>

        {/* Divider */}
        <View className="w-full flex-row items-center gap-3 my-5">
          <View className="flex-1 h-px bg-hairline dark:bg-d-hairline" />
          <Text className="text-[11px] text-muted dark:text-d-muted font-medium">or</Text>
          <View className="flex-1 h-px bg-hairline dark:bg-d-hairline" />
        </View>

        <Pressable
          onPress={handleScanQr}
          className="w-full py-3 px-3.5 rounded-lg bg-surface dark:bg-d-surface flex-row items-center justify-center gap-2 active:bg-surface2 dark:active:bg-d-surface2"
        >
          <QrCode size={18} color={tokens.ink} strokeWidth={1.6} />
          <Text className="text-ink dark:text-d-ink text-sm font-medium">Scan QR from web app</Text>
        </Pressable>

        <View className="flex-1" />

        <Text
          className="text-[11.5px] text-muted dark:text-d-muted text-center leading-[18px] px-2 pt-6"
        >
          By continuing you agree to our Terms and Privacy policy. We never read your customer conversations.
        </Text>
      </ScrollView>

      {/* Loading Modal Overlay */}
      <Modal transparent visible={loading} animationType="fade">
        <View className="flex-1 bg-black/40 items-center justify-center">
          <View className="bg-surface dark:bg-d-surface p-6 rounded-2xl items-center gap-3.5 shadow-xl max-w-[280px]">
            <ActivityIndicator size="large" color={tokens.accent} />
            <View className="items-center">
              <Text className="text-base font-bold text-ink dark:text-d-ink text-center">Connecting...</Text>
              <Text className="text-[12px] text-muted dark:text-d-muted text-center mt-1">Linking with WhatsApp Business API</Text>
            </View>
          </View>
        </View>
      </Modal>

      {/* Country Picker Modal */}
      <Modal transparent visible={showCountryPicker} animationType="slide">
        <Pressable onPress={() => setShowCountryPicker(false)} className="flex-1 bg-black/40 justify-end">
          <Pressable onPress={() => {}} className="bg-surface dark:bg-d-surface rounded-t-2xl p-5 gap-3" style={{ paddingBottom: insets.bottom + 16 }}>
            <View className="flex-row items-center justify-between border-b border-hairline dark:border-d-hairline pb-2.5">
              <Text className="text-base font-bold text-ink dark:text-d-ink">Select Country</Text>
              <Pressable onPress={() => setShowCountryPicker(false)}>
                <Text className="text-accent dark:text-d-accent font-semibold text-sm">Cancel</Text>
              </Pressable>
            </View>
            <View className="gap-1 mt-1">
              {countries.map((c) => (
                <Pressable
                  key={c.code}
                  onPress={() => {
                    setCountry({ flag: c.flag, code: c.code });
                    setShowCountryPicker(false);
                  }}
                  className="flex-row items-center gap-3 py-3 px-2.5 rounded-lg active:bg-surface2 dark:active:bg-d-surface2"
                >
                  <Text className="text-xl">{c.flag}</Text>
                  <Text className="text-sm font-semibold text-ink dark:text-d-ink flex-1">{c.name}</Text>
                  <Text className="text-sm font-bold text-muted dark:text-d-muted">{c.code}</Text>
                </Pressable>
              ))}
            </View>
          </Pressable>
        </Pressable>
      </Modal>

      {/* Reusable Dialog */}
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

