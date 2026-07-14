// src/components/PhoneBubbleBar.tsx — keyboard-aware bottom composer used by Chat.

import React, { useState, useRef, useEffect } from 'react';
import { View, TextInput, Pressable, Keyboard, PanResponder, Animated, Text } from 'react-native';
import { Paperclip, Smile, Send, Mic } from 'lucide-react-native';
import { useTokens } from '@/theme';
import { IconButton } from './Button';
import EmojiPicker from 'rn-emoji-keyboard';

interface Props {
  value: string;
  onChange: (s: string) => void;
  onSend: () => void;
  onAttach?: () => void;
  hasMedia?: boolean;
  
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
        // Only allow moving left (negative dx)
        if (gestureState.dx < 0) {
           pan.setValue({ x: gestureState.dx, y: 0 });
        }
        
        // If they swipe left past -100, trigger cancel early
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
      <View className="bg-surface dark:bg-d-surface px-3 py-2.5 flex-row items-center gap-2 overflow-hidden">
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
            <IconButton icon={Paperclip} color={tokens.muted} onPress={onAttach} />
            <View className="flex-1 flex-row items-center bg-surface2 dark:bg-d-surface2 rounded-full px-3.5 py-2 gap-1.5 h-[42px]">
              <TextInput
                value={value}
                onChangeText={onChange}
                placeholder="Message"
                placeholderTextColor={tokens.muted}
                className="flex-1 text-ink dark:text-d-ink text-[14px] p-0 h-full"
                onSubmitEditing={onSend}
                returnKeyType="send"
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
          </>
        )}

        {hasContent && !isRecording ? (
          <Pressable
            onPress={onSend}
            className="w-[42px] h-[42px] rounded-full bg-accent dark:bg-d-accent items-center justify-center active:opacity-85"
          >
            <Send size={18} color={tokens.accentInk} strokeWidth={2} />
          </Pressable>
        ) : (
          <Animated.View style={{ transform: [{ translateX: pan.x }] }} {...panResponder.panHandlers}>
            <View className={`w-[42px] h-[42px] rounded-full items-center justify-center ${isRecording ? 'bg-accent dark:bg-d-accent scale-110' : ''}`}>
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

