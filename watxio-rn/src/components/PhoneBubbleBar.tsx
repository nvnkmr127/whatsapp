// src/components/PhoneBubbleBar.tsx — keyboard-aware bottom composer used by Chat.

import React, { useState, useRef, useEffect } from 'react';
import { View, TextInput, Pressable, Keyboard, PanResponder, Animated, Text, StyleSheet } from 'react-native';
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

// Dynamic Animated Waveform visualizer for WhatsApp Voice Recording
function WaveformVisualizer({ isRecording, isPaused }: { isRecording: boolean; isPaused: boolean }) {
  const [bars, setBars] = useState<number[]>([
    4, 8, 14, 22, 12, 18, 26, 14, 8, 16, 24, 10, 18, 14, 20, 12, 8, 16, 22, 14, 10, 18, 12, 6
  ]);

  useEffect(() => {
    if (!isRecording || isPaused) return;

    const interval = setInterval(() => {
      setBars((prev) =>
        prev.map(() => Math.floor(Math.random() * 20) + 4)
      );
    }, 120);

    return () => clearInterval(interval);
  }, [isRecording, isPaused]);

  return (
    <View style={styles.waveformContainer}>
      {bars.map((h, i) => (
        <View
          key={i}
          style={[
            styles.waveformBar,
            { height: h }
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

  const pan = useRef(new Animated.ValueXY()).current;

  const formatTime = (seconds: number) => {
    return `${Math.floor(seconds / 60)}:${(seconds % 60).toString().padStart(2, '0')}`;
  };

  return (
    <>
      <View style={[styles.composerContainer, { backgroundColor: tokens.bg }]}>
        {isRecording ? (
          // ── Native WhatsApp Voice Recording Bar ──
          <View style={styles.recordingOuter}>
            {/* Top Row: Duration Timer (left) + Live Waveform Visualizer (right) */}
            <View style={styles.recordingTopRow}>
              <Text style={[styles.timerText, { color: tokens.ink }]}>
                {formatTime(recordingSeconds)}
              </Text>
              <WaveformVisualizer isRecording={isRecording} isPaused={isPaused} />
            </View>

            {/* Bottom Row: Trash (left) | Pause/Resume Pill (center) | Green Send (right) */}
            <View style={styles.recordingBottomRow}>
              {/* Trash / Delete Button */}
              <Pressable
                onPress={onCancelRecording}
                style={({ pressed }) => [styles.trashButton, pressed && { opacity: 0.8 }]}
              >
                <Trash2 size={20} color="#F87171" />
              </Pressable>

              {/* Pause / Resume Pill Button */}
              <Pressable
                onPress={onTogglePause}
                style={({ pressed }) => [
                  styles.pausePill,
                  { backgroundColor: tokens.surface2 || '#202c33' },
                  pressed && { opacity: 0.85 }
                ]}
              >
                {isPaused ? (
                  <>
                    <Play size={18} color={tokens.ink} fill={tokens.ink} />
                    <Text style={[styles.pausePillText, { color: tokens.ink }]}>Resume</Text>
                  </>
                ) : (
                  <>
                    <Pause size={18} color={tokens.ink} fill={tokens.ink} />
                    <Text style={[styles.pausePillText, { color: tokens.ink }]}>Pause</Text>
                  </>
                )}
              </Pressable>

              {/* Green Send Button */}
              <Pressable
                onPress={onSendRecording}
                style={({ pressed }) => [styles.sendAudioButton, pressed && { opacity: 0.85 }]}
              >
                <Send size={18} color="#FFFFFF" strokeWidth={2.2} style={{ marginLeft: 2 }} />
              </Pressable>
            </View>
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
    paddingHorizontal: 12,
    paddingVertical: 10,
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: 8,
    overflow: 'hidden',
  },
  recordingOuter: {
    flex: 1,
    paddingHorizontal: 4,
    paddingVertical: 2,
  },
  recordingTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 8,
    marginBottom: 12,
  },
  timerText: {
    fontFamily: 'monospace',
    fontSize: 18,
    fontWeight: '600',
  },
  waveformContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 2,
    height: 28,
  },
  waveformBar: {
    width: 2.5,
    borderRadius: 2,
    backgroundColor: '#8696a0',
  },
  recordingBottomRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 10,
  },
  trashButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#3B1C24',
  },
  pausePill: {
    flex: 1,
    height: 44,
    borderRadius: 22,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingHorizontal: 16,
  },
  pausePillText: {
    fontSize: 14,
    fontWeight: '600',
  },
  sendAudioButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#00a884',
  },
  micWrapper: {
    marginBottom: 2,
  },
  micButton: {
    width: 42,
    height: 42,
    borderRadius: 21,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
