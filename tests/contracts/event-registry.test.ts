import { test, expect } from 'vitest';
import {
  registerEvent,
  getEventVersions,
  latestEventVersion,
  assertConsumerCompatible,
  __clearRegistryForTests,
} from '@navdhya/contracts/src/events/registry';

test('registry versions events and guards compatibility', () => {
  __clearRegistryForTests();
  registerEvent({ eventType: 'BookingCreated', version: 1, producer: 'booking', schemaHash: 'sha:aaa', consumers: ['fulfillment'] });
  registerEvent({ eventType: 'BookingCreated', version: 2, producer: 'booking', schemaHash: 'sha:bbb', consumers: ['fulfillment', 'analytics'] });
  expect(getEventVersions('BookingCreated').map((v) => v.version)).toEqual([1, 2]);
  expect(latestEventVersion('BookingCreated')?.schemaHash).toBe('sha:bbb');
  expect(() => assertConsumerCompatible('BookingCreated', 2)).not.toThrow();
  expect(() => assertConsumerCompatible('BookingCreated', 3)).toThrow('EVENT_VERSION_TOO_OLD');
  expect(() => assertConsumerCompatible('Nope', 1)).toThrow('EVENT_UNREGISTERED');
  expect(() =>
    registerEvent({ eventType: 'BookingCreated', version: 2, producer: 'booking', schemaHash: 'x', consumers: [] }),
  ).toThrow('EVENT_VERSION_CONFLICT');
  expect(() =>
    registerEvent({ eventType: '', version: 1, producer: 'booking', schemaHash: 'x', consumers: [] }),
  ).toThrow('EVENT_REGISTRATION_INVALID');
});
