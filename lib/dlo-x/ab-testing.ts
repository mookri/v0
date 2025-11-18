// lib/dlo-x/ab-testing.ts

// Simple deterministic hash function for A/B testing
export function deterministicHash(str: string): number {
  let hash = 0;
  for (let i = 0; i < str.length; i++) {
    const char = str.charCodeAt(i);
    hash = (hash << 5) - hash + char;
    hash = hash & hash; // Convert to 32-bit integer
  }
  // Convert to positive percentage (0-100)
  return Math.abs(hash) % 101;
}