// src/components/Bubble.tsx — chat bubble (in or out) with media support.
// Borderless, single-fill. Tail style by default to match WhatsApp affordance.

import React from 'react';
import { View, Text, Image, Pressable } from 'react-native';
import { Check, CheckCheck, Star, Play, Pause, FileText, Mic, Clock, AlertCircle } from 'lucide-react-native';
import { Audio } from 'expo-av';
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
  metadata?: any;
  replyToContent?: string | null;
  onMediaPress?: () => void;
}

export function Bubble({ kind, children, time, status, variant = 'tail', radius = 18, isStarred, mediaUrl, mediaType, metadata, replyToContent, onMediaPress }: Props) {
  const { tokens } = useTokens();
  const isOut = kind === 'out';
  const baseR = variant === 'squared' ? Math.min(8, radius * 0.45) : radius;

  const [isPlaying, setIsPlaying] = React.useState(false);
  const soundRef = React.useRef<Audio.Sound | null>(null);

  React.useEffect(() => {
    return () => {
      if (soundRef.current) {
        soundRef.current.unloadAsync();
      }
    };
  }, []);

  const handleAudioPress = async () => {
    if (isPlaying) {
      if (soundRef.current) {
        await soundRef.current.pauseAsync();
        setIsPlaying(false);
      }
      return;
    }

    try {
      if (!soundRef.current && mediaUrl) {
        const { sound } = await Audio.Sound.createAsync(
          { uri: mediaUrl },
          { shouldPlay: true }
        );
        soundRef.current = sound;
        sound.setOnPlaybackStatusUpdate((status: any) => {
          if (status.isLoaded) {
            setIsPlaying(status.isPlaying);
            if (status.didJustFinish) {
              soundRef.current?.setPositionAsync(0);
              setIsPlaying(false);
            }
          }
        });
      } else if (soundRef.current) {
        await soundRef.current.playAsync();
      }
      setIsPlaying(true);
    } catch (e) {
      console.warn("Error playing audio", e);
    }
  };

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

  let reactionEmoji = null;
  if (metadata?.reaction && typeof metadata.reaction === 'string' && metadata.reaction.trim() !== '') {
    reactionEmoji = metadata.reaction.trim();
  } else if (metadata?.reactions && typeof metadata.reactions === 'object') {
    const keys = Object.keys(metadata.reactions);
    if (keys.length > 0) {
      const firstReaction = metadata.reactions[keys[0]];
      if (typeof firstReaction === 'string' && firstReaction.trim() !== '') {
        reactionEmoji = firstReaction.trim();
      }
    }
  }

  return (
    <View className={`max-w-[78%] ${isOut ? 'self-end' : 'self-start'} ${reactionEmoji ? 'mb-3' : ''} relative`}>
      <View
        className={`overflow-hidden ${isOut ? 'bg-bubble-out dark:bg-d-bubble-out' : 'bg-bubble-in dark:bg-d-bubble-in'}`}
        style={corner}
      >
        {/* Reply Context */}
        {replyToContent ? (
          <View className="m-1.5 p-2 bg-black/5 dark:bg-white/10 rounded border-l-4 border-l-accent dark:border-l-d-accent">
            <Text className="text-accent dark:text-d-accent text-xs font-semibold mb-0.5">Replied Message</Text>
            <Text className="text-ink/80 dark:text-d-ink/80 text-xs" numberOfLines={3}>
              {replyToContent}
            </Text>
          </View>
        ) : null}

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
          <Pressable onPress={handleAudioPress} className="flex-row items-center gap-3 px-3 py-3">
            <View className="w-9 h-9 rounded-full items-center justify-center" style={{ backgroundColor: tokens.accent + '30' }}>
              {isPlaying ? (
                <Pause size={18} color={tokens.accent} fill={tokens.accent} />
              ) : (
                <Play size={18} color={tokens.accent} fill={tokens.accent} />
              )}
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
            status === 'read' ? (
              <CheckCheck size={15} color="#34B7F1" strokeWidth={2} />
            ) : status === 'delivered' ? (
              <CheckCheck size={15} color={tokens.muted} strokeWidth={2} />
            ) : status === 'sent' ? (
              <Check size={15} color={tokens.muted} strokeWidth={2} />
            ) : status === 'failed' ? (
              <AlertCircle size={13} color={tokens.danger} strokeWidth={2} />
            ) : (
              <Clock size={12} color={tokens.muted} strokeWidth={2} />
            )
          ) : null}
        </View>
      </View>

      {reactionEmoji ? (
        <View className={`absolute -bottom-3 ${isOut ? 'right-2' : 'right-2'} bg-surface dark:bg-d-surface rounded-full px-1.5 py-0.5 border border-hairline dark:border-d-hairline z-10`}>
          <Text style={{ fontSize: 13, lineHeight: 16 }}>{reactionEmoji}</Text>
        </View>
      ) : null}
    </View>
  );
}
