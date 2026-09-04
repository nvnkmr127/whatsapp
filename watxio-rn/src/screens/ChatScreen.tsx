import React, { useEffect, useRef, useState } from 'react';
import {
  View, Text, ScrollView, Platform, Animated, Pressable, Keyboard, BackHandler,
  Modal, FlatList, ActivityIndicator, Image, Vibration, TextInput, Linking, Share
} from 'react-native';
import { useReanimatedKeyboardAnimation } from 'react-native-keyboard-controller';
import Reanimated, { useAnimatedStyle } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation, useRoute, RouteProp } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { ChevronLeft, Phone, MoreHorizontal, BellOff, LayoutTemplate, Tag, Reply, Clock } from 'lucide-react-native';
import { Swipeable } from 'react-native-gesture-handler';
import * as ImagePicker from 'expo-image-picker';
import * as DocumentPicker from 'expo-document-picker';
import * as ImageManipulator from 'expo-image-manipulator';
import {
  getInfoAsync,
  readAsStringAsync,
  writeAsStringAsync,
  deleteAsync as fsDeleteAsync,
  EncodingType,
  cacheDirectory,
} from 'expo-file-system/legacy';
import { Audio } from 'expo-av';

import { useTokens } from '@/theme';
import type { ChatMessage, Conversation, RootStackParamList, Template } from '@/types';
import { Avatar } from '@/components/Avatar';
import { Bubble } from '@/components/Bubble';
import { IconButton } from '@/components/Button';
import { Composer } from '@/components/PhoneBubbleBar';
import { CustomDialog } from '@/components/Dialog';
import { OfflineBanner } from '@/components/OfflineBanner';
import { resolveMediaUrl } from '@/utils/media';
import { ChatSkeleton } from '@/components/ChatSkeleton';
import { MediaViewer } from '@/components/MediaViewer';
import { ReplyPreview } from '@/components/ReplyPreview';
import { VideoThumbnailPreview } from '@/components/VideoThumbnailPreview';
import { api } from '@/services/api';
import { CallWebRTC } from '@/services/webrtc';
import { useGlobalState } from '@/store';
import { useNetworkStatus } from '@/hooks/useNetworkStatus';
import {
  cacheMessages, cacheMeta,
  loadCachedMessages, loadCachedMeta,
} from '@/services/chatCache';
import { safeExtractText } from '@/utils/text';
import { safeGoBack } from '@/navigation/navigationRef';

