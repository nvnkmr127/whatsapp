// src/components/PhoneBubbleBar.tsx — keyboard-aware bottom composer used by Chat.

import React, { useState } from 'react';
import { View, TextInput, Pressable, Keyboard } from 'react-native';
import { Paperclip, Smile, Send, Mic } from 'lucide-react-native';
import { useTokens } from '@/theme';
import { IconButton } from './Button';
import EmojiPicker from 'rn-emoji-keyboard';

interface Props {
  value: string;
  onChange: (s: string) => void;
  onSend: () => void;
  onAttach?: () => void;
  onVoice?: () => void;
  hasMedia?: boolean;
}

export function Composer({ value, onChange, onSend, onAttach, onVoice, hasMedia }: Props) {
  const { tokens } = useTokens();
  const [isEmojiPickerOpen, setIsEmojiPickerOpen] = useState(false);
  const hasContent = value.trim().length > 0 || hasMedia;

  const handleEmojiSelect = (emojiData: any) => {
    onChange(value + emojiData.emoji);
  };

  return (
    <>
      <View className="bg-surface dark:bg-d-surface px-3 py-2.5 flex-row items-center gap-2">
        <IconButton icon={Paperclip} color={tokens.muted} onPress={onAttach} />
        <View className="flex-1 flex-row items-center bg-surface2 dark:bg-d-surface2 rounded-full px-3.5 py-2 gap-1.5">
          <TextInput
            value={value}
            onChangeText={onChange}
            placeholder="Message"
            placeholderTextColor={tokens.muted}
            className="flex-1 text-ink dark:text-d-ink text-[14px] p-0"
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
        {hasContent ? (
          <Pressable
            onPress={onSend}
            className="w-[42px] h-[42px] rounded-full bg-accent dark:bg-d-accent items-center justify-center active:opacity-85"
          >
            <Send size={18} color={tokens.accentInk} strokeWidth={2} />
          </Pressable>
        ) : (
          <IconButton icon={Mic} size={42} color={tokens.accent} onPress={onVoice} />
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

