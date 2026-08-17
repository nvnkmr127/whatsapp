// src/utils/media.ts — Helper to resolve local/remote media URLs for React Native Image & Media components.
import { api } from '@/services/api';

export type MediaCategory = 'image' | 'video' | 'audio' | 'document';

export function resolveMediaUrl(rawUrl: string | null | undefined): string | null {
  if (!rawUrl) return null;
  let url = String(rawUrl).trim();
  if (!url) return null;

  // Local device URIs (Android Content Provider, iOS Photos, file, data, blob)
  if (
    url.startsWith('data:') ||
    url.startsWith('file://') ||
    url.startsWith('content://') ||
    url.startsWith('ph://') ||
    url.startsWith('assets-library://') ||
    url.startsWith('blob:')
  ) {
    return url;
  }

  const apiBaseUrl = api.getBaseUrl();
  const origin = apiBaseUrl.replace(/\/api\/?$/, ''); // e.g. "https://flow.watxio.com"

  // Handle absolute HTTP/HTTPS URLs
  if (url.startsWith('http://') || url.startsWith('https://')) {
    if (url.includes('localhost') || url.includes('127.0.0.1') || url.includes('10.0.2.2')) {
      const originHostMatch = origin.match(/^https?:\/\/([^/]+)/);
      if (originHostMatch && originHostMatch[1]) {
        const activeHostAndPort = originHostMatch[1];
        const protocol = origin.startsWith('https') ? 'https' : 'http';
        url = url.replace(/^https?:\/\/(localhost|127\.0\.0\.1|10\.0\.2\.2)(:\d+)?/, `${protocol}://${activeHostAndPort}`);
      }
    }
    return url;
  }

  // Clean relative path (e.g. "storage/whatsapp/1/xyz.jpg", "public/whatsapp/1/xyz.jpg", "whatsapp/1/xyz.jpg")
  const cleanPath = url.replace(/^\/?(storage\/public|public\/storage|storage|public)\//i, '');
  
  return `${origin}/storage/${cleanPath}`;
}

/**
 * Accurately classifies media into 'video' | 'audio' | 'image' | 'document'
 * based on both MIME type and file extension.
 */
export function detectMediaType(mediaType?: string | null, mediaUrl?: string | null): MediaCategory {
  const rawType = (mediaType || '').toLowerCase().trim();
  const cleanUrl = (mediaUrl || '').toLowerCase().trim().split('?')[0];

  // 1. Check video extensions (.mp4 is always video even if legacy MIME was audio/mp4)
  if (cleanUrl.match(/\.(mp4|mov|avi|mkv|3gp|webm|m4v)$/i)) {
    return 'video';
  }

  // 2. Check explicit video MIME types & names
  if (rawType.startsWith('video') || rawType === 'video/mp4' || rawType === 'video/quicktime' || rawType === 'video/3gpp') {
    return 'video';
  }

  // 3. Check audio extensions (.m4a, .mp3, .ogg, .opus, etc.)
  if (cleanUrl.match(/\.(ogg|opus|mp3|m4a|aac|wav|amr)$/i)) {
    return 'audio';
  }

  // 4. Check explicit audio MIME types & names (note: audio/mp4 for .m4a audio)
  if (rawType.startsWith('audio') || rawType === 'voice' || rawType === 'ptt') {
    return 'audio';
  }

  // 5. Check image extensions
  if (cleanUrl.match(/\.(jpg|jpeg|png|gif|webp|heic|heif|svg)$/i)) {
    return 'image';
  }

  // 6. Check explicit image MIME types & names
  if (rawType.startsWith('image') || rawType === 'photo' || rawType === 'sticker') {
    return 'image';
  }

  // 7. Check document extensions
  if (cleanUrl.match(/\.(pdf|doc|docx|xls|xlsx|csv|zip|rar|txt|json|xml|pptx|ppt)$/i)) {
    return 'document';
  }

  // 8. Check explicit document MIME types & names
  if (rawType.startsWith('application') || rawType.startsWith('text') || rawType === 'document' || rawType === 'file') {
    return 'document';
  }

  // Fallback: if mediaUrl exists, default to image
  if (mediaUrl) {
    return 'image';
  }

  return 'document';
}




