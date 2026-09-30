export interface StoredObject {
  key: string;
}

export interface ObjectStorage {
  put(key: string, body: Uint8Array, contentType?: string): Promise<StoredObject>;
  get(key: string): Promise<Uint8Array | null>;
  delete(key: string): Promise<void>;
  getSignedUrl(key: string, expiresInSeconds: number): Promise<string>;
}
