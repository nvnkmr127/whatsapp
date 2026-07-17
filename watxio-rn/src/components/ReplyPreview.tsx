import React from 'react';
import { View, Text, Image, Pressable } from 'react-native';
import { Camera, Video, FileText, Mic } from 'lucide-react-native';
import { useTokens } from '@/theme';
import type { ChatMessage } from '@/types';

interface ReplyPreviewProps {
  message: ChatMessage | null | undefined;
  onPress?: () => void;
  onCancel?: () => void;
  inBubble?: boolean; // Changes styling if it's inside a chat bubble vs above input
}

export function ReplyPreview({ message, onPress, onCancel, inBubble = false }: ReplyPreviewProps) {
  const { tokens } = useTokens();

  if (!message) return null;

  const isOut = message.kind === 'out';
  const senderName = isOut ? 'You' : 'Contact'; // Can be mapped to real name
  const nameColor = inBubble ? (isOut ? tokens.accent : '#34B7F1') : tokens.accent;

  const hasMedia = !!message.media_url && !!message.media_type;
  const isImage = message.media_type === 'image';
  const isVideo = message.media_type === 'video';
  const isAudio = message.media_type === 'audio';
  const isDoc = message.media_type === 'document';

  let previewText = message.text || '';
  if (!previewText && hasMedia) {
    if (isImage) previewText = 'Photo';
    if (isVideo) previewText = 'Video';
    if (isAudio) previewText = 'Voice message';
    if (isDoc) previewText = 'Document';
  }

  const containerClass = inBubble
    ? "mx-1 mt-1 mb-1 p-[4px] bg-black/5 dark:bg-white/10 rounded-lg overflow-hidden flex-row border-l-[3.5px]"
    : "bg-surface2 dark:bg-d-surface2 mx-2 mt-2 mb-1 rounded-t-lg border-l-[3.5px] flex-row items-center justify-between px-3 py-2";
  
  const borderColor = isOut ? tokens.accent : '#34B7F1';

  return (
    <Pressable 
      onPress={onPress} 
      className={containerClass}
      style={{ borderLeftColor: borderColor }}
      disabled={!onPress}
    >
      <View className={`${inBubble ? 'pl-1.5 pr-3 pb-1 min-w-[100px]' : 'flex-1 mr-2'}`}>
        {inBubble ? null : (
           <View className="flex-row items-center justify-between">
             <Text className="text-accent dark:text-d-accent font-semibold text-xs mb-0.5" style={{ color: nameColor }}>
               Replying to {senderName}
             </Text>
             {onCancel && (
                <Pressable onPress={onCancel} className="p-1" hitSlop={10}>
                  <Text className="text-danger dark:text-d-danger font-bold text-xs">✕</Text>
                </Pressable>
             )}
           </View>
        )}
        
        {inBubble && (
          <Text className="font-semibold text-[12.5px] leading-[16px] mb-0.5" style={{ color: nameColor }}>
            {senderName}
          </Text>
        )}

        <View className="flex-row items-center gap-1">
          {hasMedia && isImage && <Camera size={12} color={tokens.muted} />}
          {hasMedia && isVideo && <Video size={12} color={tokens.muted} />}
          {hasMedia && isAudio && <Mic size={12} color={tokens.muted} />}
          {hasMedia && isDoc && <FileText size={12} color={tokens.muted} />}
          <Text className={`${inBubble ? 'text-ink/60 dark:text-d-ink/60 text-[12.5px] leading-[16px]' : 'text-ink/80 dark:text-d-ink/80 text-[12px]'}`} numberOfLines={inBubble ? 3 : 1}>
            {previewText}
          </Text>
        </View>
      </View>

      {hasMedia && (isImage || isVideo) && message.media_url && (
        <View className={inBubble ? 'ml-1' : ''}>
          <Image 
            source={{ uri: message.media_url }} 
            style={{ width: inBubble ? 42 : 45, height: inBubble ? 42 : 45, borderRadius: 4 }} 
            resizeMode="cover" 
          />
        </View>
      )}
    </Pressable>
  );
}
