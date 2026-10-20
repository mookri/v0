export interface Redirect {
  from: string;
  to: string;
  status: 301 | 302;
}

export class RedirectRegistry {
  private readonly map = new Map<string, Redirect>();

  register(r: Redirect): void {
    this.map.set(r.from, r);
  }

  resolve(path: string): Redirect | null {
    return this.map.get(path) ?? null;
  }
}
