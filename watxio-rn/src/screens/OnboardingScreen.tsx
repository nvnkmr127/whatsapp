import React, { useState, useEffect } from 'react';
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
  ScrollView,
  Image,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import {
  MessageCircle,
  ChevronDown,
  QrCode,
  Check,
  Lock,
  Mail,
  Server,
  Smartphone,
} from 'lucide-react-native';
import Svg, { Path } from 'react-native-svg';
import { CameraView, useCameraPermissions } from 'expo-camera';

import { useTokens } from '@/theme';
import { store, useGlobalState } from '@/store';
import { CustomDialog } from '@/components/Dialog';
import { api } from '@/services/api';

export default function OnboardingScreen({ navigation }: any) {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  const [globalState, setGlobalState] = useGlobalState();

  // Login Mode: 'otp' or 'email'
  const [loginMode, setLoginMode] = useState<'otp' | 'email'>('otp');

  // Input Fields
  const [phoneNumber, setPhoneNumber] = useState('');
  const [country, setCountry] = useState({ flag: '🇺🇸', code: '+1' });
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [otp, setOtp] = useState('');

  // States
  const [isOtpSent, setIsOtpSent] = useState(false);
  const [serverUrl, setServerUrl] = useState(globalState.baseUrl);
  const [showServerConfig, setShowServerConfig] = useState(false);
  const [loading, setLoading] = useState(false);
  const [showCountryPicker, setShowCountryPicker] = useState(false);

  const [cameraPermission, requestCameraPermission] = useCameraPermissions();
  const [showQrScanner, setShowQrScanner] = useState(false);
  const [checkingSession, setCheckingSession] = useState(true);

  useEffect(() => {
    const checkSession = async () => {
      try {
        const hasSession = await store.loadSession();
        if (hasSession) {
          navigation.replace('Main');
        } else {
          setCheckingSession(false);
        }
      } catch (e) {
        console.error('Error loading session:', e);
        setCheckingSession(false);
      }
    };
    checkSession();
  }, [navigation]);


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

  const handleScanQr = async () => {
    if (!cameraPermission || !cameraPermission.granted) {
      const result = await requestCameraPermission();
      if (!result.granted) {
        showDialog('Permission Denied', 'Camera permission is required to scan the pairing QR code.');
        return;
      }
    }
    setShowQrScanner(true);
  };

  const handleBarcodeScanned = async (data: string) => {
    setShowQrScanner(false);
    if (!data) return;

    setLoading(true);
    try {
      const payload = JSON.parse(data);
      if (!payload.token) {
        if (payload.baseUrl && payload.email) {
          let targetBaseUrl = payload.baseUrl;

          // Auto-replace localhost/127.0.0.1 with Android emulator host IP if needed
          if (Platform.OS === 'android') {
            targetBaseUrl = targetBaseUrl
              .replace('localhost', '10.0.2.2')
              .replace('127.0.0.1', '10.0.2.2');
          }

          if (targetBaseUrl.endsWith('/v1')) {
            targetBaseUrl = targetBaseUrl.substring(0, targetBaseUrl.length - 3); // trim '/v1'
          }

          api.setBaseUrl(targetBaseUrl);
          setServerUrl(targetBaseUrl);
          setGlobalState({ baseUrl: targetBaseUrl });
          setEmail(payload.email);
          setLoginMode('email');
          setShowServerConfig(true);
          setLoading(false);

          showDialog(
            'Connection Configured',
            'Your server URL and email have been pre-filled. Please enter your password to sign in and complete pairing.'
          );
          return;
        }

        throw new Error('Missing pairing token. Please generate a setup token on the web dashboard first, or log in manually.');
      }

      // Base URL normalization
      let targetBaseUrl = payload.baseUrl || globalState.baseUrl;

      // Auto-replace localhost/127.0.0.1 with Android emulator host IP if needed
      if (Platform.OS === 'android') {
        targetBaseUrl = targetBaseUrl
          .replace('localhost', '10.0.2.2')
          .replace('127.0.0.1', '10.0.2.2');
      }

      if (targetBaseUrl.endsWith('/v1')) {
        targetBaseUrl = targetBaseUrl.substring(0, targetBaseUrl.length - 3); // trim '/v1'
      }

      // Configure api client with scanned endpoint & token
      api.setBaseUrl(targetBaseUrl);
      api.setToken(payload.token);
      if (payload.teamId) {
        api.setTeamId(payload.teamId);
      }

      // Verify and finalize with backend
      const response = await api.post('/v1/mobile/auth/finalize');

      const userTeams = response.teams || [];
      const activeTeam = userTeams[0] || null;
      const teamNumbers = response.numbers || [];
      const activeNumberObj = teamNumbers[0] || null;

      store.set({
        token: payload.token,
        user: response.user,
        teams: userTeams,
        activeTeamId: activeTeam ? activeTeam.id : null,
        businessName: activeTeam ? activeTeam.name : 'Watxio Workspace',
        waNumber: activeNumberObj ? activeNumberObj.display_number : '+1 (415) 555-0118',
        plan: 'Business Plan',
        userName: response.user.name,
        userRole: response.user.role || 'Member',
        numbers: teamNumbers,
        baseUrl: targetBaseUrl,
        websocket: response.websocket,
      });

      setLoading(false);
      showDialog('Pairing Successful', `Authenticated as ${response.user.name} for ${activeTeam ? activeTeam.name : 'Watxio'}.`, [
        { text: 'Continue', onPress: () => navigation.replace('Main') }
      ]);
    } catch (err: any) {
      setLoading(false);
      showDialog(
        'Pairing Failed',
        err.message || 'Could not verify the pairing QR code. Please check your network and try again.'
      );
    }
  };

  const handleContinue = async () => {
    // Save/Update server URL configuration
    api.setBaseUrl(serverUrl);
    setGlobalState({ baseUrl: serverUrl });

    if (loginMode === 'email') {
      const trimmedEmail = email.trim();
      const trimmedPassword = password;

      if (!trimmedEmail || !trimmedPassword) {
        showDialog('Required Fields', 'Please enter your email and password.');
        return;
      }

      setLoading(true);
      try {
        const response = await api.post('/v1/mobile/auth/login', {
          email: trimmedEmail,
          password: trimmedPassword,
        });

        const userTeams = response.teams || [];
        const activeTeam = userTeams[0] || null;

        // Fetch numbers if available
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

        setLoading(false);
        navigation.replace('Main');
      } catch (err: any) {
        setLoading(false);
        showDialog('Login Failed', err.message || 'Invalid email or password.');
      }
    } else {
      // OTP LOGIN FLOW
      if (!isOtpSent) {
        const trimmedPhone = phoneNumber.trim();
        if (!trimmedPhone) {
          showDialog('Phone Number Required', 'Please enter your business WhatsApp number.');
          return;
        }

        const fullPhone = trimmedPhone; // Don't force country code, let it match exactly what the user types
        setLoading(true);

        try {
          await api.post('/v1/mobile/auth/send-otp', {
            phone: fullPhone,
          });
          setLoading(false);
          setIsOtpSent(true);
          showDialog('OTP Sent', `A verification code has been sent to ${fullPhone}.`);
        } catch (err: any) {
          setLoading(false);
          // Auto-fallback: If strict match fails, try with country code as a fallback
          try {
            const fallbackPhone = `${country.code}${trimmedPhone.replace(/\D/g, '')}`;
            await api.post('/v1/mobile/auth/send-otp', {
              phone: fallbackPhone,
            });
            setLoading(false);
            setIsOtpSent(true);
            showDialog('OTP Sent', `A verification code has been sent to ${fallbackPhone}.`);
            // Update the phone number state so the verify step uses the correct format
            setPhoneNumber(fallbackPhone);
          } catch (fallbackErr: any) {
            setLoading(false);
            showDialog('OTP Failed', fallbackErr.message || 'Failed to send OTP. Ensure your number is registered exactly as shown.');
          }
        }
      } else {
        const trimmedOtp = otp.trim();
        if (!trimmedOtp) {
          showDialog('OTP Required', 'Please enter the verification code.');
          return;
        }

        const fullPhone = phoneNumber.trim();
        setLoading(true);

        try {
          const response = await api.post('/v1/mobile/auth/login-otp', {
            phone: fullPhone,
            otp: trimmedOtp,
          });

          const userTeams = response.teams || [];
          const activeTeam = userTeams[0] || null;

          // Fetch numbers if available
          let teamNumbers: any[] = [];
          if (response.token && activeTeam) {
            api.setToken(response.token);
            api.setTeamId(activeTeam.id);
            try {
              teamNumbers = await api.get('/v1/mobile/auth/numbers');
            } catch (err) {
              console.warn('Failed to load team numbers during OTP login', err);
            }
          }

          const activeNumberObj = teamNumbers[0] || null;

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

          setLoading(false);
          navigation.replace('Main');
        } catch (err: any) {
          setLoading(false);
          showDialog('Verification Failed', err.message || 'Invalid or expired OTP code.');
        }
      }
    }
  };

  if (checkingSession) {
    return (
      <View style={{ flex: 1, backgroundColor: '#0F1515', alignItems: 'center', justifyContent: 'center' }}>
        <ActivityIndicator size="large" color="#5bb393" />
      </View>
    );
  }

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      className="flex-1 bg-[#0F1515]"
    >
      <TouchableWithoutFeedback onPress={Keyboard.dismiss}>
        <ScrollView
          className="flex-1"
          contentContainerStyle={{
            flexGrow: 1,
            paddingHorizontal: 24,
            paddingTop: insets.top + 40,
            paddingBottom: insets.bottom + 24,
            alignItems: 'center',
          }}
          keyboardShouldPersistTaps="handled"
        >
          <Text className="text-[28px] font-extrabold text-white text-center tracking-tight">
            {isOtpSent ? 'Verify Code' : 'Welcome to'}
          </Text>
          {/* Squirclish Logo Container */}
          <View className="w-18 h-18 rounded-[22px] items-center justify-center shadow-lg mt-10 mb-6 overflow-hidden bg-transparent">
            <Image
              source={require('../assets/app logo.png')}
              style={{ width: 150, height: 72, borderRadius: 22 }}
              resizeMode="cover"
            />
          </View>

          {/* Welcome Text */}

          <Text className="text-sm text-gray-400 text-center mt-2.5 leading-relaxed px-5 max-w-[310px]">
            {isOtpSent
              ? `Enter the 6-digit OTP verification code sent to ${country.code} ${phoneNumber}.`
              : 'The WhatsApp Business inbox your team has been waiting for.'}
          </Text>

          {/* Form Fields */}
          <View className="w-full mt-10 gap-5">
            {loginMode === 'email' ? (
              // Email / Password Form
              <>
                <View>
                  <Text className="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2">
                    Email Address
                  </Text>
                  <View className="flex-row items-center bg-[#141A1A] border border-[#242E2E] rounded-xl px-4 h-14">
                    <Mail size={18} color="#8A9999" />
                    <TextInput
                      value={email}
                      onChangeText={setEmail}
                      placeholder="admin@example.com"
                      placeholderTextColor="#4C5757"
                      keyboardType="email-address"
                      autoCapitalize="none"
                      className="flex-1 text-sm text-white font-medium h-full ml-3"
                    />
                  </View>
                </View>

                <View>
                  <Text className="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2">
                    Password
                  </Text>
                  <View className="flex-row items-center bg-[#141A1A] border border-[#242E2E] rounded-xl px-4 h-14">
                    <Lock size={18} color="#8A9999" />
                    <TextInput
                      value={password}
                      onChangeText={setPassword}
                      placeholder="••••••••"
                      placeholderTextColor="#4C5757"
                      secureTextEntry
                      className="flex-1 text-sm text-white font-medium h-full ml-3"
                    />
                  </View>
                </View>
              </>
            ) : (
              // OTP Phone / Code Form
              <>
                {!isOtpSent ? (
                  <View>
                    <Text className="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2">
                      Business WhatsApp number
                    </Text>
                    <View className="flex-row items-center bg-[#141A1A] border border-[#242E2E] rounded-xl px-4 h-14">
                      <Pressable onPress={() => setShowCountryPicker(true)} className="flex-row items-center gap-1">
                        <Text className="text-lg">{country.flag}</Text>
                        <Text className="text-sm font-bold text-white ml-1">
                          {country.code}
                        </Text>
                        <ChevronDown size={14} color="#8A9999" strokeWidth={2.5} className="ml-0.5" />
                      </Pressable>
                      <View className="w-px h-5 bg-[#242E2E] mx-3.5" />
                      <TextInput
                        value={phoneNumber}
                        onChangeText={setPhoneNumber}
                        placeholder="415 555 0118"
                        placeholderTextColor="#4C5757"
                        keyboardType="phone-pad"
                        className="flex-1 text-sm text-white font-semibold h-full"
                      />
                    </View>
                  </View>
                ) : (
                  <View>
                    <Text className="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2">
                      Verification OTP Code
                    </Text>
                    <View className="flex-row items-center bg-[#141A1A] border border-[#5bb393] rounded-xl px-4 h-14">
                      <Lock size={18} color="#5bb393" />
                      <TextInput
                        value={otp}
                        onChangeText={setOtp}
                        placeholder="000000"
                        placeholderTextColor="#4C5757"
                        keyboardType="number-pad"
                        className="flex-1 text-base text-white font-bold h-full ml-3 tracking-[6px] text-center"
                        maxLength={6}
                      />
                    </View>
                    <Pressable
                      onPress={() => setIsOtpSent(false)}
                      className="mt-2.5 self-start"
                    >
                      <Text className="text-xs text-[#5bb393] font-semibold">Change Phone Number</Text>
                    </Pressable>
                  </View>
                )}
              </>
            )}

            {/* Continue / Action Button */}
            <Pressable
              onPress={handleContinue}
              className="bg-[#5bb393] rounded-xl h-14 flex-row items-center justify-center gap-2 mt-2 active:opacity-90"
            >
              <Text className="text-sm font-bold text-[#0F1515]">
                {loginMode === 'email' ? 'Sign In' : isOtpSent ? 'Verify & Continue' : 'Continue'}
              </Text>
            </Pressable>
          </View>

          {/* Or Divider */}
          {!isOtpSent && (
            <>
              <View className="w-full flex-row items-center gap-4 my-6">
                <View className="flex-1 h-px bg-[#242E2E]" />
                <Text className="text-[11px] text-gray-500 font-semibold uppercase tracking-wider">or</Text>
                <View className="flex-1 h-px bg-[#242E2E]" />
              </View>

              {/* Scan QR Button */}
              <Pressable
                onPress={handleScanQr}
                className="w-full h-14 rounded-xl bg-[#141A1A] border border-[#242E2E] flex-row items-center justify-center gap-2.5 active:bg-[#1C2424]"
              >
                <QrCode size={18} color="white" strokeWidth={1.8} />
                <Text className="text-white text-sm font-bold">Scan QR from web app</Text>
              </Pressable>
            </>
          )}

          {/* Switch Login Method Link */}
          {!isOtpSent && (
            <Pressable
              onPress={() => {
                setLoginMode(loginMode === 'email' ? 'otp' : 'email');
              }}
              className="mt-6 py-2"
            >
              <Text className="text-xs text-[#5bb393] font-bold">
                {loginMode === 'email' ? 'Sign in with phone number instead' : 'Sign in with email & password instead'}
              </Text>
            </Pressable>
          )}

          {/* Collapsible Server Config Settings */}
          <View className="w-full mt-6 border-t border-[#242E2E] pt-4">
            <Pressable
              onPress={() => setShowServerConfig(!showServerConfig)}
              className="flex-row items-center justify-between py-1"
            >
              <View className="flex-row items-center gap-2">
                <Server size={14} color="#8A9999" />
                <Text className="text-xs font-semibold text-gray-500">Server URL Configuration</Text>
              </View>
              <ChevronDown size={14} color="#8A9999" style={{ transform: [{ rotate: showServerConfig ? '180deg' : '0deg' }] }} />
            </Pressable>

            {showServerConfig && (
              <View className="mt-2.5 bg-[#141A1A] p-3.5 rounded-lg gap-2 border border-[#242E2E]">
                <Text className="text-[10px] text-gray-400 leading-relaxed">
                  Modify the API server base host if you are connecting to a remote staging server, a local network IP address, or custom port.
                </Text>
                <TextInput
                  value={serverUrl}
                  onChangeText={setServerUrl}
                  placeholder="e.g. http://192.168.1.50:8000/api"
                  placeholderTextColor="#4C5757"
                  className="bg-[#0F1515] p-2 text-xs rounded text-white font-mono mt-1 border border-[#242E2E]"
                  autoCapitalize="none"
                />
              </View>
            )}
          </View>

          {/* Footer Text */}
          <View className="flex-1 justify-end items-center pb-2 pt-10">
            <Text className="text-[10.5px] text-gray-500 text-center leading-relaxed max-w-[280px]">
              By continuing you agree to our{' '}
              <Text className="underline text-gray-400 font-medium">Terms</Text> and{' '}
              <Text className="underline text-gray-400 font-medium">Privacy policy</Text>. We never read your customer
              conversations.
            </Text>
          </View>
        </ScrollView>
      </TouchableWithoutFeedback>

      {/* QR Code Scanner Modal */}
      <Modal transparent visible={showQrScanner} animationType="slide" onRequestClose={() => setShowQrScanner(false)}>
        <View className="flex-1 bg-black">
          {cameraPermission?.granted ? (
            <CameraView
              style={{ flex: 1 }}
              facing="back"
              onBarcodeScanned={({ data }) => {
                if (data) {
                  handleBarcodeScanned(data);
                }
              }}
            >
              {/* Overlay Frame */}
              <View className="flex-1 bg-black/50 justify-between p-6">
                {/* Header */}
                <View className="items-center mt-12">
                  <Text className="text-white text-lg font-bold">Scan Web App QR Code</Text>
                  <Text className="text-gray-300 text-xs text-center mt-1">
                    Locate the Mobile Setup QR code in settings on the web dashboard
                  </Text>
                </View>

                {/* Target Frame Area */}
                <View className="items-center justify-center">
                  <View className="w-64 h-64 border-2 border-[#5bb393] rounded-2xl items-center justify-center bg-transparent" />
                </View>

                {/* Footer Cancel Button */}
                <View className="items-center mb-10">
                  <Pressable
                    onPress={() => setShowQrScanner(false)}
                    className="px-6 py-3 rounded-full bg-white/20 active:bg-white/30"
                  >
                    <Text className="text-white text-sm font-semibold">Cancel Scan</Text>
                  </Pressable>
                </View>
              </View>
            </CameraView>
          ) : (
            <View className="flex-1 justify-center items-center p-6 gap-4 bg-[#0F1515]">
              <Text className="text-center text-white font-semibold">
                Camera permission is required to scan the pairing QR code.
              </Text>
              <Pressable
                onPress={requestCameraPermission}
                className="bg-[#5bb393] px-6 py-3 rounded-xl active:opacity-90"
              >
                <Text className="text-[#0F1515] font-bold">Grant Permission</Text>
              </Pressable>
              <Pressable onPress={() => setShowQrScanner(false)} className="mt-2">
                <Text className="text-gray-400 text-sm font-semibold">Cancel</Text>
              </Pressable>
            </View>
          )}
        </View>
      </Modal>

      {/* Loading Modal Overlay */}
      <Modal transparent visible={loading} animationType="fade">
        <View className="flex-1 bg-black/40 items-center justify-center">
          <View className="bg-[#141A1A] p-6 rounded-2xl items-center gap-3.5 shadow-xl max-w-[280px] border border-[#242E2E]">
            <ActivityIndicator size="large" color="#5bb393" />
            <View className="items-center">
              <Text className="text-base font-bold text-white text-center">Processing...</Text>
              <Text className="text-[12px] text-gray-400 text-center mt-1">Connecting to API Host</Text>
            </View>
          </View>
        </View>
      </Modal>

      {/* Country Picker Modal */}
      <Modal transparent visible={showCountryPicker} animationType="slide">
        <Pressable onPress={() => setShowCountryPicker(false)} className="flex-1 bg-black/40 justify-end">
          <Pressable onPress={() => { }} className="bg-[#141A1A] rounded-t-2xl p-5 gap-3 border-t border-[#242E2E]" style={{ paddingBottom: insets.bottom + 16 }}>
            <View className="flex-row items-center justify-between border-b border-[#242E2E] pb-2.5">
              <Text className="text-base font-bold text-white">Select Country</Text>
              <Pressable onPress={() => setShowCountryPicker(false)}>
                <Text className="text-[#5bb393] font-semibold text-sm">Cancel</Text>
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
                  className="flex-row items-center gap-3 py-3 px-2.5 rounded-lg active:bg-[#1C2424]"
                >
                  <Text className="text-xl">{c.flag}</Text>
                  <Text className="text-sm font-semibold text-white flex-1">{c.name}</Text>
                  <Text className="text-sm font-bold text-gray-400">{c.code}</Text>
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
