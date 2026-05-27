/**
 * chatCache.ts — Persistent offline cache using AsyncStorage.
 * Covers: messages, conversation meta, conversation list, contacts list.
 */
import AsyncStorage from '@react-native-async-storage/async-storage';
import type { ChatMessage, Conversation } from '@/types';

const MESSAGES_KEY  = (id: number) => `chat_cache_v1_msgs_${id}`;
const META_KEY      = (id: number) => `chat_cache_v1_meta_${id}`;
const INBOX_KEY     = 'chat_cache_v1_inbox';
const CONTACTS_KEY  = 'chat_cache_v1_contacts';

export interface CachedMeta {
  isWithin24Hours: boolean;
  isAiEnabled: boolean;
  sessionExpiresAt: string | null;
  dbContactId: number;
}

// ── Messages ─────────────────────────────────────────────────────────────────
export async function cacheMessages(id: number, msgs: ChatMessage[]) {
  try { await AsyncStorage.setItem(MESSAGES_KEY(id), JSON.stringify(msgs)); } catch (_) {}
}
export async function loadCachedMessages(id: number): Promise<ChatMessage[] | null> {
  try { const r = await AsyncStorage.getItem(MESSAGES_KEY(id)); return r ? JSON.parse(r) : null; } catch { return null; }
}

// ── Conversation meta ─────────────────────────────────────────────────────────
export async function cacheMeta(id: number, meta: CachedMeta) {
  try { await AsyncStorage.setItem(META_KEY(id), JSON.stringify(meta)); } catch (_) {}
}
export async function loadCachedMeta(id: number): Promise<CachedMeta | null> {
  try { const r = await AsyncStorage.getItem(META_KEY(id)); return r ? JSON.parse(r) : null; } catch { return null; }
}

// ── Inbox (conversation list) ─────────────────────────────────────────────────
export async function cacheInbox(conversations: Conversation[]) {
  try { await AsyncStorage.setItem(INBOX_KEY, JSON.stringify(conversations)); } catch (_) {}
}
export async function loadCachedInbox(): Promise<Conversation[] | null> {
  try { const r = await AsyncStorage.getItem(INBOX_KEY); return r ? JSON.parse(r) : null; } catch { return null; }
}

// ── Contacts list ─────────────────────────────────────────────────────────────
export async function cacheContacts(contacts: any[]) {
  try { await AsyncStorage.setItem(CONTACTS_KEY, JSON.stringify(contacts)); } catch (_) {}
}
export async function loadCachedContacts(): Promise<any[] | null> {
  try { const r = await AsyncStorage.getItem(CONTACTS_KEY); return r ? JSON.parse(r) : null; } catch { return null; }
}
