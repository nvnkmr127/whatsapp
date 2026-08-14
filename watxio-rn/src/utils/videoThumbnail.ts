// src/utils/videoThumbnail.ts — High performance cached video thumbnail generator
import * as VideoThumbnails from 'expo-video-thumbnails';

const memoryCache = new Map<string, string>();
const inFlightRequests = new Map<string, Promise<string | null>>();

/**
 * Generate and cache a thumbnail for local or remote video files.
 * @param videoUri Video URL (http/https) or local file path (file://)
 * @param timeMs Time offset in milliseconds to extract the frame (default: 500ms)
 * @returns Local file URI of the generated thumbnail image or null if extraction fails
 */
export async function getVideoThumbnail(videoUri: string | null | undefined, timeMs: number = 500): Promise<string | null> {
  if (!videoUri) return null;
  const cleanUri = String(videoUri).trim();
  if (!cleanUri) return null;

  const key = `${cleanUri}_${timeMs}`;

  if (memoryCache.has(key)) {
    return memoryCache.get(key)!;
  }

  if (inFlightRequests.has(key)) {
    return inFlightRequests.get(key)!;
  }

  const promise = (async () => {
    try {
      const result = await VideoThumbnails.getThumbnailAsync(cleanUri, {
        time: timeMs,
        quality: 0.85,
      });

      if (result && result.uri) {
        memoryCache.set(key, result.uri);
        return result.uri;
      }
    } catch (err) {
      // If extracting at timeMs > 0 fails, retry at 0ms (first keyframe)
      if (timeMs > 0) {
        try {
          const fallback = await VideoThumbnails.getThumbnailAsync(cleanUri, {
            time: 0,
            quality: 0.85,
          });
          if (fallback && fallback.uri) {
            memoryCache.set(key, fallback.uri);
            return fallback.uri;
          }
        } catch (retryErr) {
          // Ignore retry error
        }
      }
    } finally {
      inFlightRequests.delete(key);
    }
    return null;
  })();

  inFlightRequests.set(key, promise);
  return promise;
}

/**
 * Synchronously checks if a thumbnail is already cached in memory.
 */
export function getCachedVideoThumbnail(videoUri: string | null | undefined, timeMs: number = 500): string | null {
  if (!videoUri) return null;
  return memoryCache.get(`${String(videoUri).trim()}_${timeMs}`) || null;
}
