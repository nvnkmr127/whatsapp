import React, { useEffect, useRef, useState } from 'react';
import {
  View, Text, ScrollView, KeyboardAvoidingView, Platform, Animated, Pressable, Keyboard, BackHandler,
  Modal, FlatList, ActivityIndicator, Image, Vibration
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation, useRoute, RouteProp } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { ChevronLeft, Phone, MoreHorizontal, BellOff } from 'lucide-react-native';
import * as ImagePicker from 'expo-image-picker';
import * as DocumentPicker from 'expo-document-picker';
import * as ImageManipulator from 'expo-image-manipulator';

import { useTokens } from '@/theme';
import type { ChatMessage, RootStackParamList, Template } from '@/types';
import { Avatar } from '@/components/Avatar';
import { Bubble } from '@/components/Bubble';
import { IconButton } from '@/components/Button';
import { Composer } from '@/components/PhoneBubbleBar';
import { CustomDialog } from '@/components/Dialog';
import { api } from '@/services/api';
import {
  cacheMessages, cacheMeta,
  loadCachedMessages, loadCachedMeta,
} from '@/services/chatCache';

export default function ChatScreen({ navigation, route }: any) {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  const nav = navigation;
  const contact = route.params.contact;

  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [draft, setDraft] = useState('');
  const [typing, setTyping] = useState(false);
  const [loading, setLoading] = useState(true);      // first paint spinner
  const [isRefreshing, setIsRefreshing] = useState(false); // silent bg refresh indicator
  const [serverDown, setServerDown] = useState(false);     // 503 / network offline

  // Live Conversation Detail States
  const [isWithin24Hours, setIsWithin24Hours] = useState(true);
  const [isAiEnabled, setIsAiEnabled] = useState(true);
  const [sessionExpiresAt, setSessionExpiresAt] = useState<string | null>(null);
  const [dbContactId, setDbContactId] = useState<number>(contact.id);

  // New States for Actions
  const [isCalling, setIsCalling] = useState(false);
  const [callTime, setCallTime] = useState(0);
  const [selectedMedia, setSelectedMedia] = useState<{
    uri: string;
    type: 'image' | 'video' | 'document' | 'audio';
    name: string;
    mimeType?: string;
    size?: number;
  } | null>(null);
  const [showOptions, setShowOptions] = useState(false);
  const [showAttachmentMenu, setShowAttachmentMenu] = useState(false);
  const [showTemplatePicker, setShowTemplatePicker] = useState(false);
  const [templates, setTemplates] = useState<Template[]>([]);
  const [loadingTemplates, setLoadingTemplates] = useState(false);
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
  // Track previous message count to avoid scrolling on polls with no new messages
  const prevMsgCountRef = useRef<number>(0);

  // Fetch Conversation metadata and Messages — parallel requests for speed
  const fetchConversationDetails = async (isBackground = false) => {
    if (isBackground) setIsRefreshing(true);
    try {
      // Fire both requests at the same time instead of waiting one-by-one
      const [details, msgsData] = await Promise.all([
        api.get(`/v1/mobile/conversations/${contact.id}`),
        api.get(`/v1/mobile/conversations/${contact.id}/messages`),
      ]);

      const newIsWithin24 = details.is_within_24_hours;
      const newIsAi       = details.is_ai_enabled;
      const newSession    = details.session_expires_at;
      const newDbId       = details.contact?.id ?? dbContactId;

      setIsWithin24Hours(newIsWithin24);
      setIsAiEnabled(newIsAi);
      setSessionExpiresAt(newSession);
      setDbContactId(newDbId);
      setServerDown(false); // server is back

      const rawMsgs = msgsData.data || [];
      // Sort chronological (oldest first)
      const sorted = [...rawMsgs].reverse();

      const mapped: ChatMessage[] = [];
      let lastDateStr = '';

      sorted.forEach((m: any) => {
        const msgDate = new Date(m.created_at);
        const dateStr = msgDate.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });

        if (dateStr !== lastDateStr) {
          mapped.push({ kind: 'date', text: dateStr });
          lastDateStr = dateStr;
        }

        const msgTime = msgDate.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit', hour12: false });
        mapped.push({
          kind: m.direction === 'inbound' ? 'in' : 'out',
          text: m.content || (m.type === 'image' ? '📷 Image' : m.type === 'video' ? '🎥 Video' : '📄 Document'),
          time: msgTime,
          status: m.status === 'read' ? 'read' : m.status === 'delivered' ? 'delivered' : 'sent',
        });
      });

      // Only update messages state if content actually changed (avoids re-render on every poll)
      setMessages((prev) => {
        const prevLast = prev[prev.length - 1];
        const newLast  = mapped[mapped.length - 1];
        const countChanged = prev.length !== mapped.length;
        const lastChanged  = prevLast?.text !== newLast?.text || prevLast?.time !== newLast?.time;
        if (!countChanged && !lastChanged) return prev; // nothing changed — skip re-render

        // Haptic feedback (Vibration) on new inbound message in foreground
        if (newLast && newLast.kind === 'in') {
          try {
            Vibration.vibrate(500);
          } catch (e) {
            // Ignore in environments without haptics
          }
        }

        return mapped;
      });

      // ── Persist to local cache so next open is instant ──
      await Promise.all([
        cacheMessages(contact.id, mapped),
        cacheMeta(contact.id, {
          isWithin24Hours: newIsWithin24,
          isAiEnabled: newIsAi,
          sessionExpiresAt: newSession,
          dbContactId: newDbId,
        }),
      ]);

      return true; // success
    } catch (err: any) {
      // Mark server as down for 503 or network errors so the banner shows
      const status = err?.status || 0;
      if (status === 503 || status === 502 || status === 0 || err?.message === 'Network request failed') {
        setServerDown(true);
      }
      console.warn('Failed to refresh messages from API:', err?.message || err);
      return false; // failure
    } finally {
      setLoading(false);
      setIsRefreshing(false);
    }
  };

  // Compress an image URI to max 1200px wide and 70% quality (~< 300KB)
  const compressImage = async (uri: string): Promise<{ uri: string; mimeType: string; name: string }> => {
    const result = await ImageManipulator.manipulateAsync(
      uri,
      [{ resize: { width: 1200 } }],
      { compress: 0.7, format: ImageManipulator.SaveFormat.JPEG }
    );
    const name = result.uri.split('/').pop() || `img_${Date.now()}.jpg`;
    return { uri: result.uri, mimeType: 'image/jpeg', name: name.includes('.') ? name : `${name}.jpg` };
  };

  // Smart polling with exponential backoff when server is down
  useEffect(() => {
    let pollTimer: ReturnType<typeof setTimeout>;
    let isMounted = true;
    let failCount = 0;

    const POLL_INTERVAL = 8000;   // 8s when healthy
    const MAX_BACKOFF   = 120000; // max 2 min backoff when server is down

    const poll = async () => {
      if (!isMounted) return;
      const ok = await fetchConversationDetails(true);
      if (!isMounted) return;

      if (ok) {
        failCount = 0;
        pollTimer = setTimeout(poll, POLL_INTERVAL);
      } else {
        // Exponential backoff: 8s, 16s, 32s, 64s, 120s, 120s...
        failCount++;
        const delay = Math.min(POLL_INTERVAL * Math.pow(2, failCount - 1), MAX_BACKOFF);
        console.log(`[Poll] Server error, retrying in ${delay / 1000}s (attempt ${failCount})`);
        pollTimer = setTimeout(poll, delay);
      }
    };

    // ── Step 1: Load cache immediately (no spinner if cache exists) ──
    (async () => {
      const [cachedMsgs, cachedMeta] = await Promise.all([
        loadCachedMessages(contact.id),
        loadCachedMeta(contact.id),
      ]);

      if (cachedMsgs && cachedMsgs.length > 0) {
        setMessages(cachedMsgs);
        setLoading(false);
        if (cachedMeta) {
          setIsWithin24Hours(cachedMeta.isWithin24Hours);
          setIsAiEnabled(cachedMeta.isAiEnabled);
          setSessionExpiresAt(cachedMeta.sessionExpiresAt);
          setDbContactId(cachedMeta.dbContactId);
        }
        // ── Step 2: Silently refresh, then start backoff poll ──
        const ok = await fetchConversationDetails(true);
        if (isMounted) {
          failCount = ok ? 0 : 1;
          const delay = ok ? POLL_INTERVAL : Math.min(POLL_INTERVAL * 2, MAX_BACKOFF);
          pollTimer = setTimeout(poll, delay);
        }
      } else {
        // No cache — show spinner, fetch, then start poll
        await fetchConversationDetails(false);
        if (isMounted) pollTimer = setTimeout(poll, POLL_INTERVAL);
      }
    })();

    return () => {
      isMounted = false;
      clearTimeout(pollTimer);
    };
  }, [contact.id]);

  useEffect(() => {
    const currentCount = messages.length;
    const prevCount    = prevMsgCountRef.current;

    // Only auto-scroll to bottom when NEW messages arrive (count increased)
    // Not on every poll that returns the same messages
    if (currentCount > prevCount) {
      const t = setTimeout(() => scroller.current?.scrollToEnd({ animated: currentCount - prevCount <= 2 }), 80);
      prevMsgCountRef.current = currentCount;
      return () => clearTimeout(t);
    }
    prevMsgCountRef.current = currentCount;
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

  const loadTemplatesList = async () => {
    setLoadingTemplates(true);
    try {
      const response = await api.get('/v1/mobile/templates');
      const rawData = response.data || [];
      const mapped: Template[] = rawData.map((t: any) => {
        const bodyPreview = t.components?.find((c: any) => c.type === 'BODY')?.text || t.content || '';
        return {
          name: t.name,
          cat: t.category === 'MARKETING' ? 'Marketing' : t.category === 'UTILITY' ? 'Utility' : 'Authentication',
          lang: t.language || 'en_US',
          status: t.status === 'APPROVED' ? 'Approved' : t.status === 'PENDING' ? 'Pending' : 'Rejected',
          uses: String(t.total_sent || 0),
          preview: bodyPreview,
        };
      });
      setTemplates(mapped);
    } catch (err: any) {
      console.warn('Could not load templates', err);
    } finally {
      setLoadingTemplates(false);
    }
  };

  const openTemplateSelector = () => {
    setShowAttachmentMenu(false);
    loadTemplatesList();
    setShowTemplatePicker(true);
  };

  const pickImage = async (useCamera: boolean) => {
    setShowAttachmentMenu(false);
    let result;
    if (useCamera) {
      const permissionResult = await ImagePicker.requestCameraPermissionsAsync();
      if (!permissionResult.granted) {
        showDialog('Permission Required', 'Camera permission is needed to take photos.');
        return;
      }
      result = await ImagePicker.launchCameraAsync({
        mediaTypes: ['images', 'videos'],
        allowsEditing: false,
        quality: 0.8,
      });
    } else {
      result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ['images', 'videos'],
        allowsEditing: false,
        quality: 0.8,
      });
    }

    if (!result.canceled && result.assets && result.assets.length > 0) {
      const asset = result.assets[0];
      let fileName = asset.fileName || asset.uri.split('/').pop() || 'media';
      let mimeType = asset.mimeType;
      
      // Guess mime and add extension if missing
      if (!mimeType) {
        if (asset.type === 'video') mimeType = 'video/mp4';
        else mimeType = 'image/jpeg';
      }
      if (!fileName.includes('.')) {
        fileName += mimeType === 'video/mp4' ? '.mp4' : '.jpg';
      }

      setSelectedMedia({
        uri: asset.uri,
        type: asset.type === 'video' ? 'video' : 'image',
        name: fileName,
        mimeType: mimeType,
        size: asset.fileSize,
      });
    }
  };

  const pickDocument = async () => {
    setShowAttachmentMenu(false);
    const result = await DocumentPicker.getDocumentAsync({
      copyToCacheDirectory: true,
    });
    if (!result.canceled && result.assets && result.assets.length > 0) {
      const asset = result.assets[0];
      setSelectedMedia({
        uri: asset.uri,
        type: 'document',
        name: asset.name,
        mimeType: asset.mimeType,
        size: asset.size,
      });
    }
  };

  const send = async () => {
    const text = draft.trim();
    if (!text && !selectedMedia) return;

    // Check policy limits: 24h window constraint
    if (!isWithin24Hours) {
      showDialog(
        '24-Hour Policy Window Closed',
        'WhatsApp policy requires using a pre-approved template message because the 24-hour customer service window is closed.',
        [
          { text: 'Cancel', style: 'cancel' },
          { text: 'Choose Template', onPress: openTemplateSelector },
        ]
      );
      return;
    }

    // Prepend locally for immediate UX
    const displayStr = selectedMedia ? (text || `📄 ${selectedMedia.name}`) : text;
    const newMsg: ChatMessage = { kind: 'out', text: displayStr, time: 'now', status: 'sent' };
    setMessages((m) => [...m, newMsg]);
    setDraft('');
    const mediaToUpload = selectedMedia;
    setSelectedMedia(null);

    try {
      let mediaUrl = null;
      let msgType = 'text';

      if (mediaToUpload) {
        msgType = mediaToUpload.type;
        const formData = new FormData();

        // Compress images/photos before uploading to avoid 413 errors
        let uploadUri = mediaToUpload.uri;
        let finalMime = mediaToUpload.mimeType || 'application/octet-stream';
        let fileName = mediaToUpload.name;

        if (mediaToUpload.type === 'image') {
          const compressed = await compressImage(mediaToUpload.uri);
          uploadUri = compressed.uri;
          finalMime = compressed.mimeType;
          fileName = compressed.name;
        } else if (!finalMime || finalMime === 'application/octet-stream') {
          const ext = fileName.split('.').pop()?.toLowerCase();
          if (ext === 'mp4' || ext === 'mov') finalMime = 'video/mp4';
          else if (ext === 'pdf') finalMime = 'application/pdf';
          else if (ext === 'doc' || ext === 'docx') finalMime = 'application/msword';
        }

        formData.append('file', {
          uri: uploadUri,
          name: fileName,
          type: finalMime,
        } as any);

        try {
          const uploadRes = await api.post('/v1/mobile/media/upload', formData);
          mediaUrl = uploadRes.url;
          msgType = uploadRes.type; // From backend
        } catch (uploadErr) {
          console.warn('Media upload failed, using local URI as fallback', uploadErr);
          mediaUrl = uploadUri;
          msgType = mediaToUpload.type;
        }
      }

      const payload: any = {
        type: msgType,
      };
      if (msgType === 'text' || text) {
        payload.content = text;
      }
      if (mediaUrl) {
        payload.media_url = mediaUrl;
      }

      await api.post(`/v1/mobile/conversations/${contact.id}/messages`, payload);
      fetchConversationDetails();
    } catch (err: any) {
      showDialog('Failed to Send Message', err.message || 'Error occurred while sending.');
    }
  };

  const handleSendTemplate = async (templateName: string) => {
    setLoading(true);
    try {
      // Find template object from local array if needed, or query backend
      const response = await api.get('/v1/mobile/templates');
      const templatesList = response.data || [];
      const matched = templatesList.find((t: any) => t.name === templateName);

      if (!matched) {
        showDialog('Error', 'Template configuration not found on server.');
        return;
      }

      await api.post(`/v1/mobile/conversations/${contact.id}/send-template`, {
        template_id: matched.id,
        variables: [],
      });

      setShowTemplatePicker(false);
      showDialog('Template Sent', `WhatsApp template "${templateName}" dispatched.`);
      fetchConversationDetails();
    } catch (err: any) {
      showDialog('Failed to Send Template', err.message || 'Error occurred.');
    } finally {
      setLoading(false);
    }
  };

  const toggleAiAssistant = async () => {
    setShowOptions(false);
    const nextVal = !isAiEnabled;
    try {
      await api.post(`/v1/mobile/conversations/${contact.id}/toggle-ai`, {
        enabled: nextVal,
      });
      setIsAiEnabled(nextVal);
      showDialog(
        nextVal ? 'AI Chatbot Resumed' : 'AI Chatbot Paused',
        nextVal
          ? 'The AI assistant is now managing automatic customer replies.'
          : 'AI replies are paused. You can respond manually.'
      );
    } catch (err: any) {
      showDialog('Failed to Toggle AI', err.message || 'Error updating AI status.');
    }
  };

  const handleCloseConversation = async () => {
    setShowOptions(false);
    try {
      await api.post(`/v1/mobile/conversations/${contact.id}/close`);
      showDialog('Conversation Resolved', 'Closed this ticket and sent customer CSAT survey.', [
        { text: 'OK', onPress: () => nav.goBack() }
      ]);
    } catch (err: any) {
      showDialog('Error Closing', err.message || 'Could not close conversation.');
    }
  };

  const handleInitiateCall = async () => {
    setIsCalling(true);
    try {
      await api.post('/v1/calls/initiate', {
        phone_number: contact.phone || contact.name,
      });
    } catch (err: any) {
      console.warn('Call setup failed:', err);
      // Keep UI mock calling running for premium preview, but log eligibility issues
    }
  };

  return (
    <View className="flex-1 bg-bg dark:bg-d-bg">
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        className="flex-1"
      >
        {/* Header */}
        <View
          className="flex-row items-center gap-[10px] px-3 pb-3 bg-bg dark:bg-d-bg border-b border-hairline dark:border-d-hairline"
          style={{ paddingTop: insets.top + 4 }}
        >
          <IconButton icon={ChevronLeft} onPress={handleBack} />
          <Pressable
            onPress={() => nav.navigate('Contact', { conversationId: contact.id, contactId: dbContactId })}
            className="flex-1 flex-row items-center gap-[10px] min-w-0"
          >
            <Avatar name={contact.name} size={36} dot={contact.online ? tokens.ok : null} />
            <View className="flex-1 min-w-0">
              <View className="flex-row items-center gap-1.5">
                <Text numberOfLines={1} className="text-[15px] font-bold text-ink dark:text-d-ink">
                  {contact.name}
                </Text>
                {isMuted && <BellOff size={11} color={tokens.muted} strokeWidth={2} />}
              </View>
              <Text className={`text-[11.5px] ${typing ? 'text-accent dark:text-d-accent' : 'text-muted dark:text-d-muted'}`}>
                {typing ? 'typing…' : `${contact.tag} · ${isAiEnabled ? 'AI Active' : 'Human Only'} · ${isWithin24Hours ? '24h Open' : '24h Closed'}`}
              </Text>
            </View>
          </Pressable>
          <IconButton icon={Phone} onPress={handleInitiateCall} />
          <IconButton icon={MoreHorizontal} onPress={() => setShowOptions(true)} />
        </View>

        {/* Loading messages indicator */}
        {loading ? (
          <View className="flex-1 items-center justify-center">
            <ActivityIndicator size="small" color={tokens.accent} />
            <Text className="text-xs text-muted dark:text-d-muted mt-2">Loading chat history...</Text>
          </View>
        ) : (
          /* Messages List */
          <View className="flex-1">
            {/* Server unavailable banner (503/network error) */}
            {serverDown && (
              <View className="flex-row items-center justify-center gap-1.5 py-1.5 bg-warn/15">
                <Text className="text-[11px] text-warn dark:text-d-warn font-semibold">⚠️ Server unavailable · Showing cached messages</Text>
              </View>
            )}
            {/* Subtle "syncing" indicator — only while background-refreshing normally */}
            {isRefreshing && !serverDown && (
              <View className="flex-row items-center justify-center gap-1.5 py-1 bg-surface2 dark:bg-d-surface2">
                <ActivityIndicator size={10} color={tokens.muted} />
                <Text className="text-[10px] text-muted dark:text-d-muted font-medium">Syncing...</Text>
              </View>
            )}
            <ScrollView
              ref={scroller}
              contentContainerStyle={{ paddingHorizontal: 14, paddingTop: 10, paddingBottom: 12, gap: 6 }}
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
          </View>
        )}

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
                onPress={async () => {
                  setIsRecording(false);
                  const duration = recordingSeconds || 3;
                  const timeStr = `${Math.floor(duration / 60)}:${(duration % 60).toString().padStart(2, '0')}`;
                  
                  // Prepend locally
                  setMessages((m) => [...m, { kind: 'out', text: `🎙️ Voice message (${timeStr})`, time: 'now', status: 'sent' }]);
                  
                  try {
                    await api.post(`/v1/mobile/conversations/${contact.id}/messages`, {
                      type: 'document',
                      media_url: 'https://watxio-recordings.s3.amazonaws.com/voice-temp.aac',
                      content: `Voice message (${timeStr})`,
                    });
                    fetchConversationDetails();
                  } catch (err: any) {
                    console.warn('Voice upload api block:', err);
                  }
                }}
                className="px-3.5 py-1.5 rounded-full bg-accent dark:bg-d-accent active:opacity-85"
              >
                <Text className="text-accent-ink dark:text-d-accent-ink text-xs font-bold">Stop & Send</Text>
              </Pressable>
            </View>
          </View>
        ) : (
          <View>
            {selectedMedia && (
              <View className="bg-surface dark:bg-d-surface px-3 py-2 border-t border-hairline dark:border-d-hairline flex-row items-center justify-between">
                 <View className="flex-row items-center gap-3">
                   <View className="w-12 h-12 bg-surface2 dark:bg-d-surface2 rounded items-center justify-center overflow-hidden">
                     {selectedMedia.type === 'image' || selectedMedia.type === 'video' ? (
                       <Image source={{ uri: selectedMedia.uri }} style={{ width: '100%', height: '100%' }} />
                     ) : (
                       <Text className="text-2xl">📄</Text>
                     )}
                   </View>
                   <View>
                     <Text className="text-ink dark:text-d-ink font-semibold text-[13px] max-w-[200px]" numberOfLines={1}>{selectedMedia.name}</Text>
                     <Text className="text-muted dark:text-d-muted text-[11px] uppercase mt-0.5">{selectedMedia.type}</Text>
                   </View>
                 </View>
                 <Pressable onPress={() => setSelectedMedia(null)} className="p-2">
                   <Text className="text-danger dark:text-d-danger font-bold text-sm">Remove</Text>
                 </Pressable>
              </View>
            )}
            <Composer
              value={draft}
              onChange={setDraft}
              onSend={send}
              onAttach={() => setShowAttachmentMenu(true)}
              onVoice={() => {
                setIsRecording(true);
                setRecordingSeconds(0);
              }}
              hasMedia={!!selectedMedia}
            />
          </View>
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
                onPress={toggleAiAssistant}
                className="flex-row items-center justify-between py-3.5 px-2 active:bg-surface2 dark:active:bg-d-surface2 rounded-md"
              >
                <Text className="text-sm font-semibold text-ink dark:text-d-ink">
                  {isAiEnabled ? '🤖 Pause AI Assistant' : '🤖 Resume AI Assistant'}
                </Text>
              </Pressable>
              <Pressable
                onPress={handleCloseConversation}
                className="flex-row items-center justify-between py-3.5 px-2 active:bg-surface2 dark:active:bg-d-surface2 rounded-md"
              >
                <Text className="text-sm font-semibold text-ink dark:text-d-ink">✅ Resolve & Close Conversation</Text>
              </Pressable>
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
                { label: '📷 Camera', onPress: () => pickImage(true) },
                { label: '🖼️ Photos & Videos', onPress: () => pickImage(false) },
                { label: '📄 Document', onPress: () => pickDocument() },
                { label: '📄 Select Template', onPress: openTemplateSelector },
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

              {loadingTemplates ? (
                <View className="py-20 items-center justify-center">
                  <ActivityIndicator size="small" color={tokens.accent} />
                  <Text className="text-xs text-muted dark:text-d-muted mt-2">Loading templates...</Text>
                </View>
              ) : (
                <FlatList
                  data={templates}
                  keyExtractor={(item) => item.name}
                  contentContainerStyle={{ padding: 18, gap: 10 }}
                  ListEmptyComponent={
                    <View className="py-12 items-center justify-center">
                      <Text className="text-xs text-muted dark:text-d-muted">No approved templates found.</Text>
                    </View>
                  }
                  renderItem={({ item }) => {
                    const isApproved = item.status === 'Approved';
                    return (
                      <Pressable
                        onPress={() => {
                          if (!isApproved) {
                            showDialog('Template Not Approved', 'Only Approved templates can be sent.');
                            return;
                          }
                          handleSendTemplate(item.name);
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
              )}
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
