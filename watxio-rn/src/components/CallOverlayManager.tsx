import React, { useState, useEffect, useRef, useCallback } from 'react';
import { View, Text, Modal, Pressable, Animated, ActivityIndicator } from 'react-native';
import { Phone, PhoneOff, Mic, MicOff, Volume2 } from 'lucide-react-native';
import { useGlobalState } from '@/store';
import { api } from '@/services/api';
import { CallWebRTC } from '@/services/webrtc';
import { useTokens } from '@/theme';
import { Avatar } from '@/components/Avatar';

interface ActiveCall {
  id: number;
  call_id: string;
  direction: 'inbound' | 'outbound';
  status: 'initiated' | 'ringing' | 'in_progress' | 'completed' | 'failed' | 'rejected' | 'missed';
  contact_name: string;
  contact_phone: string;
  initiated_at?: string;
  // SDP fields added by the backend to support WebRTC on mobile
  offer_sdp?: string | null;   // inbound: caller's offer that the agent must answer
  answer_sdp?: string | null;  // outbound: remote party's answer (delivered via webhook)
}

export function CallOverlayManager() {
  const [globalState] = useGlobalState();
  const { tokens } = useTokens();
  const [activeCall, setActiveCall] = useState<ActiveCall | null>(null);
  const [callTime, setCallTime] = useState(0);
  const [isMuted, setIsMuted] = useState(false);
  const [isSpeaker, setIsSpeaker] = useState(false);
  const [isActionLoading, setIsActionLoading] = useState(false);

  const pulseAnim = useRef(new Animated.Value(1)).current;
  const pollTimerRef = useRef<NodeJS.Timeout | null>(null);
  const callTimerRef = useRef<NodeJS.Timeout | null>(null);
  // Track the last answer_sdp we have already applied so we don't re-apply on
  // every poll cycle.
  const appliedAnswerSdpRef = useRef<string | null>(null);
  // Ref copy of activeCall for use inside async callbacks without stale closures.
  const activeCallRef = useRef<ActiveCall | null>(null);

  // ── 1. Active-call polling ────────────────────────────────────────────────
  useEffect(() => {
    if (!globalState.token || !globalState.activeTeamId) {
      setActiveCall(null);
      if (pollTimerRef.current) clearInterval(pollTimerRef.current);
      pollTimerRef.current = null;
      return;
    }

    const checkActiveCalls = async () => {
      try {
        // API returns envelope: { success, message, data: ActiveCall[] }
        const response = await api.get<{ success: boolean; data: ActiveCall[] }>(
          '/v1/calls/active',
          { 'X-Silent-Errors': 'true' }
        );
        const calls = response?.data ?? [];

        if (calls.length > 0) {
          const fresh = calls[0];
          setActiveCall((prev) => {
            // Preserve local status optimistic update (e.g. in_progress after answer)
            // but always take the server's value when it has moved forward.
            if (!prev) return fresh;
            const statusOrder = ['initiated', 'ringing', 'in_progress', 'completed', 'failed', 'rejected', 'missed'];
            const prevIdx = statusOrder.indexOf(prev.status);
            const freshIdx = statusOrder.indexOf(fresh.status);
            return freshIdx >= prevIdx ? fresh : { ...fresh, status: prev.status };
          });
          activeCallRef.current = fresh;

          // ── Outbound: apply remote answer SDP once it arrives ────────────
          if (
            fresh.direction === 'outbound' &&
            fresh.answer_sdp &&
            fresh.answer_sdp !== appliedAnswerSdpRef.current &&
            CallWebRTC.hasSession()
          ) {
            appliedAnswerSdpRef.current = fresh.answer_sdp;
            CallWebRTC.session().applyAnswer(fresh.answer_sdp).catch((e) =>
              console.warn('[WebRTC] applyAnswer failed:', e)
            );
          }
        } else {
          // No active calls — tear down WebRTC if we had one
          if (activeCallRef.current) {
            CallWebRTC.end();
            appliedAnswerSdpRef.current = null;
          }
          setActiveCall(null);
          activeCallRef.current = null;
        }
      } catch (error) {
        console.debug('[Call Polling Error]', error);
      }
    };

    checkActiveCalls();
    pollTimerRef.current = setInterval(checkActiveCalls, 3000);

    return () => {
      if (pollTimerRef.current) clearInterval(pollTimerRef.current);
      pollTimerRef.current = null;
    };
  }, [globalState.token, globalState.activeTeamId]);

  // ── 2. Pulse animation (ringing) ─────────────────────────────────────────
  useEffect(() => {
    const ringing = activeCall?.status === 'initiated' || activeCall?.status === 'ringing';
    if (ringing) {
      Animated.loop(
        Animated.sequence([
          Animated.timing(pulseAnim, { toValue: 1.3, duration: 1200, useNativeDriver: true }),
          Animated.timing(pulseAnim, { toValue: 1, duration: 1200, useNativeDriver: true }),
        ])
      ).start();
    } else {
      pulseAnim.setValue(1);
    }
  }, [activeCall?.status]);

  // ── 3. Call duration timer ────────────────────────────────────────────────
  useEffect(() => {
    if (activeCall?.status === 'in_progress') {
      if (!callTimerRef.current) {
        setCallTime(0);
        callTimerRef.current = setInterval(() => setCallTime((t) => t + 1), 1000);
      }
    } else {
      if (callTimerRef.current) clearInterval(callTimerRef.current);
      callTimerRef.current = null;
      setCallTime(0);
    }
    // Cleanup runs on every status change AND on unmount — safe because we
    // null the ref after clearing so double-clear is a no-op.
    return () => {
      if (callTimerRef.current) clearInterval(callTimerRef.current);
      callTimerRef.current = null;
    };
  }, [activeCall?.status]);

  // ── Actions ───────────────────────────────────────────────────────────────

  const handleAnswer = useCallback(async () => {
    if (!activeCall) return;
    setIsActionLoading(true);
    try {
      let sdp: string | undefined;

      // Generate a WebRTC answer SDP from the caller's offer (if available).
      // Meta requires the 'session' / SDP parameter — without it the answer
      // API returns error 131009 "Missing session parameter".
      if (activeCall.offer_sdp) {
        try {
          sdp = await CallWebRTC.session().initInbound(activeCall.offer_sdp);
        } catch (rtcErr) {
          console.warn('[WebRTC] initInbound failed, answering without SDP:', rtcErr);
        }
      }

      await api.post(`/v1/calls/${activeCall.call_id}/answer`, sdp ? { sdp } : undefined);
      setActiveCall((prev) => prev ? { ...prev, status: 'in_progress' } : null);
    } catch (err: any) {
      console.warn('Failed to answer call:', err);
    } finally {
      setIsActionLoading(false);
    }
  }, [activeCall]);

  const handleDecline = useCallback(async () => {
    if (!activeCall) return;
    setIsActionLoading(true);
    try {
      await api.post(`/v1/calls/${activeCall.call_id}/reject`);
      CallWebRTC.end();
      appliedAnswerSdpRef.current = null;
      setActiveCall(null);
      activeCallRef.current = null;
    } catch (err: any) {
      console.warn('Failed to decline call:', err);
    } finally {
      setIsActionLoading(false);
    }
  }, [activeCall]);

  const handleEndCall = useCallback(async () => {
    if (!activeCall) return;
    setIsActionLoading(true);
    try {
      await api.post(`/v1/calls/${activeCall.call_id}/end`);
    } catch (err: any) {
      console.warn('Failed to end call:', err);
    } finally {
      CallWebRTC.end();
      appliedAnswerSdpRef.current = null;
      setActiveCall(null);
      activeCallRef.current = null;
      setIsActionLoading(false);
    }
  }, [activeCall]);

  const handleToggleMute = useCallback(() => {
    const next = !isMuted;
    setIsMuted(next);
    CallWebRTC.session().setMuted(next);
  }, [isMuted]);

  // ── Render ────────────────────────────────────────────────────────────────

  if (!activeCall) return null;

  const isRinging = activeCall.status === 'initiated' || activeCall.status === 'ringing';
  const isInbound = activeCall.direction === 'inbound';

  return (
    <Modal transparent visible animationType="slide">
      <View className="flex-1 bg-ink/95 dark:bg-black/95 items-center justify-between py-16 px-6">
        {/* Header */}
        <View className="items-center mt-8">
          <Text className="text-white/60 text-xs font-semibold uppercase tracking-widest mb-2">
            WhatsApp Business Call
          </Text>
          <Text className="text-white text-3xl font-bold mt-1 text-center">
            {activeCall.contact_name}
          </Text>
          <Text className="text-white/40 text-sm mt-1 font-mono">
            {activeCall.contact_phone}
          </Text>
        </View>

        {/* Avatar + pulse */}
        <View className="items-center justify-center my-12">
          {isRinging && (
            <Animated.View
              style={{
                position: 'absolute',
                width: 140,
                height: 140,
                borderRadius: 70,
                backgroundColor: tokens.ok,
                opacity: pulseAnim.interpolate({ inputRange: [1, 1.3], outputRange: [0.4, 0] }),
                transform: [{ scale: pulseAnim }],
              }}
            />
          )}
          <Avatar name={activeCall.contact_name} size={120} ring={tokens.ok} />

          <View className="mt-8 items-center">
            {isRinging ? (
              <Text className="text-ok font-semibold tracking-wide uppercase text-sm">
                {isInbound ? 'Incoming Call...' : 'Ringing...'}
              </Text>
            ) : (
              <View className="items-center">
                <Text className="text-accent font-semibold tracking-wide uppercase text-sm mb-1">
                  Connected
                </Text>
                <Text className="text-white/80 text-xl font-mono font-medium">
                  {Math.floor(callTime / 60)}:{(callTime % 60).toString().padStart(2, '0')}
                </Text>
              </View>
            )}
          </View>
        </View>

        {/* Controls */}
        <View className="w-full items-center gap-10 mb-8">
          {/* Mute / Speaker row (active calls only) */}
          {!isRinging && (
            <View className="flex-row justify-center gap-12 w-full">
              <Pressable
                onPress={handleToggleMute}
                className={`w-14 h-14 rounded-full items-center justify-center border ${
                  isMuted ? 'bg-white border-white' : 'bg-transparent border-white/20'
                }`}
              >
                {isMuted
                  ? <MicOff size={22} color="#000000" />
                  : <Mic size={22} color="#FFFFFF" />}
              </Pressable>

              <Pressable
                onPress={() => setIsSpeaker(!isSpeaker)}
                className={`w-14 h-14 rounded-full items-center justify-center border ${
                  isSpeaker ? 'bg-white border-white' : 'bg-transparent border-white/20'
                }`}
              >
                <Volume2 size={22} color={isSpeaker ? '#000000' : '#FFFFFF'} />
              </Pressable>
            </View>
          )}

          {/* Accept / Decline / End */}
          <View className="flex-row items-center justify-center gap-12 w-full">
            {isActionLoading ? (
              <ActivityIndicator size="large" color="#FFFFFF" />
            ) : isRinging && isInbound ? (
              <>
                <Pressable
                  onPress={handleDecline}
                  className="w-16 h-16 rounded-full items-center justify-center active:opacity-90 shadow-lg"
                  style={{ backgroundColor: tokens.danger }}
                >
                  <PhoneOff size={26} color="#FFFFFF" strokeWidth={2} />
                </Pressable>
                <Pressable
                  onPress={handleAnswer}
                  className="w-16 h-16 rounded-full items-center justify-center active:opacity-90 shadow-lg"
                  style={{ backgroundColor: tokens.ok }}
                >
                  <Phone size={26} color="#FFFFFF" strokeWidth={2} />
                </Pressable>
              </>
            ) : (
              <Pressable
                onPress={handleEndCall}
                className="w-16 h-16 rounded-full items-center justify-center active:opacity-90 shadow-lg"
                style={{ backgroundColor: tokens.danger }}
              >
                <Phone
                  size={26}
                  color="#FFFFFF"
                  strokeWidth={2}
                  style={{ transform: [{ rotate: '135deg' }] }}
                />
              </Pressable>
            )}
          </View>
        </View>
      </View>
    </Modal>
  );
}
