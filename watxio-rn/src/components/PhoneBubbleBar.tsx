// src/components/PhoneBubbleBar.tsx — keyboard-aware bottom composer used by Chat.

import React, { useState, useRef, useEffect } from 'react';
import { View, TextInput, Pressable, Keyboard, Animated, Text, StyleSheet } from 'react-native';
import { Paperclip, Smile, Send, Mic, X, Trash2, Pause, Play } from 'lucide-react-native';
import { useTokens } from '@/theme';
import { IconButton } from './Button';
import EmojiPicker from 'rn-emoji-keyboard';
import type { ChatMessage } from '@/types';
import { safeExtractText } from '@/utils/text';

interface Props {
  value: string;
  onChange: (s: string) => void;
  onSend: () => void;
  onAttach?: () => void;
  hasMedia?: boolean;
  replyingTo?: ChatMessage | null;
  contactName?: string;
  onCancelReply?: () => void;
  
  // Voice messaging props
  isRecording?: boolean;
  isPaused?: boolean;
  recordingSeconds?: number;
  onStartRecording?: () => void;
  onCancelRecording?: () => void;
  onSendRecording?: () => void;
  onTogglePause?: () => void;
}

// WhatsApp Live Pulsing Red Recording Indicator
function PulsingDot() {
  const pulseAnim = useRef(new Animated.Value(1)).current;

  useEffect(() => {
    const pulse = Animated.loop(
      Animated.sequence([
        Animated.timing(pulseAnim, {
          toValue: 0.25,
          duration: 550,
          useNativeDriver: true,
        }),
        Animated.timing(pulseAnim, {
          toValue: 1,
          duration: 550,
          useNativeDriver: true,
        }),
      ])
    );
    pulse.start();
    return () => pulse.stop();
  }, [pulseAnim]);

  return (
    <Animated.View
      style={[
        styles.pulsingDot,
        {
          opacity: pulseAnim,
          transform: [
            {
              scale: pulseAnim.interpolate({
                inputRange: [0.25, 1],
                outputRange: [0.85, 1.15],
              }),
            },
          ],
        },
      ]}
    />
  );
}

// WhatsApp Live Dynamic Audio Waveform Spectrum
function WaveformVisualizer({
  isRecording,
  isPaused,
  barColor,
}: {
  isRecording: boolean;
  isPaused: boolean;
  barColor?: string;
}) {
  const [bars, setBars] = useState<number[]>([
    4, 8, 14, 20, 10, 16, 22, 12, 6, 14, 24, 16, 10, 18, 12, 20, 14, 8, 16, 22, 12, 8, 14, 6
  ]);

  useEffect(() => {
    if (!isRecording || isPaused) return;

    const interval = setInterval(() => {
      setBars((prev) =>
        prev.map(() => Math.floor(Math.random() * 18) + 4)
      );
    }, 110);

    return () => clearInterval(interval);
  }, [isRecording, isPaused]);

  return (
    <View style={styles.waveformContainer}>
      {bars.map((h, i) => (
        <View
          key={i}
          style={[
            styles.waveformBar,
            {
              height: h,
              backgroundColor: barColor || '#8696A0',
              opacity: isPaused ? 0.45 : 0.9,
            },
          ]}
        />
      ))}
    </View>
  );
}

