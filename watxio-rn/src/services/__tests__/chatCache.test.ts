// src/services/__tests__/chatCache.test.ts

import AsyncStorage from '@react-native-async-storage/async-storage';
import {
  cacheMessages,
  loadCachedMessages,
  cacheMeta,
  loadCachedMeta,
  cacheInbox,
  loadCachedInbox,
  cacheContacts,
  loadCachedContacts,
} from '../chatCache';

// Mock AsyncStorage
const mockStorage: Record<string, string> = {};

jest.mock('@react-native-async-storage/async-storage', () => ({
  setItem: jest.fn((key: string, value: string) => {
    mockStorage[key] = value;
    return Promise.resolve();
  }),
  getItem: jest.fn((key: string) => {
    return Promise.resolve(mockStorage[key] || null);
  }),
  removeItem: jest.fn((key: string) => {
    delete mockStorage[key];
    return Promise.resolve();
  }),
  clear: jest.fn(() => {
    Object.keys(mockStorage).forEach((key) => delete mockStorage[key]);
    return Promise.resolve();
  }),
}));

describe('chatCache Service', () => {
  beforeEach(() => {
    Object.keys(mockStorage).forEach((key) => delete mockStorage[key]);
    jest.clearAllMocks();
  });

  test('caches and loads messages correctly', async () => {
    const mockMessages = [
      { id: '1', text: 'Hello', timestamp: '10:00 AM', isMe: false, sender: 'John' },
      { id: '2', text: 'Hi there!', timestamp: '10:01 AM', isMe: true, sender: 'Agent' },
    ] as any;

    await cacheMessages(42, mockMessages);
    expect(AsyncStorage.setItem).toHaveBeenCalledWith('chat_cache_v1_msgs_42', JSON.stringify(mockMessages));

    const loaded = await loadCachedMessages(42);
    expect(loaded).toEqual(mockMessages);
  });

  test('returns null when loading non-existent cached messages', async () => {
    const loaded = await loadCachedMessages(999);
    expect(loaded).toBeNull();
  });

  test('caches and loads meta correctly', async () => {
    const mockMeta = {
      isWithin24Hours: true,
      isAiEnabled: false,
      sessionExpiresAt: '2026-07-29T10:00:00Z',
      dbContactId: 101,
    };

    await cacheMeta(42, mockMeta);
    const loaded = await loadCachedMeta(42);
    expect(loaded).toEqual(mockMeta);
  });

  test('caches and loads inbox conversation list', async () => {
    const mockConversations = [
      { id: 1, name: 'Alice', message: 'Hey', time: '10:00 AM' },
      { id: 2, name: 'Bob', message: 'Hello', time: '09:30 AM' },
    ] as any;

    await cacheInbox(mockConversations);
    const loaded = await loadCachedInbox();
    expect(loaded).toEqual(mockConversations);
  });

  test('caches and loads contacts list', async () => {
    const mockContacts = [{ id: 1, name: 'Charlie', phone: '+123456789' }];

    await cacheContacts(mockContacts);
    const loaded = await loadCachedContacts();
    expect(loaded).toEqual(mockContacts);
  });
});