export default function ChatScreen({ navigation, route }: any) {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  const nav = navigation;
  const [globalState] = useGlobalState();
  const conversation = route.params.conversation;
  const conversationId = conversation.id;
  const [contact, setContact] = useState(conversation);

  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [draft, setDraft] = useState('');
  const [nextCursor, setNextCursor] = useState<string | null>(null);
  const [loadingEarlier, setLoadingEarlier] = useState(false);
  const [typing, setTyping] = useState(false);
  const [replyingTo, setReplyingTo] = useState<ChatMessage | null>(null);
  const [isSending, setIsSending] = useState(false);
  const [loading, setLoading] = useState(true);      // first paint spinner
  const [isRefreshing, setIsRefreshing] = useState(false); // silent bg refresh indicator
  const [serverDown, setServerDown] = useState(false);     // 503 / network offline

  // Live Conversation Detail States
  const [isWithin24Hours, setIsWithin24Hours] = useState(true);
  const [isAiEnabled, setIsAiEnabled] = useState(true);
  const [sessionExpiresAt, setSessionExpiresAt] = useState<string | null>(null);
  const [dbContactId, setDbContactId] = useState<number>(conversation.contact_id || 0);

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
  const [showTagPicker, setShowTagPicker] = useState(false);
  const [availableTags, setAvailableTags] = useState<{ id: number, name: string, color: string }[]>([]);
  const [contactTags, setContactTags] = useState<{ id: number, name: string, color: string }[]>([]);
  const [loadingTags, setLoadingTags] = useState(false);
  const [newTagName, setNewTagName] = useState('');
  const [templates, setTemplates] = useState<Template[]>([]);
  const [loadingTemplates, setLoadingTemplates] = useState(false);
  const [isMuted, setIsMuted] = useState(false);
  const [isRecording, setIsRecording] = useState(false);
  const [isRecordingPaused, setIsRecordingPaused] = useState(false);
  const [recordingSeconds, setRecordingSeconds] = useState(0);
  const recordingRef = useRef<Audio.Recording | null>(null);
  const isStartingRecordingRef = useRef(false);

  // Network Status
  const isOnline = useNetworkStatus();

  // Media Viewer State
  const [mediaViewer, setMediaViewer] = useState<{ uri: string; type: 'image' | 'video' | 'audio' | 'document' } | null>(null);

  // Voice recording timer effect
  useEffect(() => {
    let interval: NodeJS.Timeout;
    if (isRecording && !isRecordingPaused) {
      interval = setInterval(() => {
        setRecordingSeconds((s) => s + 1);
      }, 1000);
    }
    return () => clearInterval(interval);
  }, [isRecording, isRecordingPaused]);

  // Voice recording cleanup on unmount
  useEffect(() => {
    return () => {
      if (recordingRef.current) {
        recordingRef.current.stopAndUnloadAsync().catch(() => {});
        recordingRef.current = null;
      }
    };
  }, []);

  // Save Contact States
  const [showSaveContact, setShowSaveContact] = useState(false);
  const [saveContactName, setSaveContactName] = useState('');
  const [saveContactEmail, setSaveContactEmail] = useState('');
  const [saveContactNotes, setSaveContactNotes] = useState('');
  const [savingContact, setSavingContact] = useState(false);

  // Conversation Lock States
  const [lockOwner, setLockOwner] = useState<{ id: number; name: string } | null>(null);
  const [hasLock, setHasLock] = useState(false);
  const hasLockRef = useRef(false);

  // WebSocket Connection State
  const [wsConnected, setWsConnected] = useState(false);

  // Tracks whether initial messages have loaded — vibration only fires after this
  const initialLoadDoneRef = useRef(false);

  // Message Actions Context Menu & Forward States
  const [showMsgActions, setShowMsgActions] = useState(false);
  const [selectedMsg, setSelectedMsg] = useState<ChatMessage | null>(null);
  const [showForwardPicker, setShowForwardPicker] = useState(false);
  const [forwardingMsg, setForwardingMsg] = useState<ChatMessage | null>(null);
  const [activeConversations, setActiveConversations] = useState<Conversation[]>([]);
  const [loadingConversations, setLoadingConversations] = useState(false);
  const [highlightedMsgId, setHighlightedMsgId] = useState<number | string | null>(null);

  const { height } = useReanimatedKeyboardAnimation();
  const animatedStyle = useAnimatedStyle(() => {
    return {
      paddingBottom: Math.max(insets.bottom, Math.abs(height.value))
    };
  }, [insets.bottom]);

  useEffect(() => {
    // Load templates in background silently so they are ready for message rendering
    loadTemplatesList(true);
  }, []);

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
  const rowRefs = useRef<Map<number, any>>(new Map());
  const messageLayouts = useRef<Map<number, number>>(new Map());
  // Track previous message count to avoid scrolling on polls with no new messages
  const prevMsgCountRef = useRef<number>(0);
  const isUserNavigatingReplyRef = useRef<boolean>(false);
  const hasInitialScrolledRef = useRef<boolean>(false);

  // Always-fresh ref to fetchConversationDetails so the polling effect never
  // captures a stale closure (e.g. stale isOnline value).
  const fetchConversationDetailsRef = useRef<typeof fetchConversationDetails>(null as any);

  const getCursorFromUrl = (url: string | null) => {
    if (!url) return null;
    const match = url.match(/[?&]cursor=([^&]+)/);
    return match ? match[1] : null;
  };

  // Fetch Conversation metadata and Messages — parallel requests for speed
  const fetchConversationDetails = async (isBackground = false) => {
    // When offline, skip API call and rely on already-loaded cache
    if (!isOnline) {
      if (isBackground) setIsRefreshing(false);
      setLoading(false);
      return false;
    }
    if (isBackground) setIsRefreshing(true);
    try {
      // Fire both requests at the same time instead of waiting one-by-one
      // Fire both requests at the same time, but catch errors on details so we can still load messages
      const [details, msgsData] = await Promise.all([
        api.get(`/v1/mobile/conversations/${conversationId}`, { 'X-Silent-Errors': 'true' }).catch(err => {
          return null;
        }),
        api.get(`/v1/mobile/conversations/${conversationId}/messages`, { 'X-Silent-Errors': 'true' }),
      ]);

      const newIsWithin24 = details ? details.is_within_24_hours : isWithin24Hours;
      const newIsAi = details ? details.is_ai_enabled : isAiEnabled;
      const newSession = details ? details.session_expires_at : sessionExpiresAt;
      const newDbId = (details && details.contact?.id) ? details.contact.id : dbContactId;

      setIsWithin24Hours(newIsWithin24);
      setIsAiEnabled(newIsAi);
      setSessionExpiresAt(newSession);
      setDbContactId(newDbId);
      
      if (details && details.contact?.name) {
        setContact((prev: any) => ({ ...prev, name: details.contact.name }));
      }
      
      setServerDown(false); // server is back

      const nextUrl = msgsData.next_page_url || null;
      const parsedCursor = getCursorFromUrl(nextUrl);
      setNextCursor((current) => {
        return isBackground && current ? current : parsedCursor;
      });

      const rawData = msgsData.data || [];
      const rawMsgs = Array.isArray(rawData) ? rawData : Object.values(rawData);
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
        const rawStatus = m.status || (m.read_at ? 'read' : (m.delivered_at ? 'delivered' : 'sent'));
        const apiStatus = (rawStatus || '').toLowerCase();

        const rawReplyContent = m.reply_to_content ?? m.reply_to_message?.content ?? m.metadata?.reply_to_content ?? m.metadata?.reply_to_message?.content ?? m.reply_to_text;
        const replyContent = safeExtractText(rawReplyContent);

        const replyId = m.reply_to_message_id ?? m.reply_to_id ?? m.metadata?.reply_to_message_id ?? null;
        const replyIsOutbound = m.reply_to_is_outbound ?? m.reply_to_message?.is_outbound ?? m.metadata?.reply_to_is_outbound ?? false;
        const replyMediaUrl = m.reply_to_message?.full_media_url ?? m.reply_to_message?.media_url ?? m.reply_to_media_url ?? m.metadata?.reply_to_media_url ?? null;
        const replyMediaType = m.reply_to_message?.media_type ?? m.reply_to_media_type ?? m.metadata?.reply_to_media_type ?? null;

        const rawMediaUrl = m.full_media_url || m.media_url || m.metadata?.media_url || m.metadata?.file_path || m.media_path || (m.type && m.type !== 'text' && m.type !== 'template' && m.content && (m.content.startsWith('http://') || m.content.startsWith('https://') || m.content.startsWith('/storage/') || m.content.startsWith('storage/')) ? m.content : null);
        const rawMediaType = m.media_type || m.metadata?.media_type || ((m.type && m.type !== 'text' && m.type !== 'template') ? m.type : (rawMediaUrl ? 'image' : null));

        const isOutbound = m.is_outbound !== undefined ? m.is_outbound : (m.direction === 'outbound' || m.direction !== 'inbound');

        mapped.push({
          id: m.id,
          kind: isOutbound ? 'out' : 'in',
          text: safeExtractText(m.content),
          time: msgTime,
          status: apiStatus === 'read' || apiStatus === 'seen' ? 'read' : apiStatus === 'delivered' ? 'delivered' : apiStatus === 'failed' ? 'failed' : (apiStatus === 'queued' || apiStatus === 'sending' || apiStatus === 'pending') ? 'queued' : 'sent',
          isStarred: !!m.is_starred,
          media_url: rawMediaUrl,
          media_type: rawMediaType,
          metadata: m.metadata || null,
          reply_to_content: replyContent || null,
          reply_to_id: replyId ? (isNaN(Number(replyId)) ? replyId : Number(replyId)) : null,
          reply_to_is_outbound: replyIsOutbound,
          reply_to_media_url: replyMediaUrl,
          reply_to_media_type: replyMediaType,
        });
      });

      // Only update messages state if content actually changed (avoids re-render on every poll)
      setMessages((prev) => {
        // Find the first message in mapped that has an ID (the oldest one in page 1)
        const firstMappedWithId = mapped.find(m => m.kind !== 'date' && m.id);

        let merged: ChatMessage[] = [];
        if (firstMappedWithId && prev.length > 0) {
          // Find where page 1 starts in the previous messages state
          const stopIndex = prev.findIndex(m => m.kind !== 'date' && m.id && m.id >= firstMappedWithId.id!);

          let prefix: ChatMessage[] = [];
          if (stopIndex !== -1) {
            prefix = prev.slice(0, stopIndex);
          } else {
            prefix = prev;
          }

          // Combine older messages prefix and newly fetched page 1 messages
          merged = [...prefix, ...mapped];
        } else {
          merged = mapped;
        }

        // Clean up adjacent duplicate date headers
        const cleaned: ChatMessage[] = [];
        let lastHeaderSeen = '';
        merged.forEach((msg) => {
          if (msg.kind === 'date') {
            if (msg.text !== lastHeaderSeen) {
              cleaned.push(msg);
              lastHeaderSeen = msg.text;
            }
          } else {
            cleaned.push(msg);
          }
        });

        const prevLast = prev[prev.length - 1];
        const newLast = cleaned[cleaned.length - 1];
        const countChanged = prev.length !== cleaned.length;
        const lastChanged = prevLast?.text !== newLast?.text || prevLast?.time !== newLast?.time || prevLast?.status !== newLast?.status;

        if (!countChanged && !lastChanged) {
          // Check if any message status/content inside changed (e.g. read receipts)
          const anyMessageChanged = cleaned.some((m, idx) => {
            const p = prev[idx];
            return !p || p.id !== m.id || p.status !== m.status || p.text !== m.text;
          });
          if (!anyMessageChanged) return prev;
        }

        // Haptic feedback (Vibration) on new inbound message in foreground
        // Only vibrate after initial load so existing unread messages don't trigger it
        if (initialLoadDoneRef.current && newLast && newLast.kind === 'in' && (!prevLast || prevLast.id !== newLast.id)) {
          try {
            Vibration.vibrate(500);
          } catch (e) {
            // Ignore in environments without haptics
          }
        }

        return cleaned;
      });

      // ── Persist to local cache so next open is instant ──
      await Promise.all([
        cacheMessages(conversationId, mapped),
        cacheMeta(conversationId, {
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
      initialLoadDoneRef.current = true;
    }
  };

  // Keep ref in sync on every render so polling/WS callbacks always call the latest version
  fetchConversationDetailsRef.current = fetchConversationDetails;

  const handleMediaPress = async (m: ChatMessage) => {
    if (m.media_url) {
      setMediaViewer({
        uri: resolveMediaUrl(m.media_url) || m.media_url,
        type: (m.media_type as any) || 'image',
      });
      return;
    }
    try {
      setIsRefreshing(true);
      await fetchConversationDetails(true);
    } catch (err) {
      console.warn('Failed to fetch media on demand:', err);
    } finally {
      setIsRefreshing(false);
    }
  };

  const loadEarlierMessages = async () => {
    if (!nextCursor || loadingEarlier) return;
    setLoadingEarlier(true);
    try {
      const response = await api.get(`/v1/mobile/conversations/${conversationId}/messages?cursor=${nextCursor}`, { 'X-Silent-Errors': 'true' });
      const rawData = response.data || [];
      const rawMsgs = Array.isArray(rawData) ? rawData : Object.values(rawData);
      const nextUrl = response.next_page_url || null;
      setNextCursor(getCursorFromUrl(nextUrl));

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
        const rawStatus = m.status || (m.read_at ? 'read' : (m.delivered_at ? 'delivered' : 'sent'));
        const apiStatus = (rawStatus || '').toLowerCase();

        const rawReplyContent = m.reply_to_content ?? m.reply_to_message?.content ?? m.metadata?.reply_to_content ?? m.metadata?.reply_to_message?.content ?? m.reply_to_text;
        const replyContent = safeExtractText(rawReplyContent);

        const replyId = m.reply_to_message_id ?? m.reply_to_id ?? m.metadata?.reply_to_message_id ?? null;
        const replyIsOutbound = m.reply_to_is_outbound ?? m.reply_to_message?.is_outbound ?? m.metadata?.reply_to_is_outbound ?? false;
        const replyMediaUrl = m.reply_to_message?.full_media_url ?? m.reply_to_message?.media_url ?? m.reply_to_media_url ?? m.metadata?.reply_to_media_url ?? null;
        const replyMediaType = m.reply_to_message?.media_type ?? m.reply_to_media_type ?? m.metadata?.reply_to_media_type ?? null;

        const rawMediaUrl = m.full_media_url || m.media_url || m.metadata?.media_url || m.metadata?.file_path || m.media_path || (m.type && m.type !== 'text' && m.type !== 'template' && m.content && (m.content.startsWith('http://') || m.content.startsWith('https://') || m.content.startsWith('/storage/') || m.content.startsWith('storage/')) ? m.content : null);
        const rawMediaType = m.media_type || m.metadata?.media_type || ((m.type && m.type !== 'text' && m.type !== 'template') ? m.type : (rawMediaUrl ? 'image' : null));

        const isOutbound = m.is_outbound !== undefined ? m.is_outbound : (m.direction === 'outbound' || m.direction !== 'inbound');

        mapped.push({
          id: m.id,
          kind: isOutbound ? 'out' : 'in',
          text: safeExtractText(m.content),
          time: msgTime,
          status: apiStatus === 'read' || apiStatus === 'seen' ? 'read' : apiStatus === 'delivered' ? 'delivered' : apiStatus === 'failed' ? 'failed' : (apiStatus === 'queued' || apiStatus === 'sending' || apiStatus === 'pending') ? 'queued' : 'sent',
          isStarred: !!m.is_starred,
          media_url: rawMediaUrl,
          media_type: rawMediaType,
          metadata: m.metadata || null,
          reply_to_content: replyContent || null,
          reply_to_id: replyId ? (isNaN(Number(replyId)) ? replyId : Number(replyId)) : null,
          reply_to_is_outbound: replyIsOutbound,
          reply_to_media_url: replyMediaUrl,
          reply_to_media_type: replyMediaType,
        });
      });

      setMessages((prev) => {
        const combined = [...mapped, ...prev];
        const cleaned: ChatMessage[] = [];
        let lastHeaderSeen = '';

        combined.forEach((msg) => {
          if (msg.kind === 'date') {
            if (msg.text !== lastHeaderSeen) {
              cleaned.push(msg);
              lastHeaderSeen = msg.text;
            }
          } else {
            if (!cleaned.some(x => x.id === msg.id)) {
              cleaned.push(msg);
            }
          }
        });

        return cleaned;
      });
    } catch (err: any) {
      console.warn('Failed to load earlier messages:', err);
    } finally {
      setTimeout(() => {
        setLoadingEarlier(false);
      }, 500);
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

  /**
   * Chunked file upload — mirrors what Livewire does on the web.
   *
   * Splits the file into CHUNK_SIZE pieces (each well under nginx's 1 MB
   * default body limit) and POSTs them one-by-one to the chunk endpoint.
   * The server stores each piece and assembles the full file when the last
   * chunk arrives, returning the final URL in that response.
   */
  const CHUNK_SIZE = 700 * 1024; // 700 KB — safely under 1 MB nginx limit

  const uploadInChunks = async (
    uri: string,
    fileName: string,
    mimeType: string,
  ): Promise<{ url: string; type: string }> => {
    // Get the real file size from the filesystem
    const info = await getInfoAsync(uri);
    const fileSize: number = info.exists ? (info as any).size ?? 0 : 0;
    const totalChunks = Math.max(1, Math.ceil(fileSize / CHUNK_SIZE));
    const uploadId = `${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;

    if (__DEV__) {
      console.log('[CHUNKED_UPLOAD] Starting', {
        fileName,
        mimeType,
        fileSizeMB: (fileSize / (1024 * 1024)).toFixed(1),
        totalChunks,
        uploadId,
      });
    }

    let lastResponse: any = null;

    for (let i = 0; i < totalChunks; i++) {
      const start = i * CHUNK_SIZE;
      const length = Math.min(CHUNK_SIZE, fileSize - start);

      // Read this slice as base64
      const chunkBase64 = await readAsStringAsync(uri, {
        encoding: EncodingType.Base64,
        position: start,
        length,
      });

      // Write the raw bytes to a temp file so FormData sends it as binary
      const tempUri = `${cacheDirectory}chunk_${uploadId}_${i}.bin`;
      await writeAsStringAsync(tempUri, chunkBase64, {
        encoding: EncodingType.Base64,
      });

      const formData = new FormData();
      formData.append('upload_id', uploadId);
      formData.append('chunk_index', String(i));
      formData.append('total_chunks', String(totalChunks));
      formData.append('filename', fileName);
      formData.append('mime', mimeType);
      formData.append('chunk', { uri: tempUri, name: `chunk_${i}.bin`, type: 'application/octet-stream' } as any);

      if (__DEV__) {
        console.log(`[CHUNKED_UPLOAD] Uploading chunk ${i + 1}/${totalChunks} (${(length / 1024).toFixed(0)} KB)`);
      }

      lastResponse = await api.post('/v1/mobile/media/upload/chunk', formData, undefined, 60000);

      // Clean up temp chunk file immediately
      fsDeleteAsync(tempUri, { idempotent: true }).catch(() => {});

      // If the server returns a URL it means this was the final chunk and
      // the file has been fully assembled
      if (lastResponse?.url) {
        if (__DEV__) console.log('[CHUNKED_UPLOAD] Done!', lastResponse);
        return { url: lastResponse.url, type: lastResponse.type ?? 'video' };
      }
    }

    throw new Error('Chunked upload completed but server did not return a URL.');
  };

  // Smart polling with exponential backoff when server is down
  useEffect(() => {
    let pollTimer: ReturnType<typeof setTimeout>;
    let isMounted = true;
    let failCount = 0;

    const POLL_INTERVAL = wsConnected ? 40000 : 8000;   // 40s when WS is alive, 8s fallback when not
    const MAX_BACKOFF = 120000; // max 2 min backoff when server is down

    const poll = async () => {
      if (!isMounted) return;
      const ok = await fetchConversationDetailsRef.current(true);
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

    initialLoadDoneRef.current = false;

    // ── Step 1: Load cache immediately (no spinner if cache exists) ──
    (async () => {
      const [cachedMsgs, cachedMeta] = await Promise.all([
        loadCachedMessages(conversationId),
        loadCachedMeta(conversationId),
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
        const ok = await fetchConversationDetailsRef.current(true);
        if (isMounted) {
          failCount = ok ? 0 : 1;
          const delay = ok ? POLL_INTERVAL : Math.min(POLL_INTERVAL * 2, MAX_BACKOFF);
          pollTimer = setTimeout(poll, delay);
        }
      } else {
        // No cache — show spinner, fetch, then start poll
        await fetchConversationDetailsRef.current(false);
        if (isMounted) pollTimer = setTimeout(poll, POLL_INTERVAL);
      }
    })();

    return () => {
      isMounted = false;
      clearTimeout(pollTimer);
    };
  }, [conversationId, wsConnected]);

  // Sync state to ref to avoid triggering unnecessary effect dependency updates
  useEffect(() => {
    hasLockRef.current = hasLock;
  }, [hasLock]);

  // Presence Lifecycle: heartbeat every 30s, leave on unmount
  useEffect(() => {
    let presenceTimer: ReturnType<typeof setInterval>;
    let active = true;

    const sendPresenceHeartbeat = async () => {
      try {
        await api.post('/v1/mobile/presence/heartbeat', {
          conversation_id: conversationId,
        }, { 'X-Silent-Errors': 'true' });
      } catch (err) {
        console.warn('[Presence] Heartbeat failed:', err);
      }
    };

    const sendPresenceLeave = async () => {
      if (!api.getToken()) return;
      try {
        await api.post('/v1/mobile/presence/leave', {
          conversation_id: conversationId,
        }, { 'X-Silent-Errors': 'true' });
      } catch (err) {
        console.warn('[Presence] Leave failed:', err);
      }
    };

    // Send immediately on mount
    sendPresenceHeartbeat();

    // Heartbeat every 30 seconds
    presenceTimer = setInterval(() => {
      if (active) {
        sendPresenceHeartbeat();
      }
    }, 30000);

    return () => {
      active = false;
      clearInterval(presenceTimer);
      sendPresenceLeave();
    };
  }, [conversationId]);

  // Lock Lifecycle: lock on mount, heartbeat every 20s, unlock on unmount
  useEffect(() => {
    let heartbeatTimer: ReturnType<typeof setInterval>;
    let active = true;

    const performLock = async () => {
      try {
        const response = await api.post(`/v1/mobile/conversations/${conversationId}/lock`, undefined, { 'X-Silent-Errors': 'true' });
        if (!active) return;

        if (response.success) {
          setHasLock(true);
          setLockOwner(null);
        } else if (response.code === 'ERR_CONVERSATION_LOCKED') {
          setHasLock(false);
          const ownerData = response.data || {};
          setLockOwner({
            id: ownerData.owner || 0,
            name: ownerData.owner_name || 'Another Agent',
          });
          showDialog(
            'Conversation Locked',
            `This conversation is currently locked by ${ownerData.owner_name || 'another agent'}.`,
            [
              { text: 'View Only', style: 'cancel' },
              {
                text: 'Take Over',
                style: 'destructive',
                onPress: async () => {
                  try {
                    setLoading(true);
                    const takeoverRes = await api.post(`/v1/mobile/conversations/${conversationId}/takeover`, undefined, { 'X-Silent-Errors': 'true' });
                    if (takeoverRes.success) {
                      setHasLock(true);
                      setLockOwner(null);
                      showDialog('Lock Acquired', 'You have successfully taken over this conversation.');
                    } else {
                      showDialog('Takeover Failed', takeoverRes.message || 'Could not take over the conversation.');
                    }
                  } catch (takeoverErr: any) {
                    showDialog('Takeover Failed', takeoverErr.message || 'Error occurred.');
                  } finally {
                    setLoading(false);
                  }
                }
              }
            ]
          );
        } else {
          if (active) setHasLock(true);
        }
      } catch (err) {
        console.warn('[Lock] Failed to lock (timeout/server busy):', err);
        if (active) setHasLock(true);
      }
    };

    const performHeartbeat = async () => {
      try {
        const response = await api.post(`/v1/mobile/conversations/${conversationId}/heartbeat`, undefined, { 'X-Silent-Errors': 'true' });
        if (!active) return;

        if (!response.success) {
          setHasLock(false);
        }
      } catch (err: any) {
        console.warn('[Lock] Heartbeat failed:', err);
        if (err?.status === 401 || err?.data?.code === 'ERR_LOCK_LOST') {
          setHasLock(false);
        }
      }
    };

    const performUnlock = async () => {
      if (!api.getToken()) return;
      try {
        await api.post(`/v1/mobile/conversations/${conversationId}/unlock`, undefined, { 'X-Silent-Errors': 'true' });
      } catch (err) {
        console.warn('[Lock] Unlock failed:', err);
      }
    };

    performLock();

    heartbeatTimer = setInterval(() => {
      if (active && hasLockRef.current) {
        performHeartbeat();
      }
    }, 20000);

    return () => {
      active = false;
      clearInterval(heartbeatTimer);
      if (hasLockRef.current) {
        performUnlock();
      }
    };
  }, [conversationId]);

  // Real-time WebSocket Messaging listener (Reverb / Pusher)
  useEffect(() => {
    const socketConfig = globalState.websocket;
    if (!socketConfig || !socketConfig.key) return;

    let ws: WebSocket | null = null;
    let active = true;
    let reconnectTimer: ReturnType<typeof setTimeout> | null = null;

    // Backend now sends the public-facing Reverb config directly.
    // Convert http/https scheme to ws/wss for the WebSocket URL.
    const wsScheme = socketConfig.scheme === 'https' ? 'wss' : 'ws';
    let wsHost = socketConfig.host;
    
    // If running on an emulator and the config says localhost, use the actual API base url hostname (e.g. 10.111.185.147)
    if (wsHost === '127.0.0.1' || wsHost === 'localhost') {
      try {
        if (globalState.baseUrl) {
          const apiHost = globalState.baseUrl.replace(/^https?:\/\//, '').split(':')[0].split('/')[0];
          wsHost = apiHost || wsHost;
        }
      } catch (e) {
        // ignore
      }
    }

    const portSuffix = socketConfig.port && socketConfig.port !== 443 && socketConfig.port !== 80
      ? `:${socketConfig.port}`
      : '';
    const wsUrl = `${wsScheme}://${wsHost}${portSuffix}/app/${socketConfig.key}?protocol=7&client=js&version=8.4.0-rc2&flash=false`;

    const channelName = `presence-conversation.${conversationId}`;

    const connect = () => {
      if (!active) return;

      console.log(`[WS] Connecting to ${wsUrl}`);
      ws = new WebSocket(wsUrl);

      ws.onopen = () => {
        console.log('[WS] Connection opened');
      };

      ws.onmessage = async (event) => {
        try {
          const payload = JSON.parse(event.data);

          if (payload.event === 'pusher:connection_established') {
            const data = JSON.parse(payload.data);
            const socketId = data.socket_id;
            console.log(`[WS] Connection established with socket_id: ${socketId}`);

            // Authenticate subscription with backend
            try {
              const authRes = await api.post('/v1/mobile/broadcasting/auth', {
                socket_id: socketId,
                channel_name: channelName,
              });

              if (active && ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({
                  event: 'pusher:subscribe',
                  data: {
                    channel: channelName,
                    auth: authRes.auth,
                    channel_data: authRes.channel_data,
                  }
                }));
                console.log(`[WS] Subscribing to channel ${channelName}`);
                setWsConnected(true);
              }
            } catch (authErr) {
              console.warn('[WS] Subscription auth failed:', authErr);
              setWsConnected(false);
            }
          } else if (
            payload.event === 'MessageReceived' ||
            payload.event === '.MessageReceived' ||
            payload.event === 'MessageSent' ||
            payload.event === '.MessageSent' ||
            payload.event === 'MessageStatusUpdated' ||
            payload.event === '.MessageStatusUpdated' ||
            payload.event === 'App\\Events\\MessageStatusUpdated' ||
            payload.event?.endsWith('MessageStatusUpdated') ||
            payload.event?.endsWith('MessageReceived') ||
            payload.event?.endsWith('MessageSent')
          ) {
            console.log(`[WS] Invalidation event received: ${payload.event}`);
            fetchConversationDetailsRef.current(true).catch((e) =>
              console.warn('[WS] fetchConversationDetails failed:', e)
            );
          }
        } catch (err) {
          console.warn('[WS] Error processing message:', err);
        }
      };

      ws.onerror = (err) => {
        console.warn('[WS] Error:', err);
        setWsConnected(false);
      };

      ws.onclose = (event) => {
        console.log(`[WS] Connection closed: code=${event.code}, reason=${event.reason}`);
        setWsConnected(false);
        // Reconnect after 5 seconds if still active
        if (active) {
          reconnectTimer = setTimeout(connect, 5000);
        }
      };
    };

    connect();

    return () => {
      active = false;
      if (reconnectTimer) clearTimeout(reconnectTimer);
      if (ws) ws.close();
    };
  }, [conversationId, globalState.websocket, globalState.baseUrl]);

  const prevLastMsgRef = useRef<ChatMessage | null>(null);
  const prevTypingRef = useRef(false);

  useEffect(() => {
    const lastMsg = messages[messages.length - 1];
    const prevLastMsg = prevLastMsgRef.current;

    const isInitialLoad = !prevLastMsg && lastMsg;
    const isNewMessageAdded = lastMsg && prevLastMsg && (lastMsg.id !== prevLastMsg.id || lastMsg.text !== prevLastMsg.text || lastMsg.time !== prevLastMsg.time);
    const typingStarted = typing && !prevTypingRef.current;

    if ((isInitialLoad || isNewMessageAdded || typingStarted) && !isUserNavigatingReplyRef.current) {
      const t = setTimeout(() => scroller.current?.scrollToEnd({ animated: false }), 80);
      prevLastMsgRef.current = lastMsg;
      prevTypingRef.current = typing;
      return () => clearTimeout(t);
    }

    prevLastMsgRef.current = lastMsg;
    prevTypingRef.current = typing;
  }, [messages, typing]);

  useEffect(() => {
    const unsubscribe = nav.addListener('beforeRemove', () => {
      Keyboard.dismiss();
    });
    return unsubscribe;
  }, [nav]);

  const handleMessageLongPress = (msg: ChatMessage) => {
    if (!msg.id) return;
    setSelectedMsg(msg);
    setShowMsgActions(true);
  };

  const openForwardModal = async (msg: ChatMessage) => {
    setForwardingMsg(msg);
    setShowForwardPicker(true);
    setLoadingConversations(true);
    try {
      const response = await api.get('/v1/mobile/conversations');
      const rawData = response.data || [];
      const mapped: Conversation[] = rawData.map((c: any) => {
        return {
          id: c.id,
          contact_id: c.contact_id,
          name: c.name || c.phone || 'Unknown Contact',
          phone: c.phone,
          last: c.last_message ? c.last_message.content : 'No messages yet',
          time: c.last_message ? c.last_message.pretty_time : '',
          unread: c.unread_count || 0,
          status: c.status === 'closed' ? 'delivered' : 'read',
          tag: c.tags && c.tags[0] ? c.tags[0].name : 'Sales',
          online: c.is_within_24_hours || false,
          pinned: false,
          reply: c.last_message?.is_outbound ? 'me' : undefined,
          bot: c.is_ai_enabled || false,
        };
      });
      // Filter out the current conversation so users can't forward to the same chat
      const filtered = mapped.filter((c) => c.id !== conversationId);
      setActiveConversations(filtered);
    } catch (err: any) {
      console.warn('Could not load conversations for forwarding', err);
      showDialog('Error', 'Failed to load conversations for forwarding.');
    } finally {
      setLoadingConversations(false);
    }
  };

  const handleForwardMessage = async (toConversationId: number, toConversationName: string) => {
    if (!forwardingMsg || !forwardingMsg.id) return;

    setShowForwardPicker(false);
    setLoading(true);
    try {
      const res = await api.post(`/v1/mobile/messages/${forwardingMsg.id}/forward`, {
        to_conversation_id: toConversationId,
      });
      if (res.success) {
        showDialog(
          'Message Forwarded',
          `Message successfully forwarded to ${toConversationName}.`
        );
      }
    } catch (err: any) {
      showDialog('Forwarding Failed', err.message || 'Could not forward message.');
    } finally {
      setLoading(false);
      setForwardingMsg(null);
    }
  };

  const isGoingBackRef = useRef(false);

  const handleBack = () => {
    if (isGoingBackRef.current) return;
    isGoingBackRef.current = true;
    Keyboard.dismiss();
    safeGoBack(nav, 'Main');
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

  const fetchTags = async () => {
    setLoadingTags(true);
    try {
      const headers = { 'X-Silent-Errors': 'true' };
      const [allTagsRes, contactRes] = await Promise.all([
        api.get('/v1/mobile/contacts/tags', headers),
        api.get(`/v1/mobile/contacts/${dbContactId}`, headers)
      ]);
      setAvailableTags(allTagsRes || []);
      setContactTags(contactRes.contact?.tags || []);
    } catch (err: any) {
      console.warn('Could not load tags', err);
    } finally {
      setLoadingTags(false);
    }
  };

  const loadTemplatesList = async (isSilent = false) => {
    setLoadingTemplates(true);
    try {
      const headers = isSilent ? { 'X-Silent-Errors': 'true' } : undefined;
      const response = await api.get('/v1/mobile/templates', headers);
      const rawData = response.data || [];
      const mapped: Template[] = rawData.map((t: any) => {
        const bodyPreview = t.components?.find((c: any) => c.type === 'BODY')?.text || t.content || '';
        return {
          id: t.id,
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
      if (!isSilent) {
        console.warn('Could not load templates', err);
      }
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
        // iOS only: ask the OS to transcode to low quality before returning
        // the video, so files are small enough to upload without hitting nginx
        // 413. On Android, quality is not controllable here — the nginx
        // client_max_body_size must be raised on the server.
        videoQuality: ImagePicker.UIImagePickerControllerQualityType.Low,
      });
    } else {
      result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ['images', 'videos'],
        allowsEditing: false,
        quality: 0.8,
        // iOS only: ask the OS to transcode to low quality when picking from
        // gallery. On Android, no quality control — server nginx limit applies.
        videoQuality: ImagePicker.UIImagePickerControllerQualityType.Low,
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

      if (__DEV__ && asset.type === 'video') {
        const sizeMB = asset.fileSize ? (asset.fileSize / (1024 * 1024)).toFixed(1) : 'unknown';
        console.log('[VIDEO_PICK] Picked video:', { fileName, mimeType, sizeMB: `${sizeMB} MB`, uri: asset.uri });
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
    if (isSending) return;
    const text = draft.trim();
    if (!text && !selectedMedia) return;

    // Check lock ownership before sending
    if (!hasLock && lockOwner) {
      showDialog(
        'Conversation Locked',
        `This conversation is locked by ${lockOwner.name}. You must take it over before you can send messages.`,
        [
          { text: 'Cancel', style: 'cancel' },
          {
            text: 'Take Over',
            style: 'destructive',
            onPress: async () => {
              try {
                setLoading(true);
                const takeoverRes = await api.post(`/v1/mobile/conversations/${conversationId}/takeover`);
                if (takeoverRes.success) {
                  setHasLock(true);
                  setLockOwner(null);
                  showDialog('Lock Acquired', 'You have successfully taken over this conversation. You can now send messages.');
                } else {
                  showDialog('Takeover Failed', takeoverRes.message || 'Could not take over the conversation.');
                }
              } catch (takeoverErr: any) {
                showDialog('Takeover Failed', takeoverErr.message || 'Error occurred.');
              } finally {
                setLoading(false);
              }
            }
          }
        ]
      );
      return;
    }

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

    // Reset reply navigation state so screen auto scrolls to sent message
    isUserNavigatingReplyRef.current = false;

    // Prepend locally for immediate UX with a unique temp ID
    const tempId = `temp_${Date.now()}`;
    const displayStr = selectedMedia ? (text || (selectedMedia.type === 'document' ? `📄 ${selectedMedia.name}` : '')) : text;
    const nowTime = new Date().toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit', hour12: false });
    const newMsg: ChatMessage = { 
      id: tempId as any,
      kind: 'out', 
      text: displayStr, 
      time: nowTime, 
      status: 'queued',
      media_url: selectedMedia ? selectedMedia.uri : null,
      media_type: selectedMedia ? selectedMedia.type : null,
      reply_to_content: replyingTo ? safeExtractText(replyingTo.text) || 'Media Message' : null,
      reply_to_id: replyingTo ? replyingTo.id : null,
      reply_to_is_outbound: replyingTo ? replyingTo.kind === 'out' : false,
      reply_to_media_url: replyingTo ? replyingTo.media_url : null,
      reply_to_media_type: replyingTo ? replyingTo.media_type : null,
    };
    setMessages((m) => [...m, newMsg]);
    setDraft('');
    const mediaToUpload = selectedMedia;
    setSelectedMedia(null);

    setIsSending(true);
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
        } else if (mediaToUpload.type === 'video') {
          if (!finalMime || !finalMime.startsWith('video/')) {
            finalMime = 'video/mp4';
          }
          if (!fileName.match(/\.(mp4|mov|avi|mkv|3gp|webm)$/i)) {
            fileName = `${fileName.replace(/\.[^/.]+$/, '')}.mp4`;
          }

          // Hard size guard: 60 MB max to avoid 413 from nginx.
          // Phone videos picked with videoQuality: 0.3 are typically <20 MB.
          // If somehow still too large, show a clear error instead of a cryptic upload failure.
          const fileSizeBytes = mediaToUpload.size ?? 0;
          const MAX_VIDEO_BYTES = 60 * 1024 * 1024; // 60 MB
          if (__DEV__) {
            console.log('[VIDEO_SEND] Video file size:', `${(fileSizeBytes / (1024 * 1024)).toFixed(1)} MB`);
          }
          if (fileSizeBytes > MAX_VIDEO_BYTES) {
            showDialog(
              'Video Too Large',
              `This video is ${(fileSizeBytes / (1024 * 1024)).toFixed(0)} MB. Please select a shorter clip (videos must be under 60 MB). WhatsApp limits video to 16 MB.`,
              [{ text: 'OK', style: 'cancel' }]
            );
            setIsSending(false);
            return;
          }
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
          if (mediaToUpload.type === 'video' || mediaToUpload.type === 'audio') {
            // ── Chunked upload (same as Livewire on web) ─────────────────────
            // Splits the file into 700 KB pieces so every request stays under
            // nginx's 1 MB default body limit, then the server reassembles them.
            if (__DEV__) {
              console.log('[CHUNKED_UPLOAD] Using chunked upload for', mediaToUpload.type, fileName);
            }
            const chunkResult = await uploadInChunks(uploadUri, fileName, finalMime);
            mediaUrl = chunkResult.url;
            msgType  = chunkResult.type;
          } else {
            // ── Single-shot upload for images and documents ───────────────────
            if (__DEV__) {
              console.log('[VIDEO_SEND] Starting single upload...', { type: mediaToUpload.type, name: fileName, mime: finalMime });
            }
            const uploadRes = await api.post('/v1/mobile/media/upload', formData, undefined, 120000);
            if (__DEV__) console.log('[VIDEO_SEND] Upload successful:', uploadRes);
            mediaUrl = uploadRes.url;
            if (uploadRes.type && uploadRes.type !== 'document') {
              msgType = uploadRes.type;
            }
          }
        } catch (uploadErr: any) {
          console.warn('[VIDEO_SEND] Media upload failed:', uploadErr);
          showDialog('Media Upload Failed', uploadErr?.message || 'Could not upload media. Please check your connection and try again.');
          setIsSending(false);
          return;
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
      if (replyingTo && replyingTo.id) {
        payload.reply_to_message_id = replyingTo.id;
      }

      setReplyingTo(null);

      if (__DEV__) {
        console.log('[VIDEO_SEND] Sending message payload:', payload);
      }
      // Use 120-second timeout for message creation which triggers synchronous Meta upload
      const sendRes = await api.post(`/v1/mobile/conversations/${conversationId}/messages`, payload, undefined, 120000);
      if (__DEV__) {
        console.log('[VIDEO_SEND] Message sent successfully:', sendRes);
      }

      // Immediately flip from 'queued' clock -> 'sent' single tick in local state
      if (sendRes && sendRes.id) {
        const rawResStatus = (sendRes.status || 'sent').toLowerCase();
        const resStatus = rawResStatus === 'read' || rawResStatus === 'seen'
          ? 'read'
          : rawResStatus === 'delivered'
            ? 'delivered'
            : rawResStatus === 'failed'
              ? 'failed'
              : 'sent';

        setMessages((prev) =>
          prev.map((msg) =>
            msg === newMsg || (msg.id && String(msg.id) === String(tempId))
              ? {
                  ...msg,
                  id: sendRes.id,
                  status: resStatus,
                  time: sendRes.created_at
                    ? new Date(sendRes.created_at).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit', hour12: false })
                    : msg.time,
                }
              : msg
          )
        );
      }

      fetchConversationDetailsRef.current(true).catch((e) =>
        console.warn('[Send] fetchConversationDetails failed:', e)
      );
    } catch (err: any) {
      setMessages((prev) =>
        prev.map((msg) =>
          msg === newMsg || (msg.id && String(msg.id) === String(tempId))
            ? { ...msg, status: 'failed' }
            : msg
        )
      );
      showDialog('Failed to Send Message', err.message || 'Error occurred while sending.');
    } finally {
      setIsSending(false);
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

      await api.post(`/v1/mobile/conversations/${conversationId}/send-template`, {
        template_id: matched.id,
        variables: [],
      });

      setShowTemplatePicker(false);
      fetchConversationDetailsRef.current(true).catch((e) =>
        console.warn('[Template] fetchConversationDetails failed:', e)
      );
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
      await api.post(`/v1/mobile/conversations/${conversationId}/toggle-ai`, {
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
      await api.post(`/v1/mobile/conversations/${conversationId}/close`);
      showDialog('Conversation Resolved', 'Closed this ticket and sent customer CSAT survey.', [
        { text: 'OK', onPress: () => safeGoBack(nav, 'Main') }
      ]);
    } catch (err: any) {
      showDialog('Error Closing', err.message || 'Could not close conversation.');
    }
  };

  const handleInitiateCall = async () => {
    // Guard: a phone number (digits only) is required. contact.name is a display
    // name and would fail the server-side regex validation — never use it as
    // a fallback for phone_number.
    const phoneNumber = contact.phone;
    if (!phoneNumber) {
      showDialog('Call Failed', 'No phone number is available for this contact.');
      return;
    }
    setIsCalling(true);
    try {
      // Meta's Calls API requires a WebRTC SDP offer ("session" parameter).
      // Generate one here before hitting the server so we already have the
      // local PeerConnection ready when the remote answer arrives.
      const sdp = await CallWebRTC.session().initOutbound();

      await api.post('/v1/calls/initiate', {
        phone_number: phoneNumber,
        sdp,
      });
      // Call initiated — the global CallOverlayManager picks it up via polling.
      // Dismiss the local ringing indicator after a short delay.
      setTimeout(() => setIsCalling(false), 3000);
    } catch (err: any) {
      CallWebRTC.end(); // clean up the WebRTC session on failure
      console.warn('Call setup failed:', err);
      setIsCalling(false);

      const isPermissionErr =
        err?.code === 'NO_APPROVED_CALL_PERMISSION' ||
        err?.data?.code === 'NO_APPROVED_CALL_PERMISSION' ||
        err?.message?.includes('No approved call permission') ||
        err?.data?.message?.includes('No approved call permission') ||
        err?.data?.errors?.error?.code === 138006;

      if (isPermissionErr) {
        showDialog(
          'Call Permission Required',
          'WhatsApp requires permission from the recipient before a business can call them. Would you like to send a Call Permission Request template now?',
          [
            { text: 'Cancel', style: 'cancel' },
            {
              text: 'Send Request',
              onPress: async () => {
                try {
                  setLoading(true);
                  const res = await api.post('/v1/whatsapp/calls/request-permission', {
                    contact_id: dbContactId,
                  });
                  if (res.success) {
                    showDialog('Request Sent', 'Call permission request template has been sent.');
                  } else {
                    showDialog('Request Failed', res.error || 'Failed to send call permission request.');
                  }
                } catch (requestErr: any) {
                  showDialog('Request Failed', requestErr.message || 'Failed to send call permission request.');
                } finally {
                  setLoading(false);
                }
              }
            }
          ]
        );
      } else {
        showDialog('Call Failed', err.message || 'Could not initiate the call.');
      }
    }
  };

  const handleSaveContact = async () => {
    if (!saveContactName.trim()) return;
    setSavingContact(true);
    try {
      if (dbContactId) {
        await api.put(`/v1/mobile/contacts/${dbContactId}`, {
          name: saveContactName.trim(),
          email: saveContactEmail.trim() || undefined,
          notes: saveContactNotes.trim() || undefined,
        });
      } else {
        const res = await api.post('/v1/mobile/contacts', {
          phone_number: contact.phone,
          name: saveContactName.trim(),
          email: saveContactEmail.trim() || undefined,
          notes: saveContactNotes.trim() || undefined,
          conversation_id: conversationId,
        });
        if (res?.data?.id) setDbContactId(res.data.id);
      }
      setContact((prev: any) => ({ ...prev, name: saveContactName.trim() }));
      setShowSaveContact(false);
      showDialog('Saved', 'Contact saved successfully.');
    } catch (e: any) {
      showDialog('Error', e?.message || 'Could not save contact.');
    } finally {
      setSavingContact(false);
    }
  };

  return (
    <View className="flex-1 bg-bg dark:bg-d-bg">
      <Reanimated.View
        className="flex-1"
        style={animatedStyle}
      >
        {/* Header */}
        <View
          className="flex-row items-center gap-[10px] px-3 pb-3 bg-bg dark:bg-d-bg border-b border-hairline dark:border-d-hairline"
          style={{ paddingTop: insets.top + 4 }}
        >
          <IconButton icon={ChevronLeft} onPress={handleBack} />
          <Pressable
            onPress={() => nav.navigate('Contact', { conversationId, contactId: dbContactId, contactName: contact.name, contactPhone: contact.phone })}
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
          <IconButton icon={Tag} onPress={() => { setShowTagPicker(true); fetchTags(); }} />
          <IconButton icon={LayoutTemplate} onPress={openTemplateSelector} />
          <IconButton icon={MoreHorizontal} onPress={() => setShowOptions(true)} />
        </View>

        {/* Offline banner */}
        <OfflineBanner />

        {/* Loading messages indicator */}
        {loading ? (
          <ChatSkeleton />
        ) : (
          /* Messages List */
          <View className="flex-1">
            {/* Server unavailable banner (503/network error) */}
            {serverDown && (
              <View className="flex-row items-center justify-center gap-1.5 py-1.5 bg-warn/15">
                <Text className="text-[11px] text-warn dark:text-d-warn font-semibold">⚠️ Server unavailable · Showing cached messages</Text>
              </View>
            )}
            <ScrollView
              ref={scroller}
              keyboardDismissMode="interactive"
              contentContainerStyle={{ paddingHorizontal: 14, paddingTop: 10, paddingBottom: 12, gap: 6 }}
              maintainVisibleContentPosition={Platform.OS === 'ios' ? { minIndexForVisible: 1 } : undefined}
              onContentSizeChange={() => {
                if (!hasInitialScrolledRef.current && !loadingEarlier) {
                  hasInitialScrolledRef.current = true;
                  scroller.current?.scrollToEnd({ animated: false });
                }
              }}
            >
              {nextCursor && (
                <Pressable
                  onPress={loadEarlierMessages}
                  disabled={loadingEarlier}
                  className="self-center py-2 px-4 rounded-full bg-surface2 dark:bg-d-surface2 mb-2 active:opacity-75"
                >
                  {loadingEarlier ? (
                    <ActivityIndicator size="small" color={tokens.accent} />
                  ) : (
                    <Text className="text-xs font-semibold text-accent dark:text-d-accent">Load earlier messages</Text>
                  )}
                </Pressable>
              )}
              {messages.map((m, i) => {
                // Use stable keys: message ID for bubbles, text+position for date separators
                const stableKey = m.kind === 'date' ? `date-${m.text}-${i}` : `msg-${m.id ?? i}`;
                if (m.kind === 'date') {
                  return (
                    <View
                      key={stableKey}
                      className="self-center px-2.5 py-[3px] rounded-full bg-surface2 dark:bg-d-surface2 my-1"
                    >
                      <Text className="text-muted dark:text-d-muted text-[11px] font-semibold">{m.text}</Text>
                    </View>
                  );
                }

                let displayText = m.text || '';
                if (displayText.startsWith('Official Template: ') && templates.length > 0) {
                  const tName = displayText.replace('Official Template: ', '').trim();
                  const matched = templates.find((t) => t.name === tName);
                  if (matched && matched.preview) {
                    displayText = matched.preview;
                    if (m.metadata?.variables && Array.isArray(m.metadata.variables)) {
                      m.metadata.variables.forEach((val: string, index: number) => {
                        displayText = displayText.replace(new RegExp(`\\{\\{${index + 1}\\}\\}`, 'g'), val);
                      });
                    }
                  }
                }

                const isHighlighted = highlightedMsgId !== null && String(highlightedMsgId) === String(m.id);

                return (
                  <View 
                    key={stableKey} 
                    onLayout={(e) => {
                      if (m.id) messageLayouts.current.set(m.id, e.nativeEvent.layout.y);
                    }}
                    className={isHighlighted ? 'bg-accent/20 dark:bg-accent/30 rounded-2xl p-1 border border-accent' : ''}
                  >
                    <Swipeable
                      ref={(ref) => {
                        if (ref && m.id) {
                          rowRefs.current.set(m.id, ref);
                        }
                      }}
                      renderLeftActions={() => (
                      <View className="justify-center pl-4 pr-2">
                        <View className="w-8 h-8 rounded-full bg-surface2 dark:bg-d-surface2 items-center justify-center">
                          <Reply size={18} color={tokens.ink} />
                        </View>
                      </View>
                    )}
                    onSwipeableWillOpen={() => {
                      try { Vibration.vibrate(25); } catch (e) {}
                      setReplyingTo(m);
                      if (m.id) {
                        rowRefs.current.get(m.id)?.close();
                      }
                    }}
                    friction={2}
                    leftThreshold={40}
                  >
                    <Pressable
                      onLongPress={() => handleMessageLongPress(m)}
                      className="active:opacity-85"
                    >
                      <Bubble
                        kind={m.kind}
                        time={m.time}
                        status={m.status}
                        variant="tail"
                        isStarred={m.isStarred}
                        mediaUrl={m.media_url}
                        mediaType={m.media_type}
                        metadata={m.metadata}
                        contactName={contact.name}
                        replyTo={(() => {
                          if (m.reply_to_id) {
                            const found = messages.find(msg => String(msg.id) === String(m.reply_to_id));
                            if (found) return found;
                          }
                          if (m.reply_to_content || m.reply_to_media_url || m.reply_to_id) {
                            return {
                              kind: m.reply_to_is_outbound ? 'out' : 'in',
                              text: safeExtractText(m.reply_to_content),
                              media_url: m.reply_to_media_url,
                              media_type: m.reply_to_media_type,
                            } as ChatMessage;
                          }
                          return null;
                        })()}
                        onReplyPress={() => {
                          if (m.reply_to_id) {
                            const targetMsg = messages.find(msg => String(msg.id) === String(m.reply_to_id));
                            const targetId = targetMsg?.id || m.reply_to_id;
                            if (targetId) {
                              isUserNavigatingReplyRef.current = true;
                              setHighlightedMsgId(targetId);
                              setTimeout(() => setHighlightedMsgId(null), 2500);
                              const yOffset = messageLayouts.current.get(Number(targetId)) ?? messageLayouts.current.get(targetId as any);
                              if (yOffset !== undefined) {
                                scroller.current?.scrollTo({ y: Math.max(0, yOffset - 60), animated: true });
                              }
                            }
                          }
                        }}
                        onMediaPress={() => handleMediaPress(m)}
                      >
                        {displayText}
                      </Bubble>
                    </Pressable>
                  </Swipeable>
                </View>
                );
              })}
              {typing ? <TypingDots /> : null}
            </ScrollView>
          </View>
        )}

        <View>
          {selectedMedia && !isRecording && (
            <View className="bg-surface dark:bg-d-surface px-3 py-2 border-t border-hairline dark:border-d-hairline flex-row items-center justify-between">
              <View className="flex-row items-center gap-3">
                <View className="w-12 h-12 bg-surface2 dark:bg-d-surface2 rounded items-center justify-center overflow-hidden">
                  {selectedMedia.type === 'video' ? (
                    <VideoThumbnailPreview
                      videoUri={selectedMedia.uri}
                      width={48}
                      height={48}
                      borderRadius={4}
                      showPlayButton={true}
                      playButtonSize="small"
                      showDurationBadge={false}
                    />
                  ) : selectedMedia.type === 'image' ? (
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

          {isWithin24Hours ? (
            <Composer
              value={draft}
              onChange={setDraft}
              onSend={send}
              onAttach={() => setShowAttachmentMenu(true)}
              hasMedia={!!selectedMedia}
              replyingTo={replyingTo}
              contactName={contact.name}
              onCancelReply={() => setReplyingTo(null)}
              isRecording={isRecording}
              isPaused={isRecordingPaused}
              recordingSeconds={recordingSeconds}
              onStartRecording={async () => {
                if (isStartingRecordingRef.current) return;
                isStartingRecordingRef.current = true;
                try {
                  // Clean up any stale recording reference to prevent Expo lockups
                  if (recordingRef.current) {
                    try {
                      await recordingRef.current.stopAndUnloadAsync();
                    } catch (_) {}
                    recordingRef.current = null;
                  }

                  const permission = await Audio.requestPermissionsAsync();
                  if (permission.status === 'granted') {
                    await Audio.setAudioModeAsync({
                      allowsRecordingIOS: true,
                      playsInSilentModeIOS: true,
                    });
                    const { recording } = await Audio.Recording.createAsync(
                      Audio.RecordingOptionsPresets.HIGH_QUALITY
                    );
                    recordingRef.current = recording;
                    setIsRecording(true);
                    setIsRecordingPaused(false);
                    setRecordingSeconds(0);
                  } else {
                    showDialog('Permission Denied', 'Microphone permission is required to send voice notes.');
                  }
                } catch (err) {
                  console.error('Failed to start recording', err);
                  showDialog('Error', 'Could not start recording audio.');
                } finally {
                  isStartingRecordingRef.current = false;
                }
              }}
              onTogglePause={async () => {
                if (!recordingRef.current) return;
                try {
                  if (isRecordingPaused) {
                    await recordingRef.current.startAsync();
                    setIsRecordingPaused(false);
                  } else {
                    await recordingRef.current.pauseAsync();
                    setIsRecordingPaused(true);
                  }
                } catch (err) {
                  console.warn('Failed to toggle pause recording:', err);
                }
              }}
              onCancelRecording={async () => {
                setIsRecording(false);
                setIsRecordingPaused(false);
                if (recordingRef.current) {
                  try {
                    await recordingRef.current.stopAndUnloadAsync();
                  } catch (e) { }
                  recordingRef.current = null;
                }
                try {
                  await Audio.setAudioModeAsync({
                    allowsRecordingIOS: false,
                    playsInSilentModeIOS: true,
                  });
                } catch (e) {}
              }}
              onSendRecording={async () => {
                setIsRecording(false);
                setIsRecordingPaused(false);
                let uri: string | null = null;
                const rec = recordingRef.current;
                recordingRef.current = null;

                if (rec) {
                  try {
                    await rec.stopAndUnloadAsync();
                    uri = rec.getURI();
                  } catch (e) {
                    console.warn('Failed to stop recording:', e);
                  }
                }

                try {
                  await Audio.setAudioModeAsync({
                    allowsRecordingIOS: false,
                    playsInSilentModeIOS: true,
                  });
                } catch (e) {}

                const duration = recordingSeconds || 1;
                const timeStr = `${Math.floor(duration / 60)}:${(duration % 60).toString().padStart(2, '0')}`;

                if (!uri) {
                  showDialog('Error', 'Could not save audio recording.');
                  return;
                }

                // Prepend locally
                setMessages((m) => [...m, { kind: 'out', text: `🎙️ Voice message (${timeStr})`, time: 'now', status: 'queued', media_url: uri, media_type: 'audio' }]);

                try {
                  const formData = new FormData();
                  formData.append('file', {
                    uri,
                    name: 'voice.m4a',
                    type: 'audio/mp4',
                  } as any);
                  formData.append('is_voice_note', 'true');

                  const uploadRes = await api.post('/v1/mobile/media/upload', formData, undefined, 120000);

                  const sendPayload = {
                    type: 'audio',
                    media_url: uploadRes.path || uploadRes.url,
                    content: `Voice message (${timeStr})`,
                    is_voice_note: true,
                  };
                  const sendRes = await api.post(`/v1/mobile/conversations/${conversationId}/messages`, sendPayload, undefined, 120000);

                  // SendMessageJob runs inline, so the response carries the real
                  // outcome. The endpoint returns 200 even when WhatsApp rejected the
                  // media, so surface a real failure instead of a silent success.
                  if (sendRes?.message?.status === 'failed') {
                    console.warn('[Voice Debug] Send message failed status on backend:', sendRes.message);
                    showDialog('Voice Note Not Delivered', sendRes.message.error_message || 'WhatsApp rejected the voice note.');
                  }
                  fetchConversationDetailsRef.current(true).catch((e) =>
                    console.warn('[Voice] fetchConversationDetails failed:', e)
                  );
                } catch (err: any) {
                  console.error('[Voice Debug] Caught error in api block:', err);
                  console.warn('Voice upload api block:', err);
                  showDialog('Failed to Send Voice Note', err.message || 'Upload failed.');
                }
              }}
            />
          ) : (
            <View className="bg-[#FDF9ED] dark:bg-[#2A2416] px-4 py-3 items-center justify-between flex-row border-t border-hairline dark:border-d-hairline">
              <View className="flex-row items-center flex-1 pr-2">
                <View className="w-[38px] h-[38px] rounded-full bg-[#FDF0D5] dark:bg-[#40331A] items-center justify-center mr-3">
                  <Clock size={18} color="#D97706" />
                </View>
                <View className="flex-1">
                  <Text className="text-[#0f172a] dark:text-[#f8fafc] font-bold text-[13px] mb-[2px]">TIME LIMIT REACHED</Text>
                  <Text className="text-[#475569] dark:text-[#94a3b8] text-[12px]">Wait is over. You can only send templates right now.</Text>
                </View>
              </View>
              <Pressable
                onPress={openTemplateSelector}
                className="bg-accent dark:bg-d-accent px-4 py-[10px] rounded active:opacity-85 flex-row items-center justify-center"
              >
                <Text className="text-accent-ink dark:text-d-accent-ink font-bold text-xs">+ START CONVERSATION</Text>
              </Pressable>
            </View>
          )}
        </View>
      </Reanimated.View>

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
                  setShowOptions(false);
                  // Pre-fill name if it looks like a name (not just digits)
                  const nameToFill = contact.name && !/^\d+$/.test(contact.name) ? contact.name : '';
                  setSaveContactName(nameToFill);
                  setSaveContactEmail('');
                  setSaveContactNotes('');
                  setShowSaveContact(true);
                }}
                className="flex-row items-center justify-between py-3.5 px-2 active:bg-surface2 dark:active:bg-d-surface2 rounded-md"
              >
                <Text className="text-sm font-semibold text-ink dark:text-d-ink">
                  {dbContactId ? '✏️ Edit Contact' : '💾 Save Contact'}
                </Text>
              </Pressable>
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

      {/* Tag Picker Modal */}
      {showTagPicker && (
        <Modal transparent visible={showTagPicker} animationType="slide">
          <View className="flex-1 justify-end bg-black/40">
            <View className="bg-surface dark:bg-d-surface rounded-t-2xl max-h-[80%]" style={{ paddingBottom: insets.bottom || 20 }}>
              <View className="flex-row items-center justify-between p-4 border-b border-hairline dark:border-d-hairline">
                <Text className="text-base font-bold text-ink dark:text-d-ink">Manage Tags</Text>
                <Pressable onPress={() => setShowTagPicker(false)} className="p-2">
                  <Text className="text-accent dark:text-d-accent font-semibold">Close</Text>
                </Pressable>
              </View>

              {loadingTags ? (
                <View className="py-20 items-center justify-center">
                  <ActivityIndicator size="small" color={tokens.accent} />
                  <Text className="text-xs text-muted dark:text-d-muted mt-2">Loading tags...</Text>
                </View>
              ) : (
                <>
                  {/* Create New Tag */}
                  <View className="p-4 border-b border-hairline dark:border-d-hairline flex-row gap-2">
                    <TextInput
                      placeholder="New tag name..."
                      placeholderTextColor={tokens.muted}
                      value={newTagName}
                      onChangeText={setNewTagName}
                      className="flex-1 h-10 px-3 rounded-md bg-surface2 dark:bg-d-surface2 text-ink dark:text-d-ink"
                    />
                    <Pressable
                      disabled={!newTagName.trim()}
                      onPress={async () => {
                        const name = newTagName.trim();
                        if (!name) return;
                        setLoadingTags(true);
                        try {
                          const res = await api.post('/v1/mobile/contacts/tags', { name, color: '#10B981' });
                          if (res.success) {
                            setAvailableTags([...availableTags, res.tag]);
                            setNewTagName('');
                            // Auto toggle it on for this contact
                            await api.post(`/v1/mobile/contacts/${dbContactId}/tags/toggle`, { tag_id: res.tag.id });
                            setContactTags([...contactTags, res.tag]);
                          } else {
                            showDialog('Error', res.message || 'Failed to create tag');
                          }
                        } catch (err: any) {
                          showDialog('Error', err.message || 'Could not create tag');
                        } finally {
                          setLoadingTags(false);
                        }
                      }}
                      className={`h-10 px-4 rounded-md justify-center items-center ${newTagName.trim() ? 'bg-accent' : 'bg-surface3 dark:bg-d-surface3'}`}
                    >
                      <Text className={newTagName.trim() ? 'text-white font-bold' : 'text-muted dark:text-d-muted'}>Create</Text>
                    </Pressable>
                  </View>

                  <FlatList
                    data={availableTags}
                    keyExtractor={(item) => item.id.toString()}
                    contentContainerStyle={{ padding: 16, gap: 10 }}
                    ListEmptyComponent={
                      <View className="py-12 items-center justify-center">
                        <Text className="text-xs text-muted dark:text-d-muted">No tags available.</Text>
                      </View>
                    }
                    renderItem={({ item }) => {
                      const isSelected = contactTags.some((t) => t.id === item.id);
                      return (
                        <Pressable
                          onPress={async () => {
                            // Optimistic update
                            if (isSelected) {
                              setContactTags(contactTags.filter((t) => t.id !== item.id));
                            } else {
                              setContactTags([...contactTags, item]);
                            }
                            try {
                              await api.post(`/v1/mobile/contacts/${dbContactId}/tags/toggle`, { tag_id: item.id });
                            } catch (err: any) {
                              fetchTags();
                              showDialog('Error', 'Failed to toggle tag');
                            }
                          }}
                          className="flex-row items-center justify-between p-3.5 rounded-lg border border-hairline dark:border-d-hairline bg-surface2 dark:bg-d-surface2 active:bg-surface3"
                        >
                          <View className="flex-row items-center gap-3">
                            <View className="w-4 h-4 rounded-full" style={{ backgroundColor: item.color || '#10B981' }} />
                            <Text className="font-semibold text-[14px] text-ink dark:text-d-ink">{item.name}</Text>
                          </View>
                          {isSelected && <Text className="text-accent dark:text-d-accent font-bold">✓</Text>}
                        </Pressable>
                      );
                    }}
                  />
                </>
              )}
            </View>
          </View>
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

      {/* Message Actions Sheet Menu */}
      {showMsgActions && selectedMsg && (
        <Modal transparent visible={showMsgActions} animationType="fade">
          <Pressable onPress={() => {
            setShowMsgActions(false);
            setSelectedMsg(null);
          }} className="flex-1 bg-black/40 justify-end">
            <View className="bg-surface dark:bg-d-surface rounded-t-2xl p-4 gap-2" style={{ paddingBottom: insets.bottom + 16 }}>
              <View className="flex-row justify-between items-center px-1 pb-4 pt-2">
                {['👍', '❤️', '😂', '😮', '😢', '🙏'].map(emoji => (
                  <Pressable
                    key={emoji}
                    onPress={async () => {
                      setShowMsgActions(false);
                      const msg = selectedMsg;
                      if (!msg || !msg.id) return;
                      // Optimistic UI update
                      setMessages((prevMsgs) => prevMsgs.map((m) => {
                        if (m.id === msg.id) {
                          const meta = m.metadata || {};
                          return { ...m, metadata: { ...meta, reaction: emoji } };
                        }
                        return m;
                      }));
                      try {
                        await api.post(`/v1/mobile/messages/${msg.id}/react`, { emoji });
                      } catch (err: any) {
                        showDialog('Reaction Failed', err.message || 'Could not send reaction.');
                      } finally {
                        setSelectedMsg(null);
                      }
                    }}
                    className="p-2.5 bg-surface2 dark:bg-d-surface2 rounded-full active:opacity-70 border border-hairline dark:border-d-hairline"
                  >
                    <Text style={{ fontSize: 24, lineHeight: 28 }}>{emoji}</Text>
                  </Pressable>
                ))}
              </View>
              <View className="items-center pb-2 border-b border-hairline dark:border-d-hairline">
                <Text className="text-xs text-muted dark:text-d-muted font-bold uppercase tracking-wider">Message Actions</Text>
              </View>
              <Pressable
                onPress={() => {
                  setShowMsgActions(false);
                  const msg = selectedMsg;
                  setSelectedMsg(null);
                  if (msg) {
                    setReplyingTo(msg);
                  }
                }}
                className="py-3.5 px-2 active:bg-surface2 dark:active:bg-d-surface2 rounded-md"
              >
                <Text className="text-sm font-semibold text-ink dark:text-d-ink">↩️ Reply to Message</Text>
              </Pressable>

              {selectedMsg?.media_url ? (
                <>
                  <Pressable
                    onPress={() => {
                      setShowMsgActions(false);
                      const msg = selectedMsg;
                      setSelectedMsg(null);
                      if (msg && msg.media_url) {
                        setMediaViewer({
                          uri: msg.media_url,
                          type: (msg.media_type || 'image') as any,
                        });
                      }
                    }}
                    className="py-3.5 px-2 active:bg-surface2 dark:active:bg-d-surface2 rounded-md"
                  >
                    <Text className="text-sm font-semibold text-ink dark:text-d-ink">🔍 View / Open Media</Text>
                  </Pressable>

                  <Pressable
                    onPress={async () => {
                      setShowMsgActions(false);
                      const msg = selectedMsg;
                      setSelectedMsg(null);
                      if (msg && msg.media_url) {
                        const targetUrl = resolveMediaUrl(msg.media_url) || msg.media_url;
                        try {
                          await Linking.openURL(targetUrl);
                        } catch (e) {
                          showDialog('Open Failed', 'Could not open media URL.');
                        }
                      }
                    }}
                    className="py-3.5 px-2 active:bg-surface2 dark:active:bg-d-surface2 rounded-md"
                  >
                    <Text className="text-sm font-semibold text-ink dark:text-d-ink">📥 Download / Save Media</Text>
                  </Pressable>
                </>
              ) : null}
              <Pressable
                onPress={async () => {
                  setShowMsgActions(false);
                  const msg = selectedMsg;
                  if (!msg || !msg.id) return;
                  try {
                    setLoading(true);
                    const res = await api.post(`/v1/mobile/messages/${msg.id}/star`);
                    if (res.success) {
                      // Update local messages state instantly
                      setMessages((prevMsgs) =>
                        prevMsgs.map((m) =>
                          m.id === msg.id ? { ...m, isStarred: res.is_starred } : m
                        )
                      );
                    }
                  } catch (err: any) {
                    showDialog('Action Failed', err.message || 'Could not toggle star on this message.');
                  } finally {
                    setLoading(false);
                    setSelectedMsg(null);
                  }
                }}
                className="py-3.5 px-2 active:bg-surface2 dark:active:bg-d-surface2 rounded-md"
              >
                <Text className="text-sm font-semibold text-ink dark:text-d-ink">
                  {selectedMsg.isStarred ? '⭐ Unstar Message' : '⭐ Star Message'}
                </Text>
              </Pressable>
              <Pressable
                onPress={() => {
                  setShowMsgActions(false);
                  const msg = selectedMsg;
                  setSelectedMsg(null);
                  if (msg) {
                    openForwardModal(msg);
                  }
                }}
                className="py-3.5 px-2 active:bg-surface2 dark:active:bg-d-surface2 rounded-md"
              >
                <Text className="text-sm font-semibold text-ink dark:text-d-ink">➡️ Forward Message</Text>
              </Pressable>
              <Pressable
                onPress={() => {
                  setShowMsgActions(false);
                  setSelectedMsg(null);
                }}
                className="py-3.5 px-2 active:bg-surface2 dark:active:bg-d-surface2 rounded-md"
              >
                <Text className="text-sm font-semibold text-danger dark:text-d-danger">❌ Cancel</Text>
              </Pressable>
            </View>
          </Pressable>
        </Modal>
      )}

      {/* Forward Destination Modal */}
      {showForwardPicker && (
        <Modal transparent visible={showForwardPicker} animationType="slide">
          <View className="flex-1 bg-black/40 justify-end">
            <View className="bg-surface dark:bg-d-surface rounded-t-2xl max-h-[75%]" style={{ paddingBottom: insets.bottom + 16 }}>
              <View className="flex-row items-center justify-between px-[18px] py-4 border-b border-hairline dark:border-d-hairline">
                <Text className="text-base font-bold text-ink dark:text-d-ink">Forward Message</Text>
                <Pressable onPress={() => {
                  setShowForwardPicker(false);
                  setForwardingMsg(null);
                }} className="p-1">
                  <Text className="text-accent dark:text-d-accent font-semibold text-sm">Cancel</Text>
                </Pressable>
              </View>

              {loadingConversations ? (
                <View className="py-20 items-center justify-center">
                  <ActivityIndicator size="small" color={tokens.accent} />
                  <Text className="text-xs text-muted dark:text-d-muted mt-2">Loading conversations...</Text>
                </View>
              ) : (
                <FlatList
                  data={activeConversations}
                  keyExtractor={(item) => String(item.id)}
                  contentContainerStyle={{ padding: 18, gap: 10 }}
                  ListEmptyComponent={
                    <View className="py-12 items-center justify-center">
                      <Text className="text-xs text-muted dark:text-d-muted">No active conversations found.</Text>
                    </View>
                  }
                  renderItem={({ item }) => (
                    <Pressable
                      onPress={() => handleForwardMessage(item.id, item.name)}
                      className="flex-row items-center gap-3 p-3.5 rounded-lg border border-hairline dark:border-d-hairline bg-surface2 dark:bg-d-surface2 active:bg-surface3"
                    >
                      <Avatar name={item.name} size={36} dot={item.online ? tokens.ok : null} />
                      <View className="flex-1 min-w-0">
                        <Text numberOfLines={1} className="text-sm font-semibold text-ink dark:text-d-ink">
                          {item.name}
                        </Text>
                        <Text className="text-[11.5px] text-muted dark:text-d-muted mt-[3px]">
                          {item.tag} · {item.phone}
                        </Text>
                      </View>
                    </Pressable>
                  )}
                />
              )}
            </View>
          </View>
        </Modal>
      )}

      {/* Save / Edit Contact Modal */}
      {showSaveContact && (
        <Modal transparent visible={showSaveContact} animationType="slide">
          <View className="flex-1 bg-black/40 justify-end">
            <View
              className="bg-surface dark:bg-d-surface rounded-t-2xl px-[18px] pt-4"
              style={{ paddingBottom: insets.bottom + 16 }}
            >
              <View className="flex-row items-center justify-between pb-4 border-b border-hairline dark:border-d-hairline mb-4">
                <Text className="text-base font-bold text-ink dark:text-d-ink">
                  {dbContactId ? 'Edit Contact' : 'Save Contact'}
                </Text>
                <Pressable onPress={() => setShowSaveContact(false)} className="p-1">
                  <Text className="text-accent dark:text-d-accent font-semibold text-sm">Cancel</Text>
                </Pressable>
              </View>

              <ScrollView className="max-h-[360px]">
                <View className="mb-4">
                  <Text className="text-xs font-semibold text-muted dark:text-d-muted uppercase tracking-wider mb-1.5">Name *</Text>
                  <View className="flex-row items-center bg-surface2 dark:bg-d-surface2 rounded-lg px-3 py-3">
                    <TextInput
                      value={saveContactName}
                      onChangeText={setSaveContactName}
                      placeholder="Contact name"
                      placeholderTextColor={tokens.muted}
                      className="flex-1 text-ink dark:text-d-ink text-sm p-0"
                    />
                  </View>
                </View>

                <View className="mb-4">
                  <Text className="text-xs font-semibold text-muted dark:text-d-muted uppercase tracking-wider mb-1.5">Email (optional)</Text>
                  <View className="flex-row items-center bg-surface2 dark:bg-d-surface2 rounded-lg px-3 py-3">
                    <TextInput
                      value={saveContactEmail}
                      onChangeText={setSaveContactEmail}
                      placeholder="email@example.com"
                      placeholderTextColor={tokens.muted}
                      keyboardType="email-address"
                      autoCapitalize="none"
                      className="flex-1 text-ink dark:text-d-ink text-sm p-0"
                    />
                  </View>
                </View>

                <View className="mb-6">
                  <Text className="text-xs font-semibold text-muted dark:text-d-muted uppercase tracking-wider mb-1.5">Notes (optional)</Text>
                  <View className="bg-surface2 dark:bg-d-surface2 rounded-lg px-3 py-3">
                    <TextInput
                      value={saveContactNotes}
                      onChangeText={setSaveContactNotes}
                      placeholder="Add a note..."
                      placeholderTextColor={tokens.muted}
                      multiline
                      numberOfLines={3}
                      className="flex-1 text-ink dark:text-d-ink text-sm p-0"
                      style={{ minHeight: 60 }}
                    />
                  </View>
                </View>

                <View className="mb-2">
                  <Pressable
                    onPress={handleSaveContact}
                    disabled={savingContact || !saveContactName.trim()}
                    className="w-full bg-accent dark:bg-d-accent py-3.5 rounded-lg items-center justify-center active:opacity-90 disabled:opacity-50"
                  >
                    {savingContact ? (
                      <ActivityIndicator size="small" color="#FFFFFF" />
                    ) : (
                      <Text className="text-white text-[15px] font-bold">
                        {dbContactId ? 'Save Changes' : 'Save Contact'}
                      </Text>
                    )}
                  </Pressable>
                </View>
              </ScrollView>
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

      {/* Media Viewer */}
      {mediaViewer && (
        <MediaViewer
          visible
          uri={mediaViewer.uri}
          type={mediaViewer.type}
          onClose={() => setMediaViewer(null)}
        />
      )}
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
