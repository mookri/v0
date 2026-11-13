export const MIN_STANDARD_TOUCH_TARGET_PX = 44;
export const MIN_COURTYARD_TOUCH_TARGET_PX = 48;

export const SACRED_FOCUS_RING_CLASS =
  'focus:outline-none focus-visible:ring-2 focus-visible:ring-[#F16A24] focus-visible:ring-offset-2 focus-visible:ring-offset-[#FFF7ED]';

export const SKIP_LINK_CLASS =
  'sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-[#2C2033] focus:px-4 focus:py-3 focus:text-sm focus:font-semibold focus:text-[#FFF7ED]';

export interface LiveAnnouncement {
  role: 'status' | 'alert';
  'aria-live': 'polite' | 'assertive';
  'aria-atomic': 'true';
  message: string;
}

export function buildLiveAnnouncement(
  message: string,
  politeness: 'polite' | 'assertive' = 'polite',
): LiveAnnouncement {
  return {
    role: politeness === 'assertive' ? 'alert' : 'status',
    'aria-live': politeness,
    'aria-atomic': 'true',
    message: message.trim(),
  };
}

export function getNextRovingIndex(
  currentIndex: number,
  total: number,
  key: 'ArrowDown' | 'ArrowUp' | 'ArrowRight' | 'ArrowLeft' | 'Home' | 'End',
): number {
  if (total <= 0) return -1;
  if (key === 'Home') return 0;
  if (key === 'End') return total - 1;
  if (key === 'ArrowDown' || key === 'ArrowRight') {
    return (currentIndex + 1) % total;
  }
  return (currentIndex - 1 + total) % total;
}

export interface LandmarkAuditInput {
  hasMainLandmark: boolean;
  hasSkipLink: boolean;
  headingLevels: number[];
}

export function auditPageLandmarks(input: LandmarkAuditInput): string[] {
  const issues: string[] = [];
  if (!input.hasMainLandmark) issues.push('missing_main_landmark');
  if (!input.hasSkipLink) issues.push('missing_skip_link');
  if (input.headingLevels.length === 0 || input.headingLevels[0] !== 1) {
    issues.push('missing_h1_first');
  }
  for (let i = 1; i < input.headingLevels.length; i++) {
    if (input.headingLevels[i] - input.headingLevels[i - 1] > 1) {
      issues.push(`skipped_heading_level_${input.headingLevels[i - 1]}_to_${input.headingLevels[i]}`);
    }
  }
  return issues;
}

