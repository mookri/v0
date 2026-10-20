export interface IdGenerator {
  generate(): string;
}

export function prefixedId(prefix: string, rand: () => string = () => Math.random().toString(36).slice(2)): string {
  return `${prefix}_${Date.now()}_${rand()}`;
}
