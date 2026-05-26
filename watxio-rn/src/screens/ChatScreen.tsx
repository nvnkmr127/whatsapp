import React, { useEffect, useRef, useState } from 'react';
import {
  View, Text, ScrollView, KeyboardAvoidingView, Platform, Animated, Pressable, Keyboard, BackHandler,
  Modal, FlatList, ActivityIndicator,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation, useRoute, RouteProp } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { ChevronLeft, Phone, MoreHorizontal, BellOff } from 'lucide-react-native';

import { useTokens } from '@/theme';
import { CHAT_MESSAGES, TEMPLATES } from '@/data';
import type { ChatMessage, RootStackParamList } from '@/types';
import { Avatar } from '@/components/Avatar';
import { Bubble } from '@/components/Bubble';
import { IconButton } from '@/components/Button';
import { Composer } from '@/components/PhoneBubbleBar';
import { CustomDialog } from '@/components/Dialog';

export default function ChatScreen() {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  const nav = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
  const route = useRoute<RouteProp<RootStackParamList, 'Chat'>>();
  const contact = route.params.contact;

  const [messages, setMessages] = useState<ChatMessage[]>(CHAT_MESSAGES);
  const [draft, setDraft] = useState('');
  const [typing, setTyping] = useState(false);
  
  // New States for Actions
  const [isCalling, setIsCalling] = useState(false);
  const [callTime, setCallTime] = useState(0);
  const [showOptions, setShowOptions] = useState(false);
  const [showAttachmentMenu, setShowAttachmentMenu] = useState(false);
  const [showTemplatePicker, setShowTemplatePicker] = useState(false);
  const [isMuted, setIsMuted] = useState(false);
  const [isRecording, setIsRecording] = useState(false);
  const [recordingSeconds, setRecordingSeconds] = useState(0);

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


  const scroller = useRef<ScrollView>(null);

  useEffect(() => {
    const t = setTimeout(() => scroller.current?.scrollToEnd({ animated: true }), 60);
    return () => clearTimeout(t);
  }, [messages, typing]);

  useEffect(() => {
    const unsubscribe = nav.addListener('beforeRemove', () => {
      Keyboard.dismiss();
    });
    return unsubscribe;
  }, [nav]);

  const handleBack = () => {
    Keyboard.dismiss();
    setTimeout(() => {
      nav.goBack();
    }, 150);
  };

  useEffect(() => {
    const backAction = () => {
      handleBack();
      return true;
    };
    const backHandler = BackHandler.addEventListener('hardwareBackPress', backAction);
    return () => backHandler.remove();
  }, []);

  // Call timer effect
  useEffect(() => {
    let interval: NodeJS.Timeout;
    if (isCalling) {
      setCallTime(0);
      interval = setInterval(() => {
        setCallTime((t) => t + 1);
      }, 1000);
    }
    return () => clearInterval(interval);
  }, [isCalling]);

  // Voice recording timer effect
  useEffect(() => {
    let interval: NodeJS.Timeout;
    if (isRecording) {
      setRecordingSeconds(0);
      interval = setInterval(() => {
        setRecordingSeconds((s) => s + 1);
      }, 1000);
    }
    return () => clearInterval(interval);
  }, [isRecording]);

  const send = () => {
    const text = draft.trim();
    if (!text) return;
    setMessages((m) => [...m, { kind: 'out', text, time: 'now', status: 'sent' }]);
    setDraft('');
    setTimeout(() => setTyping(true), 600);

    const lower = text.toLowerCase();
    let replyText = 'Got it — thanks 🙌';

    if (lower.match(/\b(hour|open|where|location|address|shop|store)\b/)) {
      replyText = 'We’re open Mon–Sat, 8am–6pm at Folsom & 7th.';
    } else if (lower.match(/\b(ship|track|delivery|dhl|receive|courier|mail)\b/)) {
      replyText = 'Most orders ship within 24h via DHL Express.';
    } else if (lower.match(/\b(wholesale|bulk|partner|b2b|kg|terms)\b/)) {
      replyText = 'Thanks for reaching out about wholesale. Min order is 5kg.';
    } else if (lower.match(/\b(return|refund|exchange|unopened)\b/)) {
      replyText = 'Returns accepted within 14 days, beans unopened.';
    } else if (lower.match(/\b(hi|hello|hey|hola|greetings)\b/)) {
      replyText = 'Hey there! How can Acme Coffee help you today? ☕';
    } else {
      const genericReplies = [
        'Awesome, let me check that for you!',
        'Sounds good, I will update our team.',
        'Thanks for the details! We will process it shortly.',
        'Perfect, let me know if there is anything else I can help you with!',
      ];
      replyText = genericReplies[Math.floor(Math.random() * genericReplies.length)];
    }

    setTimeout(() => {
      setTyping(false);
      setMessages((m) => [...m, { kind: 'in', text: replyText, time: 'now' }]);
    }, 2000);
  };

  return (
    <View
      className="flex-1 bg-bg dark:bg-d-bg"
    >
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        className="flex-1"
      >
        {/* Header */}
        <View
          className="flex-row items-center gap-[10px] px-3 pb-3 bg-bg dark:bg-d-bg"
          style={{ paddingTop: insets.top + 4 }}
        >
          <IconButton icon={ChevronLeft} onPress={handleBack} />
          <Pressable
            onPress={() => nav.navigate('Contact')}
            className="flex-1 flex-row items-center gap-[10px] min-w-0"
          >
            <Avatar name={contact.name} size={36} dot={tokens.ok} />
            <View className="flex-1 min-w-0">
              <View className="flex-row items-center gap-1.5">
                <Text numberOfLines={1} className="text-[15px] font-bold text-ink dark:text-d-ink">
                  {contact.name}
                </Text>
                {isMuted && <BellOff size={11} color={tokens.muted} strokeWidth={2} />}
              </View>
              <Text className={`text-[11.5px] ${typing ? 'text-accent dark:text-d-accent' : 'text-muted dark:text-d-muted'}`}>
                {typing ? 'typing…' : `${contact.tag} · 11 orders · $842 LTV`}
              </Text>
            </View>
          </Pressable>
          <IconButton icon={Phone} onPress={() => setIsCalling(true)} />
          <IconButton icon={MoreHorizontal} onPress={() => setShowOptions(true)} />
        </View>

        {/* Messages */}
        <ScrollView
          ref={scroller}
          contentContainerStyle={{ paddingHorizontal: 14, paddingTop: 4, paddingBottom: 12, gap: 6 }}
        >
          {messages.map((m, i) => {
            if (m.kind === 'date') {
              return (
                <View
                  key={i}
                  className="self-center px-2.5 py-[3px] rounded-full bg-surface2 dark:bg-d-surface2 my-1"
                >
                  <Text className="text-muted dark:text-d-muted text-[11px] font-semibold">{m.text}</Text>
                </View>
              );
            }
            return (
              <Bubble key={i} kind={m.kind} time={m.time} status={m.status} variant="tail">
                {m.text}
              </Bubble>
            );
          })}
          {typing ? <TypingDots /> : null}
        </ScrollView>

        {isRecording ? (
          <View className="bg-surface dark:bg-d-surface px-3 py-2.5 flex-row items-center justify-between border-t border-hairline dark:border-d-hairline">
            <View className="flex-row items-center gap-2">
              <View className="w-2.5 h-2.5 rounded-full bg-danger dark:bg-d-danger animate-pulse" />
              <Text className="text-danger dark:text-d-danger font-semibold text-sm">🎙️ Recording...</Text>
              <Text className="text-ink dark:text-d-ink text-sm font-mono ml-2">
                {Math.floor(recordingSeconds / 60)}:{(recordingSeconds % 60).toString().padStart(2, '0')}
              </Text>
            </View>
            <View className="flex-row gap-2">
              <Pressable
                onPress={() => setIsRecording(false)}
                className="px-3.5 py-1.5 rounded-full bg-surface2 dark:bg-d-surface2 active:opacity-75"
              >
                <Text className="text-muted dark:text-d-muted text-xs font-bold">Cancel</Text>
              </Pressable>
              <Pressable
                onPress={() => {
                  setIsRecording(false);
                  const duration = recordingSeconds || 3;
                  const timeStr = `${Math.floor(duration / 60)}:${(duration % 60).toString().padStart(2, '0')}`;
                  setMessages((m) => [...m, { kind: 'out', text: `🎙️ Voice message (${timeStr})`, time: 'now', status: 'sent' }]);
                  setTimeout(() => setTyping(true), 600);
                  setTimeout(() => {
                    setTyping(false);
                    setMessages((m) => [...m, { kind: 'in', text: 'Received your voice note! Processing audio assistant reply...', time: 'now' }]);
                  }, 2000);
                }}
                className="px-3.5 py-1.5 rounded-full bg-accent dark:bg-d-accent active:opacity-85"
              >
                <Text className="text-accent-ink dark:text-d-accent-ink text-xs font-bold">Stop & Send</Text>
              </Pressable>
            </View>
          </View>
        ) : (
          <Composer
            value={draft}
            onChange={setDraft}
            onSend={send}
            onAttach={() => setShowAttachmentMenu(true)}
            onVoice={() => {
              setIsRecording(true);
              setRecordingSeconds(0);
            }}
          />
        )}
        <View className="bg-surface dark:bg-d-surface" style={{ height: insets.bottom }} />
      </KeyboardAvoidingView>

      {/* Simulated Calling Overlay */}
      {isCalling && (
        <Modal transparent visible={isCalling} animationType="slide">
          <View className="flex-1 bg-ink/95 dark:bg-black/95 items-center justify-center px-6">
            <View className="items-center gap-6">
              <Avatar name={contact.name} size={110} ring={tokens.ok} />
              <View className="items-center">
                <Text className="text-white text-2xl font-bold mt-2">{contact.name}</Text>
                <Text className="text-ok text-sm font-semibold tracking-wide mt-1 uppercase">
                  Calling via WhatsApp API...
                </Text>
                <Text className="text-white/60 text-sm mt-1 font-mono">
                  {Math.floor(callTime / 60)}:{(callTime % 60).toString().padStart(2, '0')}
                </Text>
              </View>
              <View className="flex-row items-center justify-center w-24 h-24 bg-ok/10 rounded-full mt-4">
                <View className="w-16 h-16 bg-ok/20 rounded-full items-center justify-center">
                  <Phone size={32} color="#FFFFFF" strokeWidth={1.8} />
                </View>
              </View>
              <Pressable
                onPress={() => setIsCalling(false)}
                className="w-16 h-16 bg-danger rounded-full items-center justify-center mt-12 active:opacity-90"
              >
                <Phone size={24} color="#FFFFFF" strokeWidth={1.8} style={{ transform: [{ rotate: '135deg' }] }} />
              </Pressable>
            </View>
          </View>
        </Modal>
      )}

      {/* Options Sheet Menu */}
      {showOptions && (
        <Modal transparent visible={showOptions} animationType="fade">
          <Pressable onPress={() => setShowOptions(false)} className="flex-1 bg-black/40 justify-end">
            <View className="bg-surface dark:bg-d-surface rounded-t-2xl p-4 gap-2" style={{ paddingBottom: insets.bottom + 16 }}>
              <View className="items-center pb-2 border-b border-hairline dark:border-d-hairline">
                <Text className="text-xs text-muted dark:text-d-muted font-bold uppercase tracking-wider">Chat Options</Text>
              </View>
              <Pressable
                onPress={() => {
                  const nextMuted = !isMuted;
                  setIsMuted(nextMuted);
                  setShowOptions(false);
                  showDialog(nextMuted ? 'Muted' : 'Unmuted', `Notifications from ${contact.name} have been ${nextMuted ? 'muted' : 'unmuted'}.`);
                }}
                className="flex-row items-center justify-between py-3.5 px-2 active:bg-surface2 dark:active:bg-d-surface2 rounded-md"
              >
                <Text className="text-sm font-semibold text-ink dark:text-d-ink">
                  {isMuted ? '🔊 Unmute Notifications' : '🔇 Mute Notifications'}
                </Text>
              </Pressable>
              <Pressable
                onPress={() => {
                  setShowOptions(false);
                  showDialog(
                    'Clear History',
                    'Are you sure you want to clear all chat messages? This cannot be undone.',
                    [
                      { text: 'Cancel', style: 'cancel' },
                      { text: 'Clear All', style: 'destructive', onPress: () => setMessages([]) },
                    ]
                  );
                }}
                className="flex-row items-center justify-between py-3.5 px-2 active:bg-surface2 dark:active:bg-d-surface2 rounded-md"
              >
                <Text className="text-sm font-semibold text-ink dark:text-d-ink">🗑️ Clear Chat History</Text>
              </Pressable>
              <Pressable
                onPress={() => {
                  setShowOptions(false);
                  showDialog(
                    'Block Contact',
                    `Are you sure you want to block ${contact.name}?`,
                    [
                      { text: 'Cancel', style: 'cancel' },
                      {
                        text: 'Block',
                        style: 'destructive',
                        onPress: () => {
                          showDialog('Blocked', `${contact.name} has been blocked.`, [
                            { text: 'OK', onPress: () => nav.goBack() }
                          ]);
                        },
                      },
                    ]
                  );
                }}
                className="flex-row items-center justify-between py-3.5 px-2 active:bg-surface2 dark:active:bg-d-surface2 rounded-md"
              >
                <Text className="text-sm font-semibold text-danger dark:text-d-danger">🚫 Block Contact</Text>
              </Pressable>
            </View>
          </Pressable>
        </Modal>
      )}

      {/* Attachment Menu */}
      {showAttachmentMenu && (
        <Modal transparent visible={showAttachmentMenu} animationType="slide">
          <Pressable onPress={() => setShowAttachmentMenu(false)} className="flex-1 bg-black/40 justify-end">
            <View className="bg-surface dark:bg-d-surface rounded-t-2xl p-4 gap-2" style={{ paddingBottom: insets.bottom + 16 }}>
              <View className="items-center pb-2 border-b border-hairline dark:border-d-hairline">
                <Text className="text-xs text-muted dark:text-d-muted font-bold uppercase tracking-wider">Share Media / Content</Text>
              </View>
              {[
                { label: '📷 Camera', onPress: () => showDialog('Camera', 'Simulated opening device camera...') },
                { label: '🖼️ Photos & Videos', onPress: () => showDialog('Gallery', 'Simulated opening photo gallery...') },
                { label: '📄 Document', onPress: () => showDialog('Documents', 'Simulated opening file picker...') },
                { label: '📍 Location', onPress: () => showDialog('Location', 'Simulated sharing current location...') },
                { label: '📄 Select Template', onPress: () => { setShowAttachmentMenu(false); setTimeout(() => setShowTemplatePicker(true), 300); } },
              ].map((item) => (
                <Pressable
                  key={item.label}
                  onPress={() => {
                    if (item.onPress) item.onPress();
                    if (!item.label.includes('Template')) setShowAttachmentMenu(false);
                  }}
                  className="py-3.5 px-2 active:bg-surface2 dark:active:bg-d-surface2 rounded-md"
                >
                  <Text className="text-sm font-semibold text-ink dark:text-d-ink">{item.label}</Text>
                </Pressable>
              ))}
            </View>
          </Pressable>
        </Modal>
      )}

      {/* Template Picker Modal */}
      {showTemplatePicker && (
        <Modal transparent visible={showTemplatePicker} animationType="slide">
          <View className="flex-1 bg-black/40 justify-end">
            <View className="bg-surface dark:bg-d-surface rounded-t-2xl max-h-[75%]" style={{ paddingBottom: insets.bottom + 16 }}>
              <View className="flex-row items-center justify-between px-[18px] py-4 border-b border-hairline dark:border-d-hairline">
                <Text className="text-base font-bold text-ink dark:text-d-ink">Insert Template</Text>
                <Pressable onPress={() => setShowTemplatePicker(false)} className="p-1">
                  <Text className="text-accent dark:text-d-accent font-semibold text-sm">Cancel</Text>
                </Pressable>
              </View>
              <FlatList
                data={TEMPLATES}
                keyExtractor={(item) => item.name}
                contentContainerStyle={{ padding: 18, gap: 10 }}
                renderItem={({ item }) => {
                  const isApproved = item.status === 'Approved';
                  return (
                    <Pressable
                      onPress={() => {
                        if (!isApproved) {
                          showDialog('Template Not Approved', 'Only Approved templates can be sent.');
                          return;
                        }
                        setDraft(item.preview);
                        setShowTemplatePicker(false);
                      }}
                      className="p-3.5 rounded-lg border border-hairline dark:border-d-hairline bg-surface2 dark:bg-d-surface2 active:bg-surface3"
                    >
                      <View className="flex-row justify-between items-center mb-1.5">
                        <Text className="font-mono text-[13px] font-bold text-ink dark:text-d-ink">{item.name}</Text>
                        <Text className={`text-[11px] font-semibold ${isApproved ? 'text-ok dark:text-d-ok' : 'text-warn dark:text-d-warn'}`}>{item.status}</Text>
                      </View>
                      <Text numberOfLines={2} className="text-xs text-muted dark:text-d-muted leading-4">{item.preview}</Text>
                    </Pressable>
                  );
                }}
              />
            </View>
          </View>
        </Modal>
      )}

      {/* Reusable Custom Dialog */}
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

function TypingDots() {
  const { tokens } = useTokens();
  const a = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    const loop = Animated.loop(
      Animated.timing(a, { toValue: 1, duration: 1200, useNativeDriver: true }),
    );
    loop.start();
    return () => loop.stop();
  }, [a]);

  const dot = (offset: number) => {
    const ty = a.interpolate({
      inputRange: [0, 0.4 - offset * 0.15, 0.6 - offset * 0.15, 1],
      outputRange: [0, -3, 0, 0],
    });
    return (
      <Animated.View
        key={offset}
        className="w-1.5 h-1.5 rounded-full bg-muted dark:bg-d-muted opacity-50"
        style={{
          transform: [{ translateY: ty }],
        }}
      />
    );
  };

  return (
    <View className="self-start bg-bubble-in dark:bg-d-bubble-in py-2.5 px-3.5 rounded-[14px] flex-row gap-1">
      {dot(0)}{dot(1)}{dot(2)}
    </View>
  );
}


