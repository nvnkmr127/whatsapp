import { Platform, NativeModules } from 'react-native';

let apiToken: string | null = null;
let apiTeamId: number | null = null;
let unauthorizedCallback: (() => void) | null = null;

const getDevMachineIp = () => {
  const scriptURL = NativeModules.SourceCode?.scriptURL;
  if (scriptURL) {
    const match = scriptURL.match(/^https?:\/\/([^:/]+)/);
    if (match && match[1]) {
      return match[1];
    }
  }
  return Platform.OS === 'android' ? '10.0.2.2' : 'localhost';
};

const defaultBaseUrl = `http://${getDevMachineIp()}:8000/api`;

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
      // Remove trailing slash if present
      apiBaseUrl = url.endsWith('/') ? url.slice(0, -1) : url;
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

    try {
      if (!isSilent) {
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

      if (!isSilent) {
        console.log(`[API RESPONSE] ${response.status} ${url}`, data);
      }

      if (!response.ok) {
        if (response.status === 401) {
          unauthorizedCallback?.();
        }
        throw {
          status: response.status,
          message: data?.message || `HTTP error! Status: ${response.status}`,
          errors: data?.errors || null,
          data,
        };
      }

      return data as T;
    } catch (error: any) {
      if (!isSilent) {
        console.error(`[API ERROR] ${method} ${url}`, error);
      }
      throw error;
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
