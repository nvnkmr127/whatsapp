// src/components/Bubble.tsx — chat bubble (in or out) with media support.
// Borderless, single-fill. Tail style by default to match WhatsApp affordance.

import React from 'react';
import { View, Text, Image, Pressable } from 'react-native';
import { Check, CheckCheck, Star, Play, FileText, Mic } from 'lucide-react-native';
import { useTokens } from '@/theme';
import type { MessageStatus } from '@/types';

interface Props {
  kind: 'in' | 'out';
  children?: React.ReactNode;
  time?: string;
  status?: MessageStatus;
  variant?: 'rounded' | 'squared' | 'tail';
  radius?: number;
  isStarred?: boolean;
  mediaUrl?: string | null;
  mediaType?: string | null;
  onMediaPress?: () => void;
}

export function Bubble({ kind, children, time, status, variant = 'tail', radius = 18, isStarred, mediaUrl, mediaType, onMediaPress }: Props) {
  const { tokens } = useTokens();
  const isOut = kind === 'out';
  const baseR = variant === 'squared' ? Math.min(8, radius * 0.45) : radius;

  const corner = variant === 'tail'
    ? (isOut
        ? { borderTopLeftRadius: baseR, borderTopRightRadius: baseR, borderBottomLeftRadius: baseR, borderBottomRightRadius: 4 }
        : { borderTopLeftRadius: baseR, borderTopRightRadius: baseR, borderBottomLeftRadius: 4, borderBottomRightRadius: baseR })
    : { borderRadius: baseR };

  const hasMedia = !!mediaUrl && !!mediaType;
  const isImage = mediaType === 'image';
  const isVideo = mediaType === 'video';
  const isAudio = mediaType === 'audio';
  const isDoc   = mediaType === 'document';

  const childText = children ? String(children).trim() : '';

  return (
    <View
      className={`max-w-[78%] overflow-hidden ${isOut ? 'self-end bg-bubble-out dark:bg-d-bubble-out' : 'self-start bg-bubble-in dark:bg-d-bubble-in'}`}
      style={corner}
    >
      {/* Media preview */}
      {hasMedia && (isImage || isVideo) && (
        <Pressable onPress={onMediaPress} className="relative">
          <Image
            source={{ uri: mediaUrl! }}
            style={{ width: 220, height: 160 }}
            resizeMode="cover"
          />
          {isVideo && (
            <View className="absolute inset-0 items-center justify-center">
              <View className="w-12 h-12 rounded-full bg-black/60 items-center justify-center">
                <Play size={22} color="#fff" fill="#fff" />
              </View>
            </View>
          )}
        </Pressable>
      )}
      {hasMedia && isAudio && (
        <Pressable onPress={onMediaPress} className="flex-row items-center gap-3 px-3 py-3">
          <View className="w-9 h-9 rounded-full items-center justify-center" style={{ backgroundColor: tokens.accent + '30' }}>
            <Mic size={18} color={tokens.accent} />
          </View>
          <Text className="text-ink dark:text-d-ink text-sm font-medium">Voice message</Text>
        </Pressable>
      )}
      {hasMedia && isDoc && (
        <Pressable onPress={onMediaPress} className="flex-row items-center gap-3 px-3 py-3">
          <View className="w-9 h-9 rounded-xl items-center justify-center bg-blue-500/20">
            <FileText size={18} color="#3b82f6" />
          </View>
          <Text className="text-ink dark:text-d-ink text-sm font-medium flex-1" numberOfLines={1}>
            {(mediaUrl!).split('/').pop() || 'Document'}
          </Text>
        </Pressable>
      )}

      {/* Text caption / body */}
      {childText ? (
        <View className="py-2 px-3">
          <Text className="text-ink dark:text-d-ink text-[14px] leading-5 font-normal">{children}</Text>
        </View>
      ) : !hasMedia ? (
        <View className="py-2 px-3">
          <Text className="text-ink dark:text-d-ink text-[14px] leading-5 font-normal">{children}</Text>
        </View>
      ) : null}

      {/* Timestamp row */}
      <View className={`flex-row items-center justify-end gap-1 ${hasMedia ? 'px-2 pb-1.5' : 'px-3 pb-2'}`}>
        {isStarred ? <Star size={10} color="#EAB308" fill="#EAB308" style={{ marginRight: 2 }} /> : null}
        {time ? <Text className="text-muted dark:text-d-muted text-[10.5px] font-medium">{time}</Text> : null}
        {isOut && status ? (
          status === 'read'
            ? <CheckCheck size={13} color={tokens.accent} strokeWidth={2} />
            : <Check size={13} color={tokens.muted} strokeWidth={2} />
        ) : null}
      </View>
    </View>
  );
}
