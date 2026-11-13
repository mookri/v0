import { describe, expect, it } from 'vitest';
import {
  MIN_COURTYARD_TOUCH_TARGET_PX,
  SACRED_FOCUS_RING_CLASS,
  auditPageLandmarks,
  buildLiveAnnouncement,
  getNextRovingIndex,
} from './index';

describe('@navdhya/accessibility helpers (T-AP06-02)', () => {
  it('enforces 48px courtyard target size and Sacred Ember focus ring tokens', () => {
    expect(MIN_COURTYARD_TOUCH_TARGET_PX).toBeGreaterThanOrEqual(48);
    expect(SACRED_FOCUS_RING_CLASS).toContain('#F16A24');
    expect(SACRED_FOCUS_RING_CLASS).toContain('#FFF7ED');
  });

  it('builds polite and assertive ARIA live announcements', () => {
    expect(buildLiveAnnouncement('  Saved booking  ', 'polite')).toEqual({
      role: 'status',
      'aria-live': 'polite',
      'aria-atomic': 'true',
      message: 'Saved booking',
    });
    expect(buildLiveAnnouncement('Conflict detected', 'assertive')).toEqual({
      role: 'alert',
      'aria-live': 'assertive',
      'aria-atomic': 'true',
      message: 'Conflict detected',
    });
  });

  it('computes circular roving tabindex navigation across keyboard keys', () => {
    expect(getNextRovingIndex(2, 3, 'ArrowDown')).toBe(0);
    expect(getNextRovingIndex(0, 3, 'ArrowUp')).toBe(2);
    expect(getNextRovingIndex(1, 3, 'Home')).toBe(0);
    expect(getNextRovingIndex(0, 3, 'End')).toBe(2);
  });

  it('flags missing main landmark, skip link, or skipped heading hierarchy', () => {
    expect(auditPageLandmarks({ hasMainLandmark: true, hasSkipLink: true, headingLevels: [1, 2, 3] })).toEqual([]);
    expect(auditPageLandmarks({ hasMainLandmark: false, hasSkipLink: false, headingLevels: [1, 3] })).toEqual([
      'missing_main_landmark',
      'missing_skip_link',
      'skipped_heading_level_1_to_3',
    ]);
  });
});

