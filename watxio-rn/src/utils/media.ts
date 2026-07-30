// src/utils/media.ts — Helper to resolve local/remote media URLs for React Native Image & Media components.
import { api } from '@/services/api';

export function resolveMediaUrl(rawUrl: string | null | undefined): string | null {
  if (!rawUrl) return null;
  let url = String(rawUrl).trim();
  if (!url) return null;

  // Get active API base URL (e.g. "http://192.168.31.52:8000/api" or "https://flow.watxio.com/api")
  const apiBaseUrl = api.getBaseUrl();
  const origin = apiBaseUrl.replace(/\/api\/?$/, ''); // e.g. "http://192.168.31.52:8000" or "https://flow.watxio.com"

  // Relative path e.g. "/storage/whatsapp/1/xyz.jpg" or "storage/whatsapp/1/xyz.jpg"
  if (url.startsWith('/')) {
    return `${origin}${url}`;
  }
  if (!url.startsWith('http://') && !url.startsWith('https://') && !url.startsWith('file://')) {
    return `${origin}/${url}`;
  }

  // If in DEV mode and URL points to localhost or 127.0.0.1 (which doesn't resolve on physical Android devices),
  // replace localhost with the actual dev machine IP from apiBaseUrl
  if (__DEV__ && (url.includes('localhost') || url.includes('127.0.0.1'))) {
    const originHostMatch = origin.match(/^https?:\/\/([^:/]+)(:\d+)?/);
    if (originHostMatch && originHostMatch[1]) {
      const activeIp = originHostMatch[1];
      const activePort = originHostMatch[2] || '';
      url = url.replace(/^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?/, `http://${activeIp}${activePort}`);
    }
  }

  return url;
}
