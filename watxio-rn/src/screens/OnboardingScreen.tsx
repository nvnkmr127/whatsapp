import React, { useState } from 'react';
import {
  View,
  Text,
  TextInput,
  Pressable,
  KeyboardAvoidingView,
  Platform,
  TouchableWithoutFeedback,
  Keyboard,
  ActivityIndicator,
  Modal,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import {
  MessageCircle,
  ArrowRight,
  ChevronDown,
  QrCode,
  Check,
} from 'lucide-react-native';

import { useTokens } from '@/theme';
import { store } from '@/store';
import { CustomDialog } from '@/components/Dialog';

export default function OnboardingScreen({ navigation }: any) {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();

  const [phoneNumber, setPhoneNumber] = useState('');
  const [country, setCountry] = useState({ flag: '🇺🇸', code: '+1' });
  const [loading, setLoading] = useState(false);
  const [showCountryPicker, setShowCountryPicker] = useState(false);

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
    const trimmed = phoneNumber.trim();
    if (!trimmed) {
      showDialog('Phone Number Required', 'Please enter your business WhatsApp number to continue.');
      return;
    }
    setLoading(true);
    setTimeout(() => {
      setLoading(false);
      store.set({ waNumber: `${country.code} ${trimmed}` });
      navigation.replace('Main');
    }, 1200);
  };

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      className="flex-1 bg-bg dark:bg-d-bg"
    >
      <TouchableWithoutFeedback onPress={Keyboard.dismiss}>
        <View
          className="flex-1 px-6"
          style={{
            paddingTop: insets.top + 60,
            paddingBottom: insets.bottom + 20,
          }}
        >
          {/* Logo & Header */}
          <View className="items-center mb-10">
            {/* The Green Logo Container */}
            <View className="w-20 h-20 bg-accent dark:bg-d-accent rounded-3xl items-center justify-center shadow-lg shadow-accent/30 elevation-md">
              {/* Fake a checkmark inside a message circle for the logo */}
              <View className="relative">
                <MessageCircle size={40} color={tokens.accentInk} strokeWidth={1.5} />
                <View className="absolute inset-0 items-center justify-center">
                  <Check size={18} color={tokens.accentInk} strokeWidth={3} style={{ marginTop: -2 }} />
                </View>
              </View>
            </View>

            <Text className="text-3xl font-extrabold text-ink dark:text-d-ink mt-8 tracking-tight">
              Welcome to Watxio
            </Text>
            <Text className="text-base text-muted dark:text-d-muted text-center mt-3 leading-6 px-5">
              The WhatsApp Business inbox your team has been waiting for.
            </Text>
          </View>

          {/* Form */}
          <View className="w-full">
            <Text className="text-[11px] font-bold text-muted dark:text-d-muted tracking-wider mb-2 uppercase">
              Business WhatsApp Number
            </Text>

            {/* Input Field Container */}
            <View className="flex-row items-center bg-surface dark:bg-d-surface border border-accent dark:border-d-accent rounded-xl px-4 h-14">
              <Pressable onPress={() => setShowCountryPicker(true)} className="flex-row items-center gap-1.5">
                <Text className="text-lg">{country.flag}</Text>
                <ChevronDown size={14} color={tokens.muted} strokeWidth={2} />
                <Text className="text-base font-medium text-ink dark:text-d-ink ml-1">
                  {country.code}
                </Text>
              </Pressable>
              <View className="w-px h-6 bg-hairline dark:bg-d-hairline mx-3" />
              <TextInput
                value={phoneNumber}
                onChangeText={setPhoneNumber}
                placeholder="(415) 555-0118"
                placeholderTextColor={tokens.faint}
                keyboardType="phone-pad"
                className="flex-1 text-base text-ink dark:text-d-ink font-medium h-full"
              />
            </View>

            {/* Continue Button */}
            <Pressable
              onPress={handleContinue}
              className="bg-accent dark:bg-d-accent rounded-xl h-14 flex-row items-center justify-center gap-2 mt-4 active:opacity-90"
            >
              <ArrowRight size={18} color={tokens.accentInk} strokeWidth={2} />
              <Text className="text-base font-semibold text-accent-ink dark:text-d-accent-ink">
                Continue
              </Text>
            </Pressable>
          </View>

          {/* OR Divider */}
          <View className="flex-row items-center my-8">
            <View className="flex-1 h-px bg-divider dark:bg-d-divider" />
            <Text className="mx-4 text-[13px] text-muted dark:text-d-muted font-semibold">
              OR
            </Text>
            <View className="flex-1 h-px bg-divider dark:bg-d-divider" />
          </View>

          {/* Secondary Button */}
          <Pressable
            onPress={() => navigation.navigate('Login')}
            className="bg-surface dark:bg-d-surface rounded-xl h-14 flex-row items-center justify-center gap-2.5 active:opacity-70 shadow-sm shadow-ink/5 elevation-sm border border-hairline dark:border-d-hairline"
          >
            <QrCode size={20} color={tokens.ink} strokeWidth={2} />
            <Text className="text-base font-semibold text-ink dark:text-d-ink">
              Scan QR from web app
            </Text>
          </Pressable>

          {/* Footer Text */}
          <View className="flex-1 justify-end items-center pb-2.5">
            <Text className="text-xs text-muted dark:text-d-muted text-center leading-relaxed">
              By continuing you agree to our{' '}
              <Text className="underline text-ink dark:text-d-ink">Terms</Text> and{' '}
              <Text className="underline text-ink dark:text-d-ink">Privacy policy</Text>. We never read your customer
              conversations.
            </Text>
          </View>
        </View>
      </TouchableWithoutFeedback>

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
    </KeyboardAvoidingView>
  );
}


