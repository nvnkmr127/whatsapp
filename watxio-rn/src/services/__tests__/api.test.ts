// src/services/__tests__/api.test.ts

import { api } from '../api';

// Mock react-native and expo-constants
jest.mock('react-native', () => ({
  Platform: { OS: 'android' },
  NativeModules: { SourceCode: { scriptURL: 'http://192.168.31.52:8081/index.bundle' } },
}));

jest.mock('expo-constants', () => ({
  __esModule: true,
  default: {
    expoConfig: { hostUri: '192.168.31.52:8081' },
    hostUri: '192.168.31.52:8081',
  },
}));

describe('API Service', () => {
  const originalFetch = global.fetch;

  beforeEach(() => {
    api.setToken(null);
    api.setTeamId(null);
    api.setBaseUrl('http://192.168.31.52:8000/api');
    global.fetch = jest.fn();
  });

  afterEach(() => {
    global.fetch = originalFetch;
  });

  test('setBaseUrl removes trailing slash', () => {
    api.setBaseUrl('http://localhost:8000/api/');
    expect(api.getBaseUrl()).toBe('http://localhost:8000/api');
  });

  test('setToken and getToken manage authorization token correctly', () => {
    api.setToken('test-sanctum-token-123');
    expect(api.getToken()).toBe('test-sanctum-token-123');
  });

  test('request sends correct headers and body for POST', async () => {
    api.setToken('my-token');
    api.setTeamId(5);

    const mockResponse = { success: true, data: { id: 101 } };
    (global.fetch as jest.Mock).mockResolvedValueOnce({
      ok: true,
      status: 200,
      text: jest.fn().mockResolvedValueOnce(JSON.stringify(mockResponse)),
    });

    const result = await api.post('/v1/test-endpoint', { foo: 'bar' });

    expect(result).toEqual(mockResponse);
    expect(global.fetch).toHaveBeenCalledWith(
      'http://192.168.31.52:8000/api/v1/test-endpoint',
      expect.objectContaining({
        method: 'POST',
        headers: expect.objectContaining({
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'Authorization': 'Bearer my-token',
          'X-Tenant-ID': '5',
        }),
        body: JSON.stringify({ foo: 'bar' }),
      })
    );
  });

  test('triggers unauthorizedCallback on 401 status', async () => {
    const onUnauthorizedMock = jest.fn();
    api.onUnauthorized(onUnauthorizedMock);

    (global.fetch as jest.Mock).mockResolvedValueOnce({
      ok: false,
      status: 401,
      text: jest.fn().mockResolvedValueOnce(JSON.stringify({ message: 'Unauthenticated.' })),
    });

    await expect(api.get('/v1/protected')).rejects.toEqual(
      expect.objectContaining({
        status: 401,
        message: 'Unauthenticated.',
      })
    );

    expect(onUnauthorizedMock).toHaveBeenCalledTimes(1);
  });

  test('normalizes AbortError into TimeoutError', async () => {
    const abortError = new Error('Aborted');
    abortError.name = 'AbortError';

    (global.fetch as jest.Mock).mockRejectedValueOnce(abortError);

    await expect(api.get('/v1/slow-endpoint')).rejects.toEqual(
      expect.objectContaining({
        isTimeout: true,
        message: 'Request timed out after 15s',
        name: 'TimeoutError',
      })
    );
  });
});
