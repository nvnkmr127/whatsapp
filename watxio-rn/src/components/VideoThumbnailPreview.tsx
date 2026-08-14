// src/components/VideoThumbnailPreview.tsx — Native WhatsApp style video preview & thumbnail component
import React, { useEffect, useState } from 'react';
import { View, Image, Text, ActivityIndicator, StyleSheet, StyleProp, ViewStyle, ImageStyle } from 'react-native';
import { Play, Video as VideoIcon } from 'lucide-react-native';
import { getVideoThumbnail, getCachedVideoThumbnail } from '@/utils/videoThumbnail';
import { resolveMediaUrl } from '@/utils/media';

interface VideoThumbnailPreviewProps {
  videoUri: string | null | undefined;
  thumbnailUrl?: string | null;
  width?: number | string;
  height?: number | string;
  style?: StyleProp<ViewStyle>;
  imageStyle?: StyleProp<ImageStyle>;
  resizeMode?: 'cover' | 'contain';
  borderRadius?: number;
  showPlayButton?: boolean;
  playButtonSize?: 'small' | 'medium' | 'large';
  duration?: string | number | null;
  showDurationBadge?: boolean;
  children?: React.ReactNode;
}

export function VideoThumbnailPreview({
  videoUri,
  thumbnailUrl,
  width = 260,
  height = 190,
  style,
  imageStyle,
  resizeMode = 'cover',
  borderRadius = 8,
  showPlayButton = true,
  playButtonSize = 'medium',
  duration,
  showDurationBadge = true,
  children,
}: VideoThumbnailPreviewProps) {
  const resolvedVideoUri = resolveMediaUrl(videoUri) || videoUri || '';
  const resolvedThumbnailUrl = resolveMediaUrl(thumbnailUrl) || thumbnailUrl;

  const [thumbUri, setThumbUri] = useState<string | null>(() => {
    if (resolvedThumbnailUrl) return resolvedThumbnailUrl;
    return getCachedVideoThumbnail(resolvedVideoUri);
  });
  const [loading, setLoading] = useState<boolean>(!thumbUri && !!resolvedVideoUri);

  useEffect(() => {
    let isMounted = true;

    if (resolvedThumbnailUrl) {
      setThumbUri(resolvedThumbnailUrl);
      setLoading(false);
      return;
    }

    if (!resolvedVideoUri) {
      setThumbUri(null);
      setLoading(false);
      return;
    }

    // Check sync memory cache first
    const cached = getCachedVideoThumbnail(resolvedVideoUri);
    if (cached) {
      setThumbUri(cached);
      setLoading(false);
      return;
    }

    setLoading(true);
    getVideoThumbnail(resolvedVideoUri)
      .then((generatedUri) => {
        if (isMounted) {
          if (generatedUri) {
            setThumbUri(generatedUri);
          }
          setLoading(false);
        }
      })
      .catch(() => {
        if (isMounted) {
          setLoading(false);
        }
      });

    return () => {
      isMounted = false;
    };
  }, [resolvedVideoUri, resolvedThumbnailUrl]);

  // Format duration if provided (e.g. seconds number -> "0:15", or string)
  const formatDuration = (d: string | number | null | undefined): string | null => {
    if (!d) return null;
    if (typeof d === 'number') {
      const totalSec = Math.floor(d > 1000 ? d / 1000 : d);
      const mins = Math.floor(totalSec / 60);
      const secs = totalSec % 60;
      return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
    }
    return String(d);
  };

  const formattedDuration = formatDuration(duration);

  // Play button dimensions
  const playDimensions = {
    small: { btnSize: 28, iconSize: 14, iconOffset: 1 },
    medium: { btnSize: 48, iconSize: 22, iconOffset: 2.5 },
    large: { btnSize: 56, iconSize: 26, iconOffset: 3 },
  }[playButtonSize];

  return (
    <View
      style={[
        {
          width: width as any,
          height: height as any,
          borderRadius,
          overflow: 'hidden',
          backgroundColor: '#0c1317',
          position: 'relative',
        },
        style,
      ]}
    >
      {/* Thumbnail Image */}
      {thumbUri ? (
        <Image
          source={{ uri: thumbUri }}
          style={[
            StyleSheet.absoluteFillObject,
            { width: '100%', height: '100%' },
            imageStyle,
          ]}
          resizeMode={resizeMode}
        />
      ) : (
        /* Dark Video Container Fallback / Loading */
        <View style={[StyleSheet.absoluteFillObject, styles.placeholder]}>
          <VideoIcon size={playButtonSize === 'small' ? 18 : 32} color="#8696a0" />
          {loading && (
            <ActivityIndicator
              size="small"
              color="#00a884"
              style={{ marginTop: 6 }}
            />
          )}
        </View>
      )}

      {/* Subtle bottom gradient tint so bottom-left badge & timestamps pop */}
      <View style={styles.bottomShadow} pointerEvents="none" />

      {/* WhatsApp Center Play Button */}
      {showPlayButton && !loading && (
        <View style={styles.centerContainer} pointerEvents="none">
          <View
            style={[
              styles.playButtonCircle,
              {
                width: playDimensions.btnSize,
                height: playDimensions.btnSize,
                borderRadius: playDimensions.btnSize / 2,
              },
            ]}
          >
            <Play
              size={playDimensions.iconSize}
              color="#FFFFFF"
              fill="#FFFFFF"
              style={{ marginLeft: playDimensions.iconOffset }}
            />
          </View>
        </View>
      )}

      {/* WhatsApp Bottom-Left Video Badge (Duration / Video Icon) */}
      {showDurationBadge && playButtonSize !== 'small' && (
        <View style={styles.bottomLeftBadge} pointerEvents="none">
          <VideoIcon size={11} color="#FFFFFF" />
          {formattedDuration ? (
            <Text style={styles.durationText}>{formattedDuration}</Text>
          ) : (
            <Text style={styles.durationText}>Video</Text>
          )}
        </View>
      )}

      {/* Additional Overlays (e.g. WhatsApp Timestamp & Status) */}
      {children}
    </View>
  );
}

const styles = StyleSheet.create({
  placeholder: {
    backgroundColor: '#111b21',
    alignItems: 'center',
    justifyContent: 'center',
  },
  bottomShadow: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    height: 48,
    backgroundColor: 'rgba(0,0,0,0.22)',
  },
  centerContainer: {
    ...StyleSheet.absoluteFillObject,
    alignItems: 'center',
    justifyContent: 'center',
  },
  playButtonCircle: {
    backgroundColor: 'rgba(11, 20, 26, 0.65)',
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.25)',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.35,
    shadowRadius: 4,
    elevation: 4,
  },
  bottomLeftBadge: {
    position: 'absolute',
    bottom: 6,
    left: 8,
    backgroundColor: 'rgba(11, 20, 26, 0.65)',
    paddingHorizontal: 7,
    paddingVertical: 2.5,
    borderRadius: 12,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  durationText: {
    color: '#FFFFFF',
    fontSize: 11,
    fontWeight: '600',
    letterSpacing: 0.2,
  },
});
