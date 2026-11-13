import { createHash, createPrivateKey, generateKeyPairSync, randomUUID, sign } from 'node:crypto';
import type { KeyObject } from 'node:crypto';
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';

export interface DpopKey {
  privateKey: KeyObject;
  publicJwk: Record<string, string>;
}

const cachedByFile = new Map<string, DpopKey>();

function defaultKeyFile(envVar = 'CUSTOMER_DPOP_KEY_FILE'): string {
  return process.env[envVar] || path.join(process.cwd(), '.next', 'dpop-key.json');
}

function keyFromRecord(record: Record<string, unknown>): DpopKey | null {
  if (
    record.kty !== 'EC' ||
    record.crv !== 'P-256' ||
    typeof record.x !== 'string' ||
    typeof record.y !== 'string' ||
    typeof record.d !== 'string'
  ) {
    return null;
  }
  try {
    const privateKey = createPrivateKey({ key: record, format: 'jwk' } as never);
    return {
      privateKey,
      publicJwk: { kty: 'EC', crv: 'P-256', x: record.x, y: record.y },
    };
  } catch {
    return null;
  }
}

function readKeyFile(file: string): DpopKey | null {
  try {
    return keyFromRecord(JSON.parse(readFileSync(file, 'utf8')) as Record<string, unknown>);
  } catch {
    return null;
  }
}

function generateRecord(): { key: DpopKey; record: Record<string, unknown> } {
  const { privateKey, publicKey } = generateKeyPairSync('ec', { namedCurve: 'P-256' });
  const priv = privateKey.export({ format: 'jwk' }) as Record<string, unknown>;
  const pub = publicKey.export({ format: 'jwk' }) as Record<string, unknown>;
  const record = { kty: pub.kty, crv: pub.crv, x: pub.x, y: pub.y, d: priv.d };
  const key: DpopKey = {
    privateKey,
    publicJwk: { kty: 'EC', crv: 'P-256', x: String(pub.x), y: String(pub.y) },
  };
  return { key, record };
}

export function getDpopKey(envVar = 'CUSTOMER_DPOP_KEY_FILE'): DpopKey {
  const file = defaultKeyFile(envVar);
  const existingMem = cachedByFile.get(file);
  if (existingMem) return existingMem;

  const existingDisk = readKeyFile(file);
  if (existingDisk) {
    cachedByFile.set(file, existingDisk);
    return existingDisk;
  }

  const { key, record } = generateRecord();
  const publish = (exclusive: boolean): boolean => {
    try {
      mkdirSync(path.dirname(file), { recursive: true });
      writeFileSync(file, JSON.stringify(record), { flag: exclusive ? 'wx' : 'w', mode: 0o600 });
      return true;
    } catch {
      return false;
    }
  };

  if (publish(true)) {
    cachedByFile.set(file, key);
    return key;
  }

  const winner = readKeyFile(file);
  if (winner) {
    cachedByFile.set(file, winner);
    return winner;
  }

  if (publish(false)) {
    cachedByFile.set(file, key);
    return key;
  }

  cachedByFile.set(file, key);
  return key;
}

export function dpopPublicJwk(envVar = 'CUSTOMER_DPOP_KEY_FILE'): Record<string, string> {
  return { ...getDpopKey(envVar).publicJwk };
}

function base64url(input: string | Buffer): string {
  return Buffer.from(input).toString('base64url');
}

export function dpopProof(
  htm: string,
  htu: string,
  accessToken?: string,
  envVar = 'CUSTOMER_DPOP_KEY_FILE',
): string {
  const key = getDpopKey(envVar);
  const target = new URL(htu);
  target.search = '';
  target.hash = '';

  const header = { typ: 'DPoP', alg: 'ES256', jwk: key.publicJwk };
  const payload: Record<string, string | number> = {
    htm: htm.toUpperCase(),
    htu: target.toString(),
    iat: Math.floor(Date.now() / 1000),
    jti: randomUUID(),
  };
  if (accessToken) {
    payload.ath = createHash('sha256').update(accessToken).digest('base64url');
  }

  const signingInput = `${base64url(JSON.stringify(header))}.${base64url(JSON.stringify(payload))}`;
  const signature = sign('sha256', Buffer.from(signingInput), {
    key: key.privateKey,
    dsaEncoding: 'ieee-p1363',
  });

  return `${signingInput}.${base64url(signature)}`;
}

