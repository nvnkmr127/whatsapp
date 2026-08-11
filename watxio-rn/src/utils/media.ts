// src/utils/media.ts — Helper to resolve local/remote media URLs for React Native Image & Media components.
import { api } from '@/services/api';

export function resolveMediaUrl(rawUrl: string | null | undefined): string | null {
  if (!rawUrl) return null;
  let url = String(rawUrl).trim();
  if (!url) return null;

  if (url.startsWith('data:') || url.startsWith('file://')) {
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



