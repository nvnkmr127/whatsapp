/**
 * chatCache.ts — Persistent offline message cache using AsyncStorage.
 *
 * Each conversation's messages are stored under a key like:
 *   chat_cache_v1_{conversationId}
 *
 * The cache also stores conversation metadata (24h window, AI status, etc.).
 * On app open: load from cache instantly (no spinner), then refresh from API silently.
 */

import AsyncStorage from '@react-native-async-storage/async-storage';
import type { ChatMessage } from '@/types';

const MESSAGES_KEY = (id: number) => `chat_cache_v1_msgs_${id}`;
const META_KEY     = (id: number) => `chat_cache_v1_meta_${id}`;

export interface CachedMeta {
  isWithin24Hours: boolean;
  isAiEnabled: boolean;
  sessionExpiresAt: string | null;
  dbContactId: number;
}

// ──────────────────────────────────────────────
// Save
// ──────────────────────────────────────────────
export async function cacheMessages(conversationId: number, messages: ChatMessage[]) {
  try {
    await AsyncStorage.setItem(MESSAGES_KEY(conversationId), JSON.stringify(messages));
  } catch (_) { /* silently ignore write errors */ }
}

export async function cacheMeta(conversationId: number, meta: CachedMeta) {
  try {
    await AsyncStorage.setItem(META_KEY(conversationId), JSON.stringify(meta));
  } catch (_) { /* silently ignore write errors */ }
}

// ──────────────────────────────────────────────
// Load
// ──────────────────────────────────────────────
export async function loadCachedMessages(conversationId: number): Promise<ChatMessage[] | null> {
  try {
    const raw = await AsyncStorage.getItem(MESSAGES_KEY(conversationId));
    if (!raw) return null;
    return JSON.parse(raw) as ChatMessage[];
  } catch {
    return null;
  }
}

export async function loadCachedMeta(conversationId: number): Promise<CachedMeta | null> {
  try {
    const raw = await AsyncStorage.getItem(META_KEY(conversationId));
    if (!raw) return null;
    return JSON.parse(raw) as CachedMeta;
  } catch {
    return null;
  }
}
