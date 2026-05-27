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
import type { ChatMessage, Conversation, RootStackParamList, Template } from '@/types';
import { Avatar } from '@/components/Avatar';
import { Bubble } from '@/components/Bubble';
import { IconButton } from '@/components/Button';
import { Composer } from '@/components/PhoneBubbleBar';
import { CustomDialog } from '@/components/Dialog';
import { api } from '@/services/api';
import { useGlobalState } from '@/store';
import {
  cacheMessages, cacheMeta,
  loadCachedMessages, loadCachedMeta,
} from '@/services/chatCache';

export default function ChatScreen({ navigation, route }: any) {
  const { tokens } = useTokens();
  const insets = useSafeAreaInsets();
  const nav = navigation;
  const [globalState] = useGlobalState();
  const conversation = route.params.conversation;
  const conversationId = conversation.id;
  const contact = conversation;

  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [draft, setDraft] = useState('');
  const [nextCursor, setNextCursor] = useState<string | null>(null);
  const [loadingEarlier, setLoadingEarlier] = useState(false);
  const [typing, setTyping] = useState(false);
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
  const [templates, setTemplates] = useState<Template[]>([]);
  const [loadingTemplates, setLoadingTemplates] = useState(false);
  const [isMuted, setIsMuted] = useState(false);
  const [isRecording, setIsRecording] = useState(false);
  const [recordingSeconds, setRecordingSeconds] = useState(0);

  // Conversation Lock States
  const [lockOwner, setLockOwner] = useState<{ id: number; name: string } | null>(null);
  const [hasLock, setHasLock] = useState(false);
  const hasLockRef = useRef(false);

  // WebSocket Connection State
  const [wsConnected, setWsConnected] = useState(false);

  // Message Actions Context Menu & Forward States
  const [showMsgActions, setShowMsgActions] = useState(false);
  const [selectedMsg, setSelectedMsg] = useState<ChatMessage | null>(null);
  const [showForwardPicker, setShowForwardPicker] = useState(false);
  const [forwardingMsg, setForwardingMsg] = useState<ChatMessage | null>(null);
  const [activeConversations, setActiveConversations] = useState<Conversation[]>([]);
  const [loadingConversations, setLoadingConversations] = useState(false);

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

  const getCursorFromUrl = (url: string | null) => {
    if (!url) return null;
    const match = url.match(/[?&]cursor=([^&]+)/);
    return match ? match[1] : null;
  };

  // Fetch Conversation metadata and Messages — parallel requests for speed
  const fetchConversationDetails = async (isBackground = false) => {
    if (isBackground) setIsRefreshing(true);
    try {
      // Fire both requests at the same time instead of waiting one-by-one
      const [details, msgsData] = await Promise.all([
        api.get(`/v1/mobile/conversations/${conversationId}`),
        api.get(`/v1/mobile/conversations/${conversationId}/messages`),
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

      const nextUrl = msgsData.next_page_url || null;
      const parsedCursor = getCursorFromUrl(nextUrl);
      setNextCursor((current) => {
        return isBackground && current ? current : parsedCursor;
      });

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
          id: m.id,
          kind: m.direction === 'inbound' ? 'in' : 'out',
          text: m.content || (m.type === 'image' ? '📷 Image' : m.type === 'video' ? '🎥 Video' : '📄 Document'),
          time: msgTime,
          status: m.status === 'read' ? 'read' : m.status === 'delivered' ? 'delivered' : 'sent',
          isStarred: !!m.is_starred,
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
        const newLast  = cleaned[cleaned.length - 1];
        const countChanged = prev.length !== cleaned.length;
        const lastChanged  = prevLast?.text !== newLast?.text || prevLast?.time !== newLast?.time || prevLast?.status !== newLast?.status;

        if (!countChanged && !lastChanged) {
          // Check if any message status/content inside changed (e.g. read receipts)
          const anyMessageChanged = cleaned.some((m, idx) => {
            const p = prev[idx];
            return !p || p.id !== m.id || p.status !== m.status || p.text !== m.text;
          });
          if (!anyMessageChanged) return prev;
        }

        // Haptic feedback (Vibration) on new inbound message in foreground
        if (newLast && newLast.kind === 'in' && (!prevLast || prevLast.id !== newLast.id)) {
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
    }
  };

  const loadEarlierMessages = async () => {
    if (!nextCursor || loadingEarlier) return;
    setLoadingEarlier(true);
    try {
      const response = await api.get(`/v1/mobile/conversations/${conversationId}/messages?cursor=${nextCursor}`);
      const rawMsgs = response.data || [];
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
        mapped.push({
          id: m.id,
          kind: m.direction === 'inbound' ? 'in' : 'out',
          text: m.content || (m.type === 'image' ? '📷 Image' : m.type === 'video' ? '🎥 Video' : '📄 Document'),
          time: msgTime,
          status: m.status === 'read' ? 'read' : m.status === 'delivered' ? 'delivered' : 'sent',
          isStarred: !!m.is_starred,
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
      setLoadingEarlier(false);
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

    const POLL_INTERVAL = wsConnected ? 40000 : 8000;   // 40s when WS is alive, 8s fallback when not
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
        });
      } catch (err) {
        console.warn('[Presence] Heartbeat failed:', err);
      }
    };

    const sendPresenceLeave = async () => {
      try {
        await api.post('/v1/mobile/presence/leave', {
          conversation_id: conversationId,
        });
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
        const response = await api.post(`/v1/conversations/${conversationId}/lock`);
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
                    const takeoverRes = await api.post(`/v1/conversations/${conversationId}/takeover`);
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
        }
      } catch (err) {
        console.warn('[Lock] Failed to lock:', err);
      }
    };

    const performHeartbeat = async () => {
      try {
        const response = await api.post(`/v1/conversations/${conversationId}/heartbeat`);
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
      try {
        await api.post(`/v1/conversations/${conversationId}/unlock`);
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
    if (!socketConfig) return;

    let ws: WebSocket | null = null;
    let active = true;

    // Resolve connection parameters dynamically
    let wsHost = socketConfig.host;
    let wsPort = socketConfig.port;
    let wsScheme = socketConfig.scheme;
    const isSecure = globalState.baseUrl.startsWith('https');

    if (isSecure) {
      wsScheme = 'wss';
      wsPort = '';
    } else {
      wsScheme = 'ws';
    }

    if (wsHost === '127.0.0.1' || wsHost === 'localhost') {
      const match = globalState.baseUrl.match(/^https?:\/\/([^:/]+)/);
      if (match && match[1]) {
        wsHost = match[1];
      }
    }

    const portSuffix = wsPort ? `:${wsPort}` : '';
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
            payload.event === 'MessageSent' ||
            payload.event === 'MessageStatusUpdated'
          ) {
            console.log(`[WS] Invalidation event received: ${payload.event}`);
            fetchConversationDetails(true);
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
          setTimeout(connect, 5000);
        }
      };
    };

    connect();

    return () => {
      active = false;
      if (ws) {
        ws.close();
      }
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
    
    if (isInitialLoad || isNewMessageAdded || typingStarted) {
      const shouldAnimate = isNewMessageAdded || typingStarted;
      const t = setTimeout(() => scroller.current?.scrollToEnd({ animated: shouldAnimate }), 80);
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
      const res = await api.post(`/v1/messages/${forwardingMsg.id}/forward`, {
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
                const takeoverRes = await api.post(`/v1/conversations/${conversationId}/takeover`);
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

      await api.post(`/v1/mobile/conversations/${conversationId}/messages`, payload);
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

      await api.post(`/v1/mobile/conversations/${conversationId}/send-template`, {
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
        { text: 'OK', onPress: () => nav.goBack() }
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
      await api.post('/v1/calls/initiate', {
        phone_number: phoneNumber,
      });
      // Call initiated — the global CallOverlayManager will pick it up via polling.
      // Dismiss the local "ringing" indicator after a short delay so the user can
      // see the transition to the real overlay.
      setTimeout(() => setIsCalling(false), 3000);
    } catch (err: any) {
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
            onPress={() => nav.navigate('Contact', { conversationId, contactId: dbContactId })}
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
              maintainVisibleContentPosition={{ minIndexForVisible: 1 }}
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
                  <Pressable
                    key={i}
                    onLongPress={() => handleMessageLongPress(m)}
                    className="active:opacity-85"
                  >
                    <Bubble
                      kind={m.kind}
                      time={m.time}
                      status={m.status}
                      variant="tail"
                      isStarred={m.isStarred}
                    >
                      {m.text}
                    </Bubble>
                  </Pressable>
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
                      await api.post(`/v1/mobile/conversations/${conversationId}/messages`, {
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

      {/* Message Actions Sheet Menu */}
      {showMsgActions && selectedMsg && (
        <Modal transparent visible={showMsgActions} animationType="fade">
          <Pressable onPress={() => {
            setShowMsgActions(false);
            setSelectedMsg(null);
          }} className="flex-1 bg-black/40 justify-end">
            <View className="bg-surface dark:bg-d-surface rounded-t-2xl p-4 gap-2" style={{ paddingBottom: insets.bottom + 16 }}>
              <View className="items-center pb-2 border-b border-hairline dark:border-d-hairline">
                <Text className="text-xs text-muted dark:text-d-muted font-bold uppercase tracking-wider">Message Actions</Text>
              </View>
              <Pressable
                onPress={async () => {
                  setShowMsgActions(false);
                  const msg = selectedMsg;
                  if (!msg || !msg.id) return;
                  try {
                    setLoading(true);
                    const res = await api.post(`/v1/messages/${msg.id}/star`);
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
