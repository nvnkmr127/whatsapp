// src/components/Bubble.tsx — chat bubble (in or out) with WhatsApp native media previews.
import React from 'react';
import { View, Text, Image, Pressable, ActivityIndicator } from 'react-native';
import { Star, Play, Pause, FileText, Mic, Download, Camera, Image as ImageIcon, Video as VideoIcon } from 'lucide-react-native';
import { Audio } from 'expo-av';
import { useTokens } from '@/theme';
import { ReplyPreview } from './ReplyPreview';
import { VideoThumbnailPreview } from './VideoThumbnailPreview';
import { WhatsAppStatusTick } from './WhatsAppStatusTick';
import type { MessageStatus, ChatMessage } from '@/types';
import { safeExtractText } from '@/utils/text';
import { resolveMediaUrl, detectMediaType } from '@/utils/media';

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
  replyTo?: ChatMessage | null;
  contactName?: string;
  onReplyPress?: () => void;
  onMediaPress?: () => void;
}

// Simulated waveform heights for native WhatsApp voice note look
const VOICE_WAVEFORM_BARS = [6, 12, 18, 10, 14, 22, 16, 8, 14, 20, 12, 18, 24, 16, 10, 14, 8, 12];

export function Bubble({
  kind,
  children,
  time,
  status,
  variant = 'tail',
  radius = 18,
  isStarred,
  mediaUrl,
  mediaType,
  metadata,
  replyTo,
  contactName,
  onReplyPress,
  onMediaPress,
}: Props) {
  const { tokens } = useTokens();
  const isOut = kind === 'out';
  const baseR = variant === 'squared' ? Math.min(8, radius * 0.45) : radius;

  const [isPlaying, setIsPlaying] = React.useState(false);
  const [playbackPosition, setPlaybackPosition] = React.useState(0);
  const [duration, setDuration] = React.useState(0);
  const soundRef = React.useRef<Audio.Sound | null>(null);

  const resolvedMediaUrl = resolveMediaUrl(mediaUrl);

  React.useEffect(() => {
    return () => {
      if (soundRef.current) {
        soundRef.current.unloadAsync().catch(() => {});
      }
    };
  }, []);

  const handleAudioPress = async () => {
    if (isPlaying) {
      if (soundRef.current) {
        try {
          await soundRef.current.pauseAsync();
        } catch (e) {}
        setIsPlaying(false);
      }
      return;
    }

    try {
      if (!soundRef.current && resolvedMediaUrl) {
        const { sound } = await Audio.Sound.createAsync(
          { uri: resolvedMediaUrl },
          { shouldPlay: true }
        );
        soundRef.current = sound;
        sound.setOnPlaybackStatusUpdate((status: any) => {
          if (status && status.isLoaded) {
            setIsPlaying(status.isPlaying);
            if (status.positionMillis) setPlaybackPosition(status.positionMillis);
            if (status.durationMillis) setDuration(status.durationMillis);
            if (status.didJustFinish) {
              soundRef.current?.setPositionAsync(0).catch(() => {});
              setIsPlaying(false);
              setPlaybackPosition(0);
            }
          }
        });
      } else if (soundRef.current) {
        await soundRef.current.playAsync();
      }
      setIsPlaying(true);
    } catch (e) {
      console.warn('Error playing audio', e);
      setIsPlaying(false);
    }
  };

  const corner = variant === 'tail'
    ? (isOut
        ? { borderTopLeftRadius: baseR, borderTopRightRadius: baseR, borderBottomLeftRadius: baseR, borderBottomRightRadius: 4 }
        : { borderTopLeftRadius: baseR, borderTopRightRadius: baseR, borderBottomLeftRadius: 4, borderBottomRightRadius: baseR })
    : { borderRadius: baseR };

  const mediaCategory = (mediaType || resolvedMediaUrl || mediaUrl)
    ? detectMediaType(mediaType, resolvedMediaUrl || mediaUrl)
    : null;

  const isVideo = mediaCategory === 'video';
  const isImage = mediaCategory === 'image';
  const isAudio = mediaCategory === 'audio';
  const isDoc   = mediaCategory === 'document';
  const hasMedia = !!mediaCategory || !!resolvedMediaUrl || !!mediaUrl;

  const childText = safeExtractText(children).trim();

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

  // Parse document file details
  const getDocumentInfo = () => {
    if (!resolvedMediaUrl && !mediaUrl) return { name: 'Document', ext: 'FILE', size: 'Document' };
    const cleanUrl = (resolvedMediaUrl || mediaUrl || '').split('?')[0];
    const name = cleanUrl.split('/').pop() || 'Document';
    const ext = name.includes('.') ? name.split('.').pop()?.toUpperCase() || 'FILE' : 'FILE';
    const size = metadata?.file_size ? `${(metadata.file_size / (1024 * 1024)).toFixed(1)} MB` : `${ext} Document`;
    return { name, ext, size };
  };

  const docInfo = getDocumentInfo();

  // Helper for Status Checkmarks (Native WhatsApp SVGs)
  const renderStatusIcon = (colorOverride?: string) => {
    if (!isOut) return null;
    return <WhatsAppStatusTick status={status || 'sent'} size={16} colorOverride={colorOverride} />;
  };

  // Format audio seconds display
  const formatTime = (millis: number) => {
    const totalSec = Math.floor(millis / 1000);
    const m = Math.floor(totalSec / 60);
    const s = totalSec % 60;
    return `${m}:${s < 10 ? '0' : ''}${s}`;
  };

  // Extract duration from message text if available (e.g. "Voice message (0:02)")
  const textDurationMatch = childText ? childText.match(/\((\d+:\d+)\)/) : null;
  const textDurationStr = textDurationMatch ? textDurationMatch[1] : null;

  const audioTimeStr = isPlaying || playbackPosition > 0
    ? formatTime(playbackPosition)
    : (duration > 0 ? formatTime(duration) : (textDurationStr || '0:00'));

  return (
    <View className={`max-w-[85%] ${replyTo ? 'min-w-[190px]' : ''} ${isOut ? 'self-end' : 'self-start'} ${reactionEmoji ? 'mb-3' : ''} relative`}>
      <View
        className={`overflow-hidden ${isOut ? 'bg-bubble-out dark:bg-d-bubble-out' : 'bg-bubble-in dark:bg-d-bubble-in'}`}
        style={corner}
      >
        {/* Reply Context */}
        {replyTo ? (
          <ReplyPreview message={replyTo} contactName={contactName} inBubble={true} onPress={onReplyPress} />
        ) : null}

        {/* ── Native WhatsApp Video Preview ── */}
        {hasMedia && isVideo && (
          <View className="relative">
            <Pressable onPress={onMediaPress} className="relative overflow-hidden rounded-t-lg active:opacity-90">
              <VideoThumbnailPreview
                videoUri={resolvedMediaUrl || mediaUrl || ''}
                thumbnailUrl={metadata?.thumbnail_url || metadata?.poster_url}
                width={260}
                height={190}
                duration={metadata?.duration || metadata?.video_length}
                showPlayButton={true}
                playButtonSize="medium"
              >
                {/* Floating Timestamp when NO caption is present */}
                {!childText && (
                  <View className="absolute bottom-1.5 right-2 bg-black/60 px-2 py-0.5 rounded-full flex-row items-center gap-1">
                    {isStarred && <Star size={9} color="#EAB308" fill="#EAB308" style={{ marginRight: 1 }} />}
                    {time && <Text className="text-white text-[10px] font-medium">{time}</Text>}
                    {renderStatusIcon('#ffffff')}
                  </View>
                )}
              </VideoThumbnailPreview>
            </Pressable>
          </View>
        )}

        {/* ── Native WhatsApp Image Preview ── */}
        {hasMedia && isImage && (
          <View className="relative">
            <Pressable onPress={onMediaPress} className="relative overflow-hidden rounded-t-lg active:opacity-90">
              {resolvedMediaUrl ? (
                <View style={{ width: 260, height: 190 }} className="relative bg-black/5 dark:bg-white/5 items-center justify-center">
                  <Image
                    source={{ uri: resolvedMediaUrl }}
                    style={{ width: 260, height: 190 }}
                    resizeMode="cover"
                  />
                </View>
              ) : (
                <View style={{ width: 260, height: 160 }} className="bg-black/10 dark:bg-white/10 items-center justify-center p-4">
                  <ImageIcon size={36} color={tokens.muted} />
                  <Text className="text-muted dark:text-d-muted text-xs font-medium mt-1.5">
                    Photo
                  </Text>
                  <View className="flex-row items-center gap-1 mt-2 px-3 py-1 bg-surface dark:bg-d-surface rounded-full border border-hairline dark:border-d-hairline shadow-sm">
                    <Download size={13} color={tokens.accent} />
                    <Text className="text-[11.5px] font-semibold text-accent dark:text-d-accent">Tap to view / download</Text>
                  </View>
                </View>
              )}

              {/* Floating Timestamp when NO caption is present */}
              {!childText && (
                <View className="absolute bottom-1.5 right-2 bg-black/50 px-2 py-0.5 rounded-full flex-row items-center gap-1">
                  {isStarred && <Star size={9} color="#EAB308" fill="#EAB308" style={{ marginRight: 1 }} />}
                  {time && <Text className="text-white text-[10px] font-medium">{time}</Text>}
                  {renderStatusIcon('#ffffff')}
                </View>
              )}
            </Pressable>
          </View>
        )}

        {/* ── Native WhatsApp Voice Note (Audio) Preview ── */}
        {hasMedia && isAudio && (
          <View className="p-2.5 min-w-[230px]">
            <View className="flex-row items-center gap-3">
              {/* Play / Pause Circular Button */}
              <Pressable
                onPress={handleAudioPress}
                className="w-11 h-11 rounded-full items-center justify-center shadow-sm"
                style={{ backgroundColor: tokens.accent }}
              >
                {isPlaying ? (
                  <Pause size={20} color="#FFFFFF" fill="#FFFFFF" />
                ) : (
                  <Play size={20} color="#FFFFFF" fill="#FFFFFF" style={{ marginLeft: 2 }} />
                )}
              </Pressable>

              {/* Waveform Visualizer & Timer */}
              <View className="flex-1 min-w-0 justify-center">
                {/* Audio Waveform Bars */}
                <View className="flex-row items-center gap-[2.5px] h-7 mb-1 pr-1">
                  {VOICE_WAVEFORM_BARS.map((h, i) => {
                    const progress = duration > 0 ? playbackPosition / duration : 0;
                    const barProgress = i / VOICE_WAVEFORM_BARS.length;
                    const isPlayed = isPlaying && barProgress <= progress;
                    return (
                      <View
                        key={i}
                        className="w-[3px] rounded-full"
                        style={{
                          height: h,
                          backgroundColor: isPlayed ? tokens.accent : (isOut ? '#94A3B8' : '#CBD5E1'),
                        }}
                      />
                    );
                  })}
                </View>

                {/* Duration & Mic indicator + Timestamp & Status */}
                <View className="flex-row items-center justify-between mt-1">
                  <View className="flex-row items-center gap-1">
                    <Mic size={12} color={tokens.accent} />
                    <Text className="text-muted dark:text-d-muted text-[11px] font-medium">{audioTimeStr}</Text>
                  </View>
                  <View className="flex-row items-center gap-1">
                    {isStarred ? <Star size={9} color="#EAB308" fill="#EAB308" style={{ marginRight: 1 }} /> : null}
                    {time ? <Text className="text-muted dark:text-d-muted text-[10px] font-medium">{time}</Text> : null}
                    {renderStatusIcon()}
                  </View>
                </View>
              </View>
            </View>
          </View>
        )}

        {/* ── Native WhatsApp Document Preview ── */}
        {hasMedia && isDoc && (
          <Pressable onPress={onMediaPress} className="p-2.5 min-w-[220px]">
            <View className="bg-black/5 dark:bg-white/10 rounded-lg p-3 flex-row items-center gap-3 border border-hairline dark:border-d-hairline">
              <View className="w-10 h-11 rounded-md bg-blue-500/20 items-center justify-center border border-blue-500/30">
                <FileText size={22} color="#3b82f6" />
                <Text className="text-[8px] font-extrabold text-blue-600 dark:text-blue-400 mt-0.5 uppercase tracking-tighter">
                  {docInfo.ext.slice(0, 4)}
                </Text>
              </View>
              <View className="flex-1 min-w-0">
                <Text className="text-ink dark:text-d-ink text-[13.5px] font-semibold" numberOfLines={1}>
                  {docInfo.name}
                </Text>
                <Text className="text-muted dark:text-d-muted text-[11px] font-medium mt-0.5">
                  {docInfo.size}
                </Text>
              </View>
              <View className="w-8 h-8 rounded-full bg-surface2 dark:bg-d-surface2 items-center justify-center">
                <Download size={16} color={tokens.ink} />
              </View>
            </View>
          </Pressable>
        )}

        {/* ── Text caption / body (hidden for voice note placeholders) ── */}
        {childText && (!isAudio || (!childText.startsWith('Voice message') && !childText.startsWith('🎙️ Voice message') && !childText.startsWith('Voice note'))) ? (
          <View className="px-3 pt-2 pb-1.5">
            <Text className="text-ink dark:text-d-ink text-[14.5px] leading-5 font-normal">{childText}</Text>
            <View className="flex-row items-center justify-end gap-1 mt-0.5 self-end">
              {isStarred ? <Star size={9} color="#EAB308" fill="#EAB308" style={{ marginRight: 1 }} /> : null}
              {time ? <Text className="text-muted dark:text-d-muted text-[10.5px] font-medium leading-none">{time}</Text> : null}
              {renderStatusIcon()}
            </View>
          </View>
        ) : (!hasMedia && childText) ? (
          <View className="px-3 pt-2 pb-1.5">
            <Text className="text-ink dark:text-d-ink text-[14.5px] leading-5 font-normal">{childText}</Text>
            <View className="flex-row items-center justify-end gap-1 mt-0.5 self-end">
              {isStarred ? <Star size={9} color="#EAB308" fill="#EAB308" style={{ marginRight: 1 }} /> : null}
              {time ? <Text className="text-muted dark:text-d-muted text-[10.5px] font-medium leading-none">{time}</Text> : null}
              {renderStatusIcon()}
            </View>
          </View>
        ) : null}

        {/* ── Document timestamp row (when document is rendered without child text) ── */}
        {hasMedia && isDoc && !childText && (
          <View className="flex-row items-center justify-end gap-1 px-3 pb-1.5 -mt-1">
            {isStarred ? <Star size={9} color="#EAB308" fill="#EAB308" style={{ marginRight: 1 }} /> : null}
            {time ? <Text className="text-muted dark:text-d-muted text-[10.5px] font-medium leading-none">{time}</Text> : null}
            {renderStatusIcon()}
          </View>
        )}
      </View>

      {/* Reaction Emoji Badge */}
      {reactionEmoji ? (
        <View className={`absolute -bottom-3 ${isOut ? 'right-2' : 'right-2'} bg-surface dark:bg-d-surface rounded-full px-1.5 py-0.5 border border-hairline dark:border-d-hairline z-10 shadow-sm`}>
          <Text style={{ fontSize: 13, lineHeight: 16 }}>{reactionEmoji}</Text>
        </View>
      ) : null}
    </View>
  );
}
