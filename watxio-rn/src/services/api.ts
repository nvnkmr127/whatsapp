import { Platform, NativeModules } from 'react-native';
import Constants from 'expo-constants';

let apiToken: string | null = null;
let apiTeamId: number | null = null;
let unauthorizedCallback: (() => void) | null = null;

const getDevMachineIp = () => {
  const hostUri = Constants.expoConfig?.hostUri || Constants.hostUri;
  if (hostUri) {
    const ip = hostUri.split(':')[0];
    if (ip && ip !== 'localhost' && ip !== '127.0.0.1') {
      return ip;
    }
  }

  const scriptURL = NativeModules.SourceCode?.scriptURL;
  if (scriptURL) {
    const match = scriptURL.match(/^https?:\/\/([^:/]+)/);
    if (match && match[1] && match[1] !== 'localhost' && match[1] !== '127.0.0.1') {
      return match[1];
    }
  }

  return '192.168.31.52';
};

const defaultBaseUrl = 'https://flow.watxio.com/api';

let apiBaseUrl: string = defaultBaseUrl;

export const api = {
  onUnauthorized(callback: () => void) {
    unauthorizedCallback = callback;
  },

  setToken(token: string | null) {
    apiToken = token;
  },

  setBaseUrl(url: string) {
    if (url) {
      apiBaseUrl = url.endsWith('/') ? url.slice(0, -1) : url;
    } else {
      apiBaseUrl = defaultBaseUrl;
    }
  },

  setTeamId(teamId: number | null) {
    apiTeamId = teamId;
  },

  getToken() {
    return apiToken;
  },

  getBaseUrl() {
    return apiBaseUrl;
  },

  async request<T = any>(
    method: string,
    endpoint: string,
    body?: any,
    customHeaders?: Record<string, string>
  ): Promise<T> {
    const cleanEndpoint = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
    const url = `${apiBaseUrl}${cleanEndpoint}`;

    const isSilent = customHeaders?.['X-Silent-Errors'] === 'true';

    const headers: Record<string, string> = {
      'Accept': 'application/json',
      ...customHeaders,
    };

    if (method === 'GET') {
      headers['Cache-Control'] = 'no-cache, no-store, must-revalidate';
      headers['Pragma'] = 'no-cache';
      headers['Expires'] = '0';
    }

    if (isSilent) {
      delete headers['X-Silent-Errors'];
    }

    if (apiToken) {
      headers['Authorization'] = `Bearer ${apiToken}`;
    }

    if (apiTeamId) {
      headers['X-Tenant-ID'] = String(apiTeamId);
    }

    const config: RequestInit = {
      method,
      headers,
    };

    if (body) {
      if (body instanceof FormData) {
        config.body = body;
        // Fetch will set the boundary header automatically for FormData
      } else {
        headers['Content-Type'] = 'application/json';
        config.body = JSON.stringify(body);
      }
    }

    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 15000);
    config.signal = controller.signal;

    try {
      if (__DEV__ && !isSilent) {
        console.log(`[API REQUEST] ${method} ${url}`, { headers, body });
      }
      const response = await fetch(url, config);

      let data: any = null;
      const text = await response.text();
      if (text) {
        try {
          data = JSON.parse(text);
        } catch {
          data = { rawText: text };
        }
      }

      if (__DEV__ && !isSilent) {
        console.log(`[API RESPONSE] ${response.status} ${url}`, data);
      }

      if (!response.ok) {
        if (response.status === 401) {
          const isLockLost = data?.code === 'ERR_LOCK_LOST';
          if (!isLockLost) {
            unauthorizedCallback?.();
          }
        }
        let errorMessage = data?.message;
        if (!errorMessage) {
          if (response.status === 413) {
            errorMessage = 'The file is too large to upload. Please try a smaller file or compress it.';
          } else {
            errorMessage = `HTTP error! Status: ${response.status}`;
          }
        }
        throw {
          status: response.status,
          message: errorMessage,
          errors: data?.errors || null,
          data,
        };
      }

      return data as T;
    } catch (error: any) {
      const isAbort = error?.name === 'AbortError' || error?.message === 'Aborted';
      const normalizedError = isAbort
        ? { status: undefined, message: 'Request timed out after 15s', isTimeout: true, name: 'TimeoutError' }
        : error;

      if (__DEV__ && !isSilent) {
        console.error(`[API ERROR] ${method} ${url}`, normalizedError);
      }
      throw normalizedError;
    } finally {
      clearTimeout(timeoutId);
    }
  },

  get<T = any>(endpoint: string, headers?: Record<string, string>) {
    return this.request<T>('GET', endpoint, undefined, headers);
  },

  post<T = any>(endpoint: string, body?: any, headers?: Record<string, string>) {
    return this.request<T>('POST', endpoint, body, headers);
  },

  put<T = any>(endpoint: string, body?: any, headers?: Record<string, string>) {
    return this.request<T>('PUT', endpoint, body, headers);
  },

  patch<T = any>(endpoint: string, body?: any, headers?: Record<string, string>) {
    return this.request<T>('PATCH', endpoint, body, headers);
  },

  delete<T = any>(endpoint: string, headers?: Record<string, string>) {
    return this.request<T>('DELETE', endpoint, undefined, headers);
  },
};
