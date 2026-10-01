import { test, expect } from 'vitest';
import { MemoryOnboardingRepository } from '@navdhya/partner/src/adapters/memory-applications';
import { PartnerApplication } from '@navdhya/partner/src/domain/partner-application';

const app = (over: Partial<PartnerApplication> = {}): PartnerApplication => ({
  id: 'app_1',
  identityId: 'u1',
  type: 'PANDIT',
  track: 'pandit',
  trackVersion: 'pandit-v1',
  status: 'DRAFT',
  ...over,
});

test('repository persists, isolates copies, and enforces identity scope', async () => {
  const repo = new MemoryOnboardingRepository();
  await repo.save(app());
  await expect(repo.save(app())).rejects.toThrow('APPLICATION_EXISTS:app_1');
  await repo.save(app({ id: 'app_2', identityId: 'u2', status: 'SUBMITTED' }));
  expect((await repo.findById('app_1'))?.status).toBe('DRAFT');
  expect(await repo.findById('nope')).toBeNull();
  expect((await repo.findByIdentity('u1')).map((a) => a.id)).toEqual(['app_1']);
  expect(await repo.findByIdentity('ghost')).toEqual([]);
  // Stored copies are isolated from caller mutation.
  const fetched = (await repo.findById('app_1'))!;
  fetched.status = 'APPROVED';
  expect((await repo.findById('app_1'))?.status).toBe('DRAFT');
  await repo.update({ ...app(), status: 'SUBMITTED', submittedAt: '2026-10-01T00:00:00.000Z' });
  expect((await repo.findById('app_1'))?.status).toBe('SUBMITTED');
  await expect(repo.update(app({ id: 'ghost' }))).rejects.toThrow('APPLICATION_NOT_FOUND:ghost');
});

test('supersedes linkage survives the round trip', async () => {
  const repo = new MemoryOnboardingRepository();
  await repo.save(app({ id: 'old', status: 'REJECTED', rejectionCode: 'OTHER' }));
  await repo.save(app({ id: 'new', status: 'DRAFT', supersedes: 'old' }));
  expect((await repo.findById('new'))?.supersedes).toBe('old');
});