export function Composer({ 
  value, 
  onChange, 
  onSend, 
  onAttach, 
  hasMedia,
  replyingTo,
  contactName,
  onCancelReply,
  isRecording = false,
  isPaused = false,
  recordingSeconds = 0,
  onStartRecording,
  onCancelRecording,
  onSendRecording,
  onTogglePause
}: Props) {
  const { tokens } = useTokens();
  const [isEmojiPickerOpen, setIsEmojiPickerOpen] = useState(false);
  const hasContent = value.trim().length > 0 || hasMedia;

  const handleEmojiSelect = (emojiData: any) => {
    onChange(value + emojiData.emoji);
  };

  const formatTime = (seconds: number) => {
    return `${Math.floor(seconds / 60)}:${(seconds % 60).toString().padStart(2, '0')}`;
  };

  return (
    <>
      <View style={[styles.composerContainer, { backgroundColor: tokens.bg }]}>
        {isRecording ? (
          // ── WhatsApp Native Single-Row Voice Recording Bar ──
          <View style={styles.recordingRow}>
            {/* 1. Left: Trash / Cancel Button */}
            <Pressable
              onPress={onCancelRecording}
              style={({ pressed }) => [
                styles.trashActionBtn,
                pressed && { opacity: 0.6, transform: [{ scale: 0.92 }] },
              ]}
              hitSlop={10}
            >
              <Trash2 size={22} color="#EF4444" strokeWidth={2} />
            </Pressable>

            {/* 2. Middle: WhatsApp Voice Recording Capsule */}
            <View style={[styles.recordingCapsule, { backgroundColor: tokens.surface2 }]}>
              {/* Red Recording Indicator */}
              {!isPaused ? (
                <PulsingDot />
              ) : (
                <View style={[styles.pulsingDot, { opacity: 0.35 }]} />
              )}

              {/* Duration Timer */}
              <Text style={[styles.timerText, { color: tokens.ink }]}>
                {formatTime(recordingSeconds)}
              </Text>

              {/* Dynamic Animated Waveform Spectrum */}
              <WaveformVisualizer
                isRecording={isRecording}
                isPaused={isPaused}
                barColor={isPaused ? tokens.muted : (tokens.accent || '#00A884')}
              />

              {/* Pause / Resume Control */}
              <Pressable
                onPress={onTogglePause}
                style={({ pressed }) => [
                  styles.pauseResumeBtn,
                  pressed && { opacity: 0.65, transform: [{ scale: 0.92 }] },
                ]}
                hitSlop={10}
              >
                {isPaused ? (
                  <Play size={18} color="#EF4444" fill="#EF4444" />
                ) : (
                  <Pause size={18} color="#EF4444" fill="#EF4444" />
                )}
              </Pressable>
            </View>

            {/* 3. Right: WhatsApp Emerald Send Button */}
            <Pressable
              onPress={onSendRecording}
              style={({ pressed }) => [
                styles.sendAudioButton,
                pressed && { opacity: 0.85, transform: [{ scale: 0.95 }] },
              ]}
            >
              <Send size={18} color="#FFFFFF" strokeWidth={2.4} style={{ marginLeft: 2 }} />
            </Pressable>
          </View>
        ) : (
          // ── Normal Input State ──
          <>
            <IconButton icon={Paperclip} color={tokens.muted} onPress={onAttach} style={{ marginBottom: 4 }} />
            <View className="flex-1 bg-surface2 dark:bg-d-surface2 rounded-[22px] px-3.5 py-2 min-h-[42px] justify-center">
              {replyingTo ? (
                <View className="mb-2 bg-black/5 dark:bg-white/10 p-2.5 rounded-xl border-l-4 border-[#d95a2b] flex-row items-center justify-between">
                  <View className="flex-1 min-w-0 pr-2">
                    <Text className="text-[#d95a2b] font-semibold text-xs mb-0.5" numberOfLines={1}>
                      {replyingTo.kind === 'out' ? 'You' : (contactName || 'Contact')}
                    </Text>
                    <Text className="text-ink/80 dark:text-d-ink/80 text-[11.5px]" numberOfLines={1}>
                      {safeExtractText(replyingTo.text) || replyingTo.media_type || 'Media message'}
                    </Text>
                  </View>
                  <Pressable onPress={onCancelReply} className="p-1">
                    <X size={16} color={tokens.muted} />
                  </Pressable>
                </View>
              ) : null}

              <View className="flex-row items-center gap-1.5 min-h-[28px]">
                <TextInput
                  value={value}
                  onChangeText={onChange}
                  placeholder="Message"
                  placeholderTextColor={tokens.muted}
                  multiline
                  className="flex-1 text-ink dark:text-d-ink text-[14px] p-0 max-h-24"
                  onSubmitEditing={onSend}
                />
                <Pressable 
                  onPress={() => {
                    Keyboard.dismiss();
                    setIsEmojiPickerOpen(true);
                  }} 
                  className="p-1"
                >
                  <Smile size={18} color={tokens.muted} strokeWidth={1.6} />
                </Pressable>
              </View>
            </View>
          </>
        )}

        {!isRecording && (
          hasContent ? (
            <Pressable
              onPress={onSend}
              className="w-[42px] h-[42px] rounded-full bg-accent dark:bg-d-accent items-center justify-center active:opacity-85 mb-0.5"
            >
              <Send size={18} color={tokens.accentInk} strokeWidth={2} />
            </Pressable>
          ) : (
            <Pressable
              onPress={onStartRecording}
              style={styles.micWrapper}
            >
              <View style={[styles.micButton, { backgroundColor: tokens.accent }]}>
                <Mic size={22} color={tokens.accentInk} strokeWidth={1.8} />
              </View>
            </Pressable>
          )
        )}
      </View>

      {isEmojiPickerOpen && (
        <EmojiPicker
          open={isEmojiPickerOpen}
          onClose={() => setIsEmojiPickerOpen(false)}
          onEmojiSelected={handleEmojiSelect}
        />
      )}
    </>
  );
}

const styles = StyleSheet.create({
  composerContainer: {
    paddingHorizontal: 10,
    paddingVertical: 8,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  recordingRow: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    minHeight: 44,
  },
  trashActionBtn: {
    width: 40,
    height: 40,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  recordingCapsule: {
    flex: 1,
    height: 44,
    borderRadius: 22,
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    gap: 8,
  },
  pulsingDot: {
    width: 10,
    height: 10,
    borderRadius: 5,
    backgroundColor: '#EF4444',
  },
  timerText: {
    fontSize: 14,
    fontWeight: '600',
    fontVariant: ['tabular-nums'],
    minWidth: 34,
  },
  waveformContainer: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 2,
    height: 24,
    overflow: 'hidden',
  },
  waveformBar: {
    width: 2.2,
    borderRadius: 1.5,
  },
  pauseResumeBtn: {
    width: 32,
    height: 32,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sendAudioButton: {
    width: 42,
    height: 42,
    borderRadius: 21,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#00A884',
    shadowColor: '#00A884',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.25,
    shadowRadius: 3,
    elevation: 3,
  },
  micWrapper: {
    marginBottom: 1,
  },
  micButton: {
    width: 42,
    height: 42,
    borderRadius: 21,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
