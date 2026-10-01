import { test, expect } from 'vitest';
import { MemoryDocumentStore, MemoryUploadUrls } from '@navdhya/partner/src/adapters/memory-documents';
import { verifyDocument } from '@navdhya/partner/src/application/verify-document';
import { ApplicationDocument } from '@navdhya/partner/src/onboarding/documents';

const NOW = '2026-10-01T00:00:00.000Z';
const doc = (over: Partial<ApplicationDocument> = {}): ApplicationDocument => ({
  id: 'doc_1',
  applicationId: 'app_1',
  kind: 'id-proof',
  status: 'PENDING',
  objectKey: 'onboarding/app_1/id-proof/abc',
  sha256: 'abc',
  createdAtIso: NOW,
  ...over,
});
const evidence = (over: Record<string, unknown> = {}) => ({
  mime: 'image/jpeg',
  sizeBytes: 1024,
  sha256: 'abc',
  ...over,
});

test('verification accepts clean evidence, rejects each failure with a code', () => {
  const good = verifyDocument(doc(), evidence(), NOW);
  expect(good.ok).toBe(true);
  if (good.ok) expect(good.document.status).toBe('VERIFIED');

  for (const [mut, reason] of [
    [{ mime: 'video/mp4' }, 'MIME_NOT_ALLOWED'],
    [{ sizeBytes: 0 }, 'TOO_LARGE'],
    [{ sizeBytes: 11 * 1024 * 1024 }, 'TOO_LARGE'],
    [{ sha256: 'other' }, 'HASH_MISMATCH'],
    [{ expired: true }, 'EXPIRED_EVIDENCE'],
  ] as const) {
    const r = verifyDocument(doc(), evidence(mut), NOW);
    expect(r.ok).toBe(false);
    if (!r.ok) {
      expect(r.reason).toBe(reason);
      expect(r.document.status).toBe('REJECTED');
      expect(r.document.reasonCode).toBe(reason);
    }
  }
  // Photo kind rejects PDFs; non-pending docs throw instead of transitioning.
  const photo = verifyDocument(doc({ kind: 'photo' }), evidence({ mime: 'application/pdf' }), NOW);
  expect(photo.ok).toBe(false);
  expect(() => verifyDocument(doc({ status: 'VERIFIED' }), evidence(), NOW)).toThrow('DOCUMENT_NOT_PENDING');
});

test('document store isolates copies; upload issuance scopes keys', async () => {
  const store = new MemoryDocumentStore();
  await store.save(doc());
  await expect(store.save(doc())).rejects.toThrow('DOCUMENT_EXISTS:doc_1');
  expect((await store.findByApplication('app_1')).map((d) => d.id)).toEqual(['doc_1']);
  await expect(store.update(doc({ id: 'ghost' }))).rejects.toThrow('DOCUMENT_NOT_FOUND:ghost');

  const urls = new MemoryUploadUrls();
  const issued = await urls.issue({ applicationId: 'app_1', kind: 'id-proof', contentLength: 100, sha256: 'abc', nowIso: NOW });
  expect(issued.objectKey).toBe('onboarding/app_1/id-proof/abc');
  expect(issued.expiresAtIso).toBe('2026-10-01T00:15:00.000Z');
  await expect(urls.issue({ applicationId: 'a', kind: 'k', contentLength: 0, sha256: 'x', nowIso: NOW })).rejects.toThrow('UPLOAD_EMPTY');
});
