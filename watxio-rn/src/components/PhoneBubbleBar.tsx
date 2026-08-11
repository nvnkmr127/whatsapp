// src/components/PhoneBubbleBar.tsx — keyboard-aware bottom composer used by Chat.

import React, { useState, useRef } from 'react';
import { View, TextInput, Pressable, Keyboard, PanResponder, Animated, Text } from 'react-native';
import { Paperclip, Smile, Send, Mic, X } from 'lucide-react-native';
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
  recordingSeconds?: number;
  onStartRecording?: () => void;
  onCancelRecording?: () => void;
  onSendRecording?: () => void;
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
  recordingSeconds = 0,
  onStartRecording,
  onCancelRecording,
  onSendRecording
}: Props) {
  const { tokens } = useTokens();
  const [isEmojiPickerOpen, setIsEmojiPickerOpen] = useState(false);
  const hasContent = value.trim().length > 0 || hasMedia;

  const handleEmojiSelect = (emojiData: any) => {
    onChange(value + emojiData.emoji);
  };

  const pan = useRef(new Animated.ValueXY()).current;
  const isCanceledRef = useRef(false);

  const panResponder = useRef(
    PanResponder.create({
      onStartShouldSetPanResponder: () => true,
      onPanResponderGrant: () => {
        isCanceledRef.current = false;
        pan.setValue({ x: 0, y: 0 });
        if (onStartRecording) {
          onStartRecording();
        }
      },
      onPanResponderMove: (e, gestureState) => {
        if (gestureState.dx < 0) {
           pan.setValue({ x: gestureState.dx, y: 0 });
        }
        
        if (gestureState.dx < -100 && !isCanceledRef.current) {
          isCanceledRef.current = true;
          if (onCancelRecording) onCancelRecording();
          resetMicPos();
        }
      },
      onPanResponderRelease: (e, gestureState) => {
        if (!isCanceledRef.current) {
          if (onSendRecording) onSendRecording();
        }
        resetMicPos();
      },
      onPanResponderTerminate: () => {
        if (!isCanceledRef.current) {
          if (onCancelRecording) onCancelRecording();
        }
        resetMicPos();
      }
    })
  ).current;

  const resetMicPos = () => {
    Animated.spring(pan, {
      toValue: { x: 0, y: 0 },
      useNativeDriver: false,
    }).start();
  };

  const formatTime = (seconds: number) => {
    return `${Math.floor(seconds / 60)}:${(seconds % 60).toString().padStart(2, '0')}`;
  };

  return (
    <>
      <View className="bg-surface dark:bg-d-surface px-3 py-2.5 flex-row items-end gap-2 overflow-hidden">
        {isRecording ? (
          // Recording State UI
          <View className="flex-1 flex-row items-center justify-between pl-2 h-[42px]">
            <View className="flex-row items-center gap-2">
              <View className="w-2.5 h-2.5 rounded-full bg-danger dark:bg-d-danger animate-pulse" />
              <Text className="text-ink dark:text-d-ink font-mono text-base font-medium">
                {formatTime(recordingSeconds)}
              </Text>
            </View>
            
            <View className="flex-1 items-end pr-8">
              <Text className="text-muted dark:text-d-muted text-sm">« Slide to cancel</Text>
            </View>
          </View>
        ) : (
          // Normal State UI
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

        {hasContent && !isRecording ? (
          <Pressable
            onPress={onSend}
            className="w-[42px] h-[42px] rounded-full bg-accent dark:bg-d-accent items-center justify-center active:opacity-85 mb-0.5"
          >
            <Send size={18} color={tokens.accentInk} strokeWidth={2} />
          </Pressable>
        ) : (
          <Animated.View style={{ transform: [{ translateX: pan.x }] }} {...panResponder.panHandlers}>
            <View className={`w-[42px] h-[42px] rounded-full items-center justify-center ${isRecording ? 'bg-accent dark:bg-d-accent scale-110' : ''} mb-0.5`}>
               <Mic size={isRecording ? 24 : 22} color={isRecording ? tokens.accentInk : tokens.accent} strokeWidth={isRecording ? 2 : 1.5} />
            </View>
          </Animated.View>
        )}
      </View>

      <EmojiPicker
        open={isEmojiPickerOpen}
        onClose={() => setIsEmojiPickerOpen(false)}
        onEmojiSelected={handleEmojiSelect}
      />
    </>
  );
}

