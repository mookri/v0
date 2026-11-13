import { describe, expect, test } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';
import {
  BOOKING_CANCELLABLE_STATUSES,
  BOOKING_LEGAL_TRANSITIONS,
  BOOKING_RESCHEDULABLE_STATUSES,
  BOOKING_SECTIONS,
  BOOKING_STATUSES,
  BOOKING_TERMINAL_STATUSES,
  type BookingStatus,
} from '@navdhya/contracts/src/generated/booking-lifecycle';
import { canCancel, canReschedule, canTransition } from '@navdhya/booking/src/domain/booking-rules';
import { bookingSection } from '@navdhya/booking/src/ui/booking-display';

const ROOT = process.cwd();

describe('BookingLifecycle single source of truth parity (A-14 / T-AP01-02)', () => {
  const phpPath = path.join(ROOT, 'platform-navdhya/app/Modules/Booking/Domain/BookingLifecycle.php');
  const jsonPath = path.join(ROOT, 'packages/contracts/src/generated/booking-lifecycle.json');
  const phpSource = fs.readFileSync(phpPath, 'utf8');
  const exportedJson = JSON.parse(fs.readFileSync(jsonPath, 'utf8')) as {
    statuses: BookingStatus[];
    transitions: Record<BookingStatus, BookingStatus[]>;
    cancellable: BookingStatus[];
    reschedulable: BookingStatus[];
    sections: Record<string, BookingStatus[]>;
    terminal: BookingStatus[];
  };

  test('every PHP BookingLifecycle::STATUS_* constant is exported identically', () => {
    const phpStatuses = [...phpSource.matchAll(/public const STATUS_\w+\s*=\s*'([A-Z_]+)'/g)].map(
      (m) => m[1],
    );
    expect(phpStatuses.length).toBeGreaterThanOrEqual(12);
    expect(BOOKING_STATUSES).toEqual(phpStatuses);
    expect(exportedJson.statuses).toEqual(phpStatuses);
    expect(BOOKING_STATUSES).toContain('NO_SHOW');
  });

  test('generated TS transitions, cancellable, reschedulable, sections, terminal match JSON snapshot 1:1', () => {
    expect(BOOKING_LEGAL_TRANSITIONS).toEqual(exportedJson.transitions);
    expect([...BOOKING_CANCELLABLE_STATUSES]).toEqual(exportedJson.cancellable);
    expect([...BOOKING_RESCHEDULABLE_STATUSES]).toEqual(exportedJson.reschedulable);
    expect(BOOKING_SECTIONS).toEqual(exportedJson.sections);
    expect([...BOOKING_TERMINAL_STATUSES]).toEqual(exportedJson.terminal);
  });

  test('@navdhya/booking rules and display sections match backend BookingLifecycle across all 144 state pairs', () => {
    for (const from of BOOKING_STATUSES) {
      for (const to of BOOKING_STATUSES) {
        const expected = exportedJson.transitions[from].includes(to);
        expect(canTransition(from, to), `${from} -> ${to}`).toBe(expected);
      }

      const stubBooking = { status: from } as Parameters<typeof canCancel>[0];
      expect(canCancel(stubBooking), `canCancel(${from})`).toBe(exportedJson.cancellable.includes(from));
      expect(canReschedule(stubBooking), `canReschedule(${from})`).toBe(
        exportedJson.reschedulable.includes(from),
      );

      const expectedSection = Object.entries(exportedJson.sections).find(([, list]) =>
        list.includes(from),
      )?.[0];
      expect(bookingSection(from), `bookingSection(${from})`).toBe(expectedSection);
    }
  });
});

