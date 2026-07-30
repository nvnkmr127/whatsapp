import React from 'react';
import { View, Text, Image, Pressable } from 'react-native';
import { Camera, Video, FileText, Mic, X } from 'lucide-react-native';
import { useTokens } from '@/theme';
import type { ChatMessage } from '@/types';
import { safeExtractText } from '@/utils/text';

interface ReplyPreviewProps {
  message: ChatMessage | null | undefined;
  contactName?: string;
  onPress?: () => void;
  onCancel?: () => void;
  inBubble?: boolean; // Changes styling if it's inside a chat bubble vs above input
}

export function ReplyPreview({ message, contactName, onPress, onCancel, inBubble = false }: ReplyPreviewProps) {
  const { tokens } = useTokens();

  if (!message) return null;

  const isOut = message.kind === 'out';
  const displayName = isOut ? 'You' : (contactName || 'Contact');
  const nameColor = isOut ? (tokens.accent || '#06CF9C') : '#34B7F1';

  const hasMedia = !!message.media_url && !!message.media_type;
  const isImage = message.media_type === 'image';
  const isVideo = message.media_type === 'video';
  const isAudio = message.media_type === 'audio';
  const isDoc = message.media_type === 'document';

  let previewText = safeExtractText(message.text);
  if (!previewText && hasMedia) {
    if (isImage) previewText = 'Photo';
    else if (isVideo) previewText = 'Video';
    else if (isAudio) previewText = 'Voice message';
    else if (isDoc) previewText = 'Document';
    else previewText = 'Media';
  } else if (!previewText) {
    previewText = 'Message';
  }

  const containerClass = inBubble
    ? "mx-1 mt-1 mb-1 p-1.5 bg-black/5 dark:bg-white/10 rounded-lg overflow-hidden flex-row border-l-[4px]"
    : "bg-surface2 dark:bg-d-surface2 mx-2 mt-2 mb-1 rounded-lg border-l-[4px] flex-row items-center justify-between px-3 py-2 border-hairline dark:border-d-hairline";
  
  const borderColor = nameColor;

  return (
    <Pressable 
      onPress={onPress} 
      className={containerClass}
      style={{ borderLeftColor: borderColor }}
      disabled={!onPress}
    >
      <View className={`${inBubble ? 'pl-1 pr-2 pb-0.5 min-w-[120px] flex-1' : 'flex-1 mr-2'}`}>
        {!inBubble && (
          <View className="flex-row items-center justify-between mb-0.5">
            <Text className="font-bold text-[12.5px] leading-4" style={{ color: nameColor }}>
              Replying to {displayName}
            </Text>
            {onCancel && (
              <Pressable onPress={onCancel} className="p-1 -mr-1" hitSlop={12}>
                <X size={15} color={tokens.muted} />
              </Pressable>
            )}
          </View>
        )}
        
        {inBubble && (
          <Text className="font-bold text-[12.5px] leading-[16px] mb-0.5" style={{ color: nameColor }}>
            {displayName}
          </Text>
        )}

        <View className="flex-row items-center gap-1.5 mt-0.5">
          {hasMedia && isImage && <Camera size={13} color={tokens.muted} />}
          {hasMedia && isVideo && <Video size={13} color={tokens.muted} />}
          {hasMedia && isAudio && <Mic size={13} color={tokens.muted} />}
          {hasMedia && isDoc && <FileText size={13} color={tokens.muted} />}
          <Text 
            className={`${inBubble ? 'text-ink/70 dark:text-d-ink/70 text-[12px] leading-[16px]' : 'text-ink/80 dark:text-d-ink/80 text-[12.5px]'}`} 
            numberOfLines={inBubble ? 2 : 1}
          >
            {previewText}
          </Text>
        </View>
      </View>

      {hasMedia && (isImage || isVideo) && message.media_url && (
        <View className={inBubble ? 'ml-1.5' : 'ml-2'}>
          <Image 
            source={{ uri: message.media_url }} 
            style={{ width: inBubble ? 44 : 46, height: inBubble ? 44 : 46, borderRadius: 6 }} 
            resizeMode="cover" 
          />
        </View>
      )}
    </Pressable>
  );
}
