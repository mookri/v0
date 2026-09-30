import type { ObjectStorage, StoredObject } from '../../../packages/contracts/src/storage/object-storage';

export class MemoryObjectStorage implements ObjectStorage {
  private readonly store = new Map<string, Uint8Array>();

  async put(key: string, body: Uint8Array): Promise<StoredObject> {
    this.store.set(key, body);
    return { key };
  }

  async get(key: string): Promise<Uint8Array | null> {
    return this.store.get(key) ?? null;
  }

  async delete(key: string): Promise<void> {
    this.store.delete(key);
  }

  async getSignedUrl(key: string, expiresInSeconds: number): Promise<string> {
    return `memory://${key}?expires=${expiresInSeconds}`;
  }
}
