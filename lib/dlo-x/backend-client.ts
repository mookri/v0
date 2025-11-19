// lib/dlo-x/backend-client.ts
// Utility for making fetch calls with required DLO-X headers

interface DLOXHeaders {
  'x-request-id': string;
  'x-layout-policy-version': string;
  'x-layout-shells': string;
  'x-tenant-id': string;
  'x-user-role': string;
}

interface FetchOptions extends RequestInit {
  dloHeaders: Partial<DLOXHeaders>;
}

// Default version for layout policy
const LAYOUT_POLICY_VERSION = '1.0.0';

export class DLOXBackendClient {
  private baseUrl: string;
  
  constructor(baseUrl: string) {
    this.baseUrl = baseUrl.replace(/\/$/, ''); // Remove trailing slash
  }
  
  async fetch(path: string, options: FetchOptions = { dloHeaders: {} }): Promise<Response> {
    const url = `${this.baseUrl}${path}`;
    
    // Merge DLO-X headers with any existing headers
    const headers = new Headers(options.headers);
    
    // Set DLO-X headers
    if (options.dloHeaders['x-request-id']) {
      headers.set('x-request-id', options.dloHeaders['x-request-id']);
    }
    
    headers.set('x-layout-policy-version', LAYOUT_POLICY_VERSION);
    
    if (options.dloHeaders['x-layout-shells']) {
      headers.set('x-layout-shells', options.dloHeaders['x-layout-shells']);
    }
    
    if (options.dloHeaders['x-tenant-id']) {
      headers.set('x-tenant-id', options.dloHeaders['x-tenant-id']);
    }
    
    if (options.dloHeaders['x-user-role']) {
      headers.set('x-user-role', options.dloHeaders['x-user-role']);
    }
    
    // Create new options object with merged headers
    const fetchOptions: RequestInit = {
      ...options,
      headers
    };
    
    return fetch(url, fetchOptions);
  }
  
  // Convenience methods for common HTTP verbs
  async get(path: string, options: Omit<FetchOptions, 'method'> = { dloHeaders: {} }) {
    return this.fetch(path, { ...options, method: 'GET' });
  }
  
  async post(path: string, options: Omit<FetchOptions, 'method'> = { dloHeaders: {} }) {
    return this.fetch(path, { ...options, method: 'POST' });
  }
  
  async put(path: string, options: Omit<FetchOptions, 'method'> = { dloHeaders: {} }) {
    return this.fetch(path, { ...options, method: 'PUT' });
  }
  
  async delete(path: string, options: Omit<FetchOptions, 'method'> = { dloHeaders: {} }) {
    return this.fetch(path, { ...options, method: 'DELETE' });
  }
}

// Create a default instance
export const dloClient = new DLOXBackendClient(
  process.env.NEXT_PUBLIC_API_URL || 'http://localhost:3001'
);

// Utility function for WebSocket connections with metadata
export function createDLOXWebSocket(url: string, protocols?: string | string[]): WebSocket {
  const ws = new WebSocket(url, protocols);
  
  // Add metadata to WebSocket connection
  ws.addEventListener('open', () => {
    // In a real implementation, you might send auth metadata here
    console.log('DLO-X WebSocket connection established');
  });
  
  return ws;
}

// Utility for analytics event tracking
export function trackDLOXEvent(eventName: string, properties: Record<string, any> = {}) {
  // In a real implementation, this would send to your analytics service
  console.log(`[DLO-X Analytics] ${eventName}:`, properties);
  
  // Example of what you might send:
  // fetch('/api/analytics', {
  //   method: 'POST',
  //   headers: { 'Content-Type': 'application/json' },
  //   body: JSON.stringify({ 
  //     event: eventName, 
  //     properties, 
  //     timestamp: new Date().toISOString() 
  //   })
  // });
}

// Utility for navigation event tracking
export function trackNavigationEvent(from: string, to: string, properties: Record<string, any> = {}) {
  trackDLOXEvent('navigation', {
    from,
    to,
    ...properties
  });
}

// Utility for correlation logs between frontend and backend
export function logCorrelation(requestId: string, message: string, details: Record<string, any> = {}) {
  console.log(`[DLO-X Correlation][${requestId}] ${message}`, details);
  
  // In a real implementation, you might send this to a logging service
  // fetch('/api/logs', {
  //   method: 'POST',
  //   headers: { 'Content-Type': 'application/json' },
  //   body: JSON.stringify({ 
  //     requestId, 
  //     message, 
  //     details,
  //     timestamp: new Date().toISOString() 
  //   })
  // });
}