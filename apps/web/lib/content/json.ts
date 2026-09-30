export function stripUtf8Bom(value: string): string {
  return value.charCodeAt(0) === 0xfeff ? value.slice(1) : value;
}

export function parseJsonString<T>(value: string): T {
  return JSON.parse(stripUtf8Bom(value)) as T;
}
