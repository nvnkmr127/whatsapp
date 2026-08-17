import React from 'react';
import { View, Text, Image, Pressable } from 'react-native';
import { Camera, Video, FileText, Mic, X } from 'lucide-react-native';
import { useTokens } from '@/theme';
import { VideoThumbnailPreview } from './VideoThumbnailPreview';
import type { ChatMessage } from '@/types';
import { safeExtractText } from '@/utils/text';
import { resolveMediaUrl, detectMediaType } from '@/utils/media';

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

  const resolvedMediaUrl = resolveMediaUrl(message.media_url);
  const mediaCategory = (message.media_type || resolvedMediaUrl)
    ? detectMediaType(message.media_type, resolvedMediaUrl)
    : null;

  const isVideo = mediaCategory === 'video';
  const isImage = mediaCategory === 'image';
  const isAudio = mediaCategory === 'audio';
  const isDoc   = mediaCategory === 'document';
  const hasMedia = !!mediaCategory || !!resolvedMediaUrl;

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

  if (inBubble) {
    return (
      <Pressable 
        onPress={onPress} 
        className="mx-1.5 mt-1.5 mb-1 rounded-lg overflow-hidden flex-row items-stretch bg-black/5 dark:bg-white/10"
        disabled={!onPress}
      >
        {/* Dedicated Left Accent Bar (avoids RN Android borderLeftWidth + borderRadius drawing glitch) */}
        <View style={{ width: 4, backgroundColor: nameColor }} />

        {/* Content Section */}
        <View className="flex-1 px-2.5 py-1.5 justify-center min-w-[120px]">
          <Text className="font-bold text-[12.5px] leading-[16px] mb-0.5" style={{ color: nameColor }} numberOfLines={1}>
            {displayName}
          </Text>

          <View className="flex-row items-center gap-1.5">
            {hasMedia && isImage && <Camera size={13} color={tokens.muted} />}
            {hasMedia && isVideo && <Video size={13} color={tokens.muted} />}
            {hasMedia && isAudio && <Mic size={13} color={tokens.muted} />}
            {hasMedia && isDoc && <FileText size={13} color={tokens.muted} />}
            <Text 
              className="text-ink/80 dark:text-d-ink/80 text-[12px] leading-[16px] flex-1" 
              numberOfLines={2}
            >
              {previewText}
            </Text>
          </View>
        </View>

        {/* Optional Right Thumbnail */}
        {hasMedia && resolvedMediaUrl && (
          <View className="p-1 justify-center">
            {isVideo ? (
              <VideoThumbnailPreview
                videoUri={resolvedMediaUrl}
                width={42}
                height={42}
                borderRadius={6}
                showPlayButton={true}
                playButtonSize="small"
                showDurationBadge={false}
              />
            ) : isImage ? (
              <Image 
                source={{ uri: resolvedMediaUrl }} 
                style={{ width: 42, height: 42, borderRadius: 6 }} 
                resizeMode="cover" 
              />
            ) : null}
          </View>
        )}
      </Pressable>
    );
  }

  // Outside bubble (Composer reply banner above input bar)
  return (
    <View 
      className="bg-surface2 dark:bg-d-surface2 mx-2 mt-2 mb-1 rounded-lg overflow-hidden flex-row items-stretch border border-hairline dark:border-d-hairline"
    >
      {/* Dedicated Left Accent Bar */}
      <View style={{ width: 4, backgroundColor: nameColor }} />

      <View className="flex-1 px-3 py-2 flex-row items-center justify-between">
        <View className="flex-1 mr-2">
          <View className="flex-row items-center justify-between mb-0.5">
            <Text className="font-bold text-[12.5px] leading-4" style={{ color: nameColor }} numberOfLines={1}>
              Replying to {displayName}
            </Text>
          </View>

          <View className="flex-row items-center gap-1.5">
            {hasMedia && isImage && <Camera size={13} color={tokens.muted} />}
            {hasMedia && isVideo && <Video size={13} color={tokens.muted} />}
            {hasMedia && isAudio && <Mic size={13} color={tokens.muted} />}
            {hasMedia && isDoc && <FileText size={13} color={tokens.muted} />}
            <Text 
              className="text-ink/80 dark:text-d-ink/80 text-[12.5px] leading-4 flex-1" 
              numberOfLines={1}
            >
              {previewText}
            </Text>
          </View>
        </View>

        {hasMedia && resolvedMediaUrl && (
          <View className="justify-center mr-2">
            {isVideo ? (
              <VideoThumbnailPreview
                videoUri={resolvedMediaUrl}
                width={40}
                height={40}
                borderRadius={6}
                showPlayButton={true}
                playButtonSize="small"
                showDurationBadge={false}
              />
            ) : isImage ? (
              <Image 
                source={{ uri: resolvedMediaUrl }} 
                style={{ width: 40, height: 40, borderRadius: 6 }} 
                resizeMode="cover" 
              />
            ) : null}
          </View>
        )}

        {onCancel && (
          <Pressable onPress={onCancel} className="p-1.5 -mr-1 rounded-full active:bg-black/5 dark:active:bg-white/10" hitSlop={12}>
            <X size={16} color={tokens.muted} />
          </Pressable>
        )}
      </View>
    </View>
  );
}
