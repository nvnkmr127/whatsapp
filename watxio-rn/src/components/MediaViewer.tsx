import React, { useState } from 'react';
import {
  Modal, View, Pressable, Image, Text, StatusBar,
  ActivityIndicator, Dimensions, StyleSheet, Linking, Share,
} from 'react-native';
import { useVideoPlayer, VideoView } from 'expo-video';
import { useEvent } from 'expo';
import { X, Download, Share2 } from 'lucide-react-native';
import { resolveMediaUrl } from '@/utils/media';

const { width: SW, height: SH } = Dimensions.get('window');

interface Props {
  visible: boolean;
  uri: string;
  type: 'image' | 'video' | 'audio' | 'document';
  onClose: () => void;
}

export function MediaViewer({ visible, uri, type, onClose }: Props) {
  const [imageLoading, setImageLoading] = useState(true);
  const resolvedUri = resolveMediaUrl(uri) || uri;

  const rawType = (type || '').toLowerCase();
  const urlLower = (resolvedUri || uri || '').toLowerCase();

  let isImage = rawType === 'image' || rawType === 'photo' || rawType === 'sticker';
  let isVideo = rawType === 'video';
  let isAudio = rawType === 'audio' || rawType === 'voice' || rawType === 'ptt';
  let isDoc   = rawType === 'document' || rawType === 'file';

  if (!isImage && !isVideo && !isAudio && !isDoc && urlLower) {
    if (urlLower.match(/\.(jpg|jpeg|png|gif|webp|heic)(\?|$)/i)) isImage = true;
    else if (urlLower.match(/\.(mp4|mov|avi|mkv|3gp|webm)(\?|$)/i)) isVideo = true;
    else if (urlLower.match(/\.(mp3|m4a|aac|wav|ogg|opus)(\?|$)/i)) isAudio = true;
    else if (urlLower.match(/\.(pdf|doc|docx|xls|xlsx|csv|zip|rar|txt)(\?|$)/i)) isDoc = true;
    else isImage = true;
  }

  // Setup the video player
  const player = useVideoPlayer(isVideo && visible ? resolvedUri : null, (playerInstance) => {
    playerInstance.play();
  });

  const { status, error } = useEvent(player, 'statusChange', {
    status: player.status,
  });

  React.useEffect(() => {
    if (visible) {
      setImageLoading(true);
    }
  }, [uri, visible]);

  React.useEffect(() => {
    if (visible && isVideo && resolvedUri) {
      player.replaceAsync(resolvedUri);
      player.play();
    } else {
      player.pause();
    }
  }, [visible, isVideo, resolvedUri, player]);

  React.useEffect(() => {
    if (error) {
      console.warn('Video playback error:', error);
    }
  }, [error]);

  const handleDownload = async () => {
    try {
      if (resolvedUri) {
        await Linking.openURL(resolvedUri);
      }
    } catch (e) {
      console.warn('Cannot open media URL:', e);
    }
  };

  const handleShare = async () => {
    try {
      if (resolvedUri) {
        await Share.share({
          url: resolvedUri,
          message: resolvedUri,
          title: 'Shared Media File',
        });
      }
    } catch (e) {
      console.warn('Error sharing media:', e);
    }
  };

  const fileName = uri ? uri.split('/').pop()?.split('?')[0] || 'Media File' : 'Media File';

  return (
    <Modal visible={visible} transparent animationType="fade" statusBarTranslucent onRequestClose={onClose}>
      <StatusBar hidden />
      <View style={styles.backdrop}>
        {/* Top Header Controls Bar */}
        <View style={styles.headerBar}>
          <Pressable onPress={onClose} style={styles.iconBtn}>
            <X size={22} color="#fff" />
          </Pressable>
          
          <Text style={styles.headerTitle} numberOfLines={1}>
            {fileName}
          </Text>

          <View style={styles.headerActions}>
            <Pressable onPress={handleShare} style={styles.iconBtn}>
              <Share2 size={20} color="#fff" />
            </Pressable>
            <Pressable onPress={handleDownload} style={styles.iconBtn}>
              <Download size={20} color="#fff" />
            </Pressable>
          </View>
        </View>

        {/* Content View */}
        <View style={styles.content}>
          {isImage && (
            <>
              {imageLoading && <ActivityIndicator color="#fff" size="large" style={StyleSheet.absoluteFillObject} />}
              <Image
                source={{ uri: resolvedUri }}
                style={styles.image}
                resizeMode="contain"
                onLoadEnd={() => setImageLoading(false)}
              />
            </>
          )}
          {isVideo && (
            <View style={styles.image}>
              {(status === 'loading' || status === 'idle') && (
                <ActivityIndicator color="#fff" size="large" style={StyleSheet.absoluteFillObject} />
              )}
              <VideoView
                player={player}
                style={styles.image}
                nativeControls
                contentFit="contain"
              />
            </View>
          )}
          {(isAudio || isDoc) && (
            <View style={styles.docBox}>
              <Text style={styles.docText}>
                {isAudio ? '🎵' : '📄'}  {fileName}
              </Text>
              <Pressable onPress={handleDownload} style={styles.downloadBtn}>
                <Download size={18} color="#fff" />
                <Text style={styles.downloadBtnText}>Open / Download File</Text>
              </Pressable>
            </View>
          )}
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  backdrop: { flex: 1, backgroundColor: 'rgba(0,0,0,0.96)', justifyContent: 'center', alignItems: 'center' },
  headerBar: {
    position: 'absolute', top: 44, left: 0, right: 0, zIndex: 10,
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: 16, paddingVertical: 10, backgroundColor: 'rgba(0,0,0,0.6)',
  },
  headerTitle: { flex: 1, color: '#ffffff', fontSize: 15, fontWeight: '600', marginHorizontal: 12 },
  headerActions: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  iconBtn: { padding: 8, borderRadius: 20, backgroundColor: 'rgba(255,255,255,0.15)' },
  content: { width: SW, height: SH * 0.85, justifyContent: 'center', alignItems: 'center', marginTop: 40 },
  image: { width: SW, height: SH * 0.82, justifyContent: 'center', alignItems: 'center' },
  docBox: { padding: 28, backgroundColor: 'rgba(255,255,255,0.1)', borderRadius: 20, alignItems: 'center', gap: 16, maxWidth: '85%' },
  docText: { color: '#fff', fontSize: 16, fontWeight: '600', textAlign: 'center' },
  downloadBtn: { flexDirection: 'row', alignItems: 'center', gap: 8, backgroundColor: '#10B981', paddingHorizontal: 20, paddingVertical: 12, borderRadius: 24 },
  downloadBtnText: { color: '#fff', fontSize: 14, fontWeight: '700' },
});
