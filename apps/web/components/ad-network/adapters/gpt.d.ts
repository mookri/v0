// components/ad-network/adapters/gpt.d.ts

/**
 * Global type definitions for Google Publisher Tag (GPT)
 * to resolve TypeScript errors when accessing window.googletag.
 */

interface Window {
  googletag?: {
    cmd: Array<() => void>;
    [key: string]: any;
  };
}
