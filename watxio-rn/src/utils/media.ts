// src/utils/media.ts — Helper to resolve local/remote media URLs for React Native Image & Media components.
import { api } from '@/services/api';

export function resolveMediaUrl(rawUrl: string | null | undefined): string | null {
  if (!rawUrl) return null;
  let url = String(rawUrl).trim();
  if (!url) return null;

  // Get active API base URL (e.g. "http://192.168.31.52:8000/api" or "https://flow.watxio.com/api")
  const apiBaseUrl = api.getBaseUrl();
  const origin = apiBaseUrl.replace(/\/api\/?$/, ''); // e.g. "http://192.168.31.52:8000" or "https://flow.watxio.com"

  // Extract host & port from origin (e.g. "192.168.31.52:8000" or "flow.watxio.com")
  const originHostMatch = origin.match(/^https?:\/\/([^/]+)/);
  const activeHostAndPort = originHostMatch ? originHostMatch[1] : '';

  // 1. Data URIs or file scheme
  if (url.startsWith('data:') || url.startsWith('file://')) {
    return url;
  }

  // 2. Absolute HTTP/HTTPS URLs
  if (url.startsWith('http://') || url.startsWith('https://')) {
    // Replace localhost, 127.0.0.1, or 10.0.2.2 with active API host (handles dev on physical devices/emulators)
    if (url.includes('localhost') || url.includes('127.0.0.1') || url.includes('10.0.2.2')) {
      if (activeHostAndPort) {
        const protocol = origin.startsWith('https') ? 'https' : 'http';
        url = url.replace(/^https?:\/\/(localhost|127\.0\.0\.1|10\.0\.2\.2)(:\d+)?/, `${protocol}://${activeHostAndPort}`);
      }
    }
    // Inject /storage/ prefix for local whatsapp storage paths if missing
    if (url.includes('/whatsapp/') && !url.includes('/storage/whatsapp/')) {
      url = url.replace('/whatsapp/', '/storage/whatsapp/');
    }
    return url;
  }

  // 3. Relative paths (e.g. "whatsapp/1/abc.jpg", "/whatsapp/1/abc.jpg", "storage/whatsapp/1/abc.jpg")
  let path = url.startsWith('/') ? url : `/${url}`;

  if (path.startsWith('/whatsapp/')) {
    path = `/storage${path}`;
  } else if (!path.startsWith('/storage/')) {
    path = `/storage${path}`;
  }

  return `${origin}${path}`;
}

