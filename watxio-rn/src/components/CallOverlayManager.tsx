import React, { useState, useEffect, useRef } from 'react';
import { View, Text, Modal, Pressable, Animated, ActivityIndicator } from 'react-native';
import { Phone, PhoneOff, Mic, MicOff, Volume2 } from 'lucide-react-native';
import { useGlobalState } from '@/store';
import { api } from '@/services/api';
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

  // 1. Polling for Active Calls
  useEffect(() => {
    // Only poll if authenticated
    if (!globalState.token || !globalState.activeTeamId) {
      setActiveCall(null);
      if (pollTimerRef.current) {
        clearInterval(pollTimerRef.current);
        pollTimerRef.current = null;
      }
      return;
    }

    const checkActiveCalls = async () => {
      try {
        const response = await api.get<ActiveCall[]>('/v1/calls/active', {
          'X-Silent-Errors': 'true',
        });
        if (response && response.length > 0) {
          setActiveCall(response[0]);
        } else {
          setActiveCall(null);
        }
      } catch (error) {
        // Quietly catch polling failures to avoid UI interruptions
        console.debug('[Call Polling Error]', error);
      }
    };

    // Run immediately and then start interval
    checkActiveCalls();
    pollTimerRef.current = setInterval(checkActiveCalls, 3000);

    return () => {
      if (pollTimerRef.current) {
        clearInterval(pollTimerRef.current);
        pollTimerRef.current = null;
      }
    };
  }, [globalState.token, globalState.activeTeamId]);

  // 2. Pulse Animation for Ringing State
  useEffect(() => {
    const isRinging = activeCall && (activeCall.status === 'initiated' || activeCall.status === 'ringing');
    if (isRinging) {
      Animated.loop(
        Animated.sequence([
          Animated.timing(pulseAnim, {
            toValue: 1.3,
            duration: 1200,
            useNativeDriver: true,
          }),
          Animated.timing(pulseAnim, {
            toValue: 1,
            duration: 1200,
            useNativeDriver: true,
          }),
        ])
      ).start();
    } else {
      pulseAnim.setValue(1);
    }
  }, [activeCall?.status]);

  // 3. Call Duration Timer (for in_progress calls)
  useEffect(() => {
    if (activeCall && activeCall.status === 'in_progress') {
      if (!callTimerRef.current) {
        setCallTime(0);
        callTimerRef.current = setInterval(() => {
          setCallTime((t) => t + 1);
        }, 1000);
      }
    } else {
      if (callTimerRef.current) {
        clearInterval(callTimerRef.current);
        callTimerRef.current = null;
      }
      setCallTime(0);
    }

    return () => {
      if (callTimerRef.current) {
        clearInterval(callTimerRef.current);
        callTimerRef.current = null;
      }
    };
  }, [activeCall?.status]);

  if (!activeCall) return null;

  const isRinging = activeCall.status === 'initiated' || activeCall.status === 'ringing';
  const isInbound = activeCall.direction === 'inbound';

  const handleAnswer = async () => {
    setIsActionLoading(true);
    try {
      await api.post(`/v1/calls/${activeCall.call_id}/answer`);
      // Update locally to minimize latency before next poll
      setActiveCall((prev) => prev ? { ...prev, status: 'in_progress' } : null);
    } catch (err: any) {
      console.warn('Failed to answer call:', err);
    } finally {
      setIsActionLoading(false);
    }
  };

  const handleDecline = async () => {
    setIsActionLoading(true);
    try {
      await api.post(`/v1/calls/${activeCall.call_id}/reject`);
      setActiveCall(null);
    } catch (err: any) {
      console.warn('Failed to decline call:', err);
    } finally {
      setIsActionLoading(false);
    }
  };

  const handleEndCall = async () => {
    setIsActionLoading(true);
    try {
      await api.post(`/v1/calls/${activeCall.call_id}/end`);
      setActiveCall(null);
    } catch (err: any) {
      console.warn('Failed to end call:', err);
    } finally {
      setIsActionLoading(false);
    }
  };

  return (
    <Modal transparent visible={!!activeCall} animationType="slide">
      <View className="flex-1 bg-ink/95 dark:bg-black/95 items-center justify-between py-16 px-6">
        {/* Top Header */}
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

        {/* Pulsing Avatar Area */}
        <View className="items-center justify-center my-12">
          {isRinging && (
            <Animated.View
              style={{
                position: 'absolute',
                width: 140,
                height: 140,
                borderRadius: 70,
                backgroundColor: tokens.ok,
                opacity: pulseAnim.interpolate({
                  inputRange: [1, 1.3],
                  outputRange: [0.4, 0],
                }),
                transform: [{ scale: pulseAnim }],
              }}
            />
          )}
          <Avatar name={activeCall.contact_name} size={120} ring={tokens.ok} />
          
          <View className="mt-8 items-center">
            {isRinging ? (
              <Text className="text-ok font-semibold tracking-wide uppercase text-sm animate-pulse">
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

        {/* Action Controls */}
        <View className="w-full items-center gap-10 mb-8">
          {/* Interactive Placeholder Buttons (Mute / Speaker) */}
          {!isRinging && (
            <View className="flex-row justify-center gap-12 w-full">
              {/* Mute Toggle */}
              <Pressable
                onPress={() => setIsMuted(!isMuted)}
                className={`w-14 h-14 rounded-full items-center justify-center border ${
                  isMuted
                    ? 'bg-white border-white'
                    : 'bg-transparent border-white/20'
                }`}
              >
                {isMuted ? (
                  <MicOff size={22} color="#000000" />
                ) : (
                  <Mic size={22} color="#FFFFFF" />
                )}
              </Pressable>

              {/* Speaker Toggle */}
              <Pressable
                onPress={() => setIsSpeaker(!isSpeaker)}
                className={`w-14 h-14 rounded-full items-center justify-center border ${
                  isSpeaker
                    ? 'bg-white border-white'
                    : 'bg-transparent border-white/20'
                }`}
              >
                <Volume2 size={22} color={isSpeaker ? '#000000' : '#FFFFFF'} />
              </Pressable>
            </View>
          )}

          {/* Accept / Decline Action Buttons */}
          <View className="flex-row items-center justify-center gap-12 w-full">
            {isActionLoading ? (
              <ActivityIndicator size="large" color="#FFFFFF" />
            ) : isRinging && isInbound ? (
              <>
                {/* Reject / Decline Button */}
                <Pressable
                  onPress={handleDecline}
                  className="w-16 h-16 bg-danger rounded-full items-center justify-center active:opacity-90 shadow-lg shadow-black/40"
                  style={{ backgroundColor: tokens.danger }}
                >
                  <PhoneOff size={26} color="#FFFFFF" strokeWidth={2} />
                </Pressable>

                {/* Answer / Accept Button */}
                <Pressable
                  onPress={handleAnswer}
                  className="w-16 h-16 bg-ok rounded-full items-center justify-center active:opacity-90 shadow-lg shadow-black/40 animate-bounce"
                  style={{ backgroundColor: tokens.ok }}
                >
                  <Phone size={26} color="#FFFFFF" strokeWidth={2} />
                </Pressable>
              </>
            ) : (
              /* End Call Button (Ringing outbound, or active in_progress call) */
              <Pressable
                onPress={handleEndCall}
                className="w-16 h-16 bg-danger rounded-full items-center justify-center active:opacity-90 shadow-lg shadow-black/40"
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
