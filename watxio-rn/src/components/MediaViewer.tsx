import React, { useState } from 'react';
import {
  Modal, View, Pressable, Image, Text, StatusBar,
  ActivityIndicator, Dimensions, StyleSheet,
} from 'react-native';
import { Video, ResizeMode } from 'expo-av';
import { X } from 'lucide-react-native';
import { resolveMediaUrl } from '@/utils/media';

const { width: SW, height: SH } = Dimensions.get('window');

interface Props {
  visible: boolean;
  uri: string;
  type: 'image' | 'video' | 'audio' | 'document';
  onClose: () => void;
}

export function MediaViewer({ visible, uri, type, onClose }: Props) {
  const [loading, setLoading] = useState(true);
  const resolvedUri = resolveMediaUrl(uri) || uri;

  return (
    <Modal visible={visible} transparent animationType="fade" statusBarTranslucent onRequestClose={onClose}>
      <StatusBar hidden />
      <View style={styles.backdrop}>
        {/* Close button */}
        <Pressable onPress={onClose} style={styles.closeBtn}>
          <X size={24} color="#fff" />
        </Pressable>

        {/* Content */}
        <View style={styles.content}>
          {type === 'image' && (
            <>
              {loading && <ActivityIndicator color="#fff" size="large" style={StyleSheet.absoluteFillObject} />}
              <Image
                source={{ uri: resolvedUri }}
                style={styles.image}
                resizeMode="contain"
                onLoadEnd={() => setLoading(false)}
              />
            </>
          )}
          {type === 'video' && (
            <View style={styles.image}>
              {loading && <ActivityIndicator color="#fff" size="large" style={StyleSheet.absoluteFillObject} />}
              <Video
                source={{ uri: resolvedUri }}
                style={styles.image}
                useNativeControls
                resizeMode={ResizeMode.CONTAIN}
                shouldPlay
                onLoad={() => setLoading(false)}
                onError={(err) => {
                  console.warn('Video playback error:', err);
                  setLoading(false);
                }}
              />
            </View>
          )}
          {(type === 'audio' || type === 'document') && (
            <View style={styles.docBox}>
              <Text style={styles.docText}>
                {type === 'audio' ? '🎵' : '📄'}  {uri.split('/').pop() || 'File'}
              </Text>
            </View>
          )}
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  backdrop:    { flex: 1, backgroundColor: 'rgba(0,0,0,0.95)', justifyContent: 'center', alignItems: 'center' },
  closeBtn:    { position: 'absolute', top: 52, right: 20, zIndex: 10, backgroundColor: 'rgba(0,0,0,0.5)', borderRadius: 20, padding: 8 },
  content:     { width: SW, height: SH * 0.85, justifyContent: 'center', alignItems: 'center' },
  image:       { width: SW, height: SH * 0.85, justifyContent: 'center', alignItems: 'center' },
  playOverlay: { position: 'absolute', top: 0, left: 0, right: 0, bottom: 0, justifyContent: 'center', alignItems: 'center' },
  playCircle:  { width: 72, height: 72, borderRadius: 36, backgroundColor: 'rgba(0,0,0,0.6)', justifyContent: 'center', alignItems: 'center' },
  docBox:      { padding: 24, backgroundColor: 'rgba(255,255,255,0.1)', borderRadius: 16, alignItems: 'center', gap: 8 },
  docText:     { color: '#fff', fontSize: 16, fontWeight: '600' },
  docHint:     { color: 'rgba(255,255,255,0.5)', fontSize: 13 },
});
