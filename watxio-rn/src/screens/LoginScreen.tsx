import React, { useState } from 'react';
import { View, Text, TextInput, Pressable, ScrollView, Modal, ActivityIndicator, Image } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { ChevronDown, QrCode, Mail, Lock, Server } from 'lucide-react-native';
import Svg, { Path } from 'react-native-svg';

import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import type { RootStackParamList } from '@/types';

import { useTokens } from '@/theme';
import { PrimaryButton } from '@/components/Button';
import { store, useGlobalState } from '@/store';
import { CustomDialog } from '@/components/Dialog';
import { api } from '@/services/api';
import { registerForPushNotifications } from '@/services/notifications';

export default function LoginScreen({ navigation }: any) {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  const [globalState, setGlobalState] = useGlobalState();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [serverUrl, setServerUrl] = useState(globalState.baseUrl);
  const [showServerConfig, setShowServerConfig] = useState(false);
  const [loading, setLoading] = useState(false);

  React.useEffect(() => {
    setServerUrl(globalState.baseUrl);
  }, [globalState.baseUrl]);

  const nav = navigation;

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

  const handleContinue = async () => {
    const trimmedEmail = email.trim();
    const trimmedPassword = password;

    if (!trimmedEmail || !trimmedPassword) {
      showDialog('Required Fields', 'Please enter your email and password.');
      return;
    }

    api.setBaseUrl(serverUrl);
    setGlobalState({ baseUrl: serverUrl });

    setLoading(true);
    try {
      const response = await api.post('/v1/mobile/auth/login', {
        email: trimmedEmail,
        password: trimmedPassword,
      });

      const userTeams = response.teams || [];
      const activeTeam = userTeams[0] || null;

      // Fetch numbers if available — keep token/teamId set in api directly.
      let teamNumbers: any[] = [];
      if (response.token && activeTeam) {
        api.setToken(response.token);
        api.setTeamId(activeTeam.id);
        try {
          teamNumbers = await api.get('/v1/mobile/auth/numbers');
        } catch (err) {
          console.warn('Failed to load team numbers during login', err);
        }
      }

      const activeNumberObj = teamNumbers[0] || null;

      // Commit full session to store — api.setToken/setTeamId/setBaseUrl are
      // synced inside store.set, so they stay consistent.
      store.set({
        token: response.token,
        user: response.user,
        teams: userTeams,
        activeTeamId: activeTeam ? activeTeam.id : null,
        businessName: activeTeam ? activeTeam.name : 'Watxio Workspace',
        waNumber: activeNumberObj ? activeNumberObj.display_number : '+1 (415) 555-0118',
        plan: 'Business Plan',
        userName: response.user.name,
        userRole: response.user.role || 'Member',
        numbers: teamNumbers,
        websocket: response.websocket,
      });

      // Register FCM token immediately after login while api token/baseUrl are
      // guaranteed to be set. Fire-and-forget — do not block navigation.
      registerForPushNotifications().catch((e) =>
        console.warn('[FCM] Registration failed after login:', e)
      );

      setLoading(false);
      nav.reset({ index: 0, routes: [{ name: 'Main' }] });
    } catch (err: any) {
      setLoading(false);
      showDialog('Login Failed', err.message || 'Invalid email or password.');
    }
  };

  const handleScanQr = () => {
    // QR code pairing flow (simulated pairing code lookup/finalize)
    setLoading(true);
    setTimeout(() => {
      setLoading(false);
      showDialog('QR Pair Success', 'Paired successfully with your active browser session.', [
        { text: 'Continue', onPress: () => nav.reset({ index: 0, routes: [{ name: 'Main' }] }) }
      ]);
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
        <View className="w-16 h-16 rounded-xl items-center justify-center mt-8 mb-6 overflow-hidden shadow-sm">
          <Image
            source={require('../assets/app logo.png')}
            style={{ width: 64, height: 64, borderRadius: 12 }}
            resizeMode="cover"
          />
        </View>

        <Text className="text-[26px] font-bold tracking-[-0.6px] text-ink dark:text-d-ink text-center">
          Login to Watxio
        </Text>
        <Text className="text-sm text-muted dark:text-d-muted text-center mt-2.5 leading-[21px] max-w-[280px]">
          Sign in using your workspace administrator or agent credentials.
        </Text>

        {/* Form Fields */}
        <View className="w-full mt-6 gap-3.5">
          <View>
            <Text className="text-xs text-muted dark:text-d-muted font-medium mb-2">
              Email Address
            </Text>
            <View className="flex-row items-center bg-surface dark:bg-d-surface rounded-lg px-4 h-12">
              <Mail size={16} color={tokens.muted} />
              <TextInput
                value={email}
                onChangeText={setEmail}
                placeholder="admin@example.com"
                placeholderTextColor={tokens.muted}
                className="flex-1 text-sm text-ink dark:text-d-ink p-0 ml-2.5"
                keyboardType="email-address"
                autoCapitalize="none"
              />
            </View>
          </View>

          <View>
            <Text className="text-xs text-muted dark:text-d-muted font-medium mb-2">
              Password
            </Text>
            <View className="flex-row items-center bg-surface dark:bg-d-surface rounded-lg px-4 h-12">
              <Lock size={16} color={tokens.muted} />
              <TextInput
                value={password}
                onChangeText={setPassword}
                placeholder="Password"
                placeholderTextColor={tokens.muted}
                className="flex-1 text-sm text-ink dark:text-d-ink p-0 ml-2.5"
                secureTextEntry
              />
            </View>
          </View>
        </View>

        <View className="w-full mt-5">
          <PrimaryButton full size="lg" label="Sign In" onPress={handleContinue} />
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

        {/* Collapsible Server Config Settings */}
        <View className="w-full mt-6 border-t border-hairline dark:border-d-hairline pt-4">
          <Pressable
            onPress={() => setShowServerConfig(!showServerConfig)}
            className="flex-row items-center justify-between py-1"
          >
            <View className="flex-row items-center gap-2">
              <Server size={14} color={tokens.muted} />
              <Text className="text-xs font-semibold text-muted dark:text-d-muted">Server URL Configuration</Text>
            </View>
            <ChevronDown size={14} color={tokens.muted} style={{ transform: [{ rotate: showServerConfig ? '180deg' : '0deg' }] }} />
          </Pressable>

          {showServerConfig && (
            <View className="mt-2.5 bg-surface dark:bg-d-surface p-3.5 rounded-lg gap-2 border border-hairline dark:border-d-hairline">
              <TextInput
                value={serverUrl}
                onChangeText={setServerUrl}
                placeholder="e.g. http://localhost:8000/api"
                placeholderTextColor={tokens.faint}
                className="bg-surface2 dark:bg-d-surface2 p-2 text-xs rounded text-ink dark:text-d-ink font-mono"
                autoCapitalize="none"
              />
            </View>
          )}
        </View>

        <View className="flex-1" />

        <Text className="text-[11.5px] text-muted dark:text-d-muted text-center leading-[18px] px-2 pt-6">
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
              <Text className="text-[12px] text-muted dark:text-d-muted text-center mt-1">Authenticating against credentials</Text>
            </View>
          </View>
        </View>
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
