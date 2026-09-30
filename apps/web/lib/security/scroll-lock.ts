// lib/security/scroll-lock.ts
// Reference-counted body scroll lock.
//
// Why:
//   The old `AuthSplitLayout` saved `document.body.style.overflow`
//   and restored it on unmount. If two layouts mounted concurrently
//   (e.g. a modal + a page, or two routes briefly overlapping during
//   fast navigation), each one captured the same already-overwritten
//   value (`''` or `'hidden'`). When the inner one unmounted, it
//   restored `''` — but the outer one was still mounted and wanted
//   `'hidden'`. Net effect: the lock leaked (page became scrollable
//   while a layout was still open) OR worse, an outer lock was
//   released by an inner unmount.
//
// Fix:
//   Reference-count lockers. The lock is engaged while at least one
//   caller is active; we only write to `document.body.style.overflow`
//   on the first acquire and the last release. Original overflow
//   value is preserved in the closure of the first acquirer.
//
// SSR-safe: no-op when `document` is undefined.

let lockCount = 0;
let originalOverflow: string | null = null;
let originalMaxHeight: string | null = null;
let originalHtmlOverflow: string | null = null;
let originalHtmlMaxHeight: string | null = null;

export interface ScrollLockHandle {
  release: () => void;
}

export interface AcquireOptions {
  /**
   * When true, also lock the <html> element (used by layouts that
   * set `overflow: hidden` on both). Default: false (body only).
   */
  lockHtml?: boolean;
  /**
   * When true, also set `max-height: 100vh` to prevent the body
   * from scrolling. Default: true.
   */
  capHeight?: boolean;
}

export function acquireScrollLock(options: AcquireOptions = {}): ScrollLockHandle {
  const { lockHtml = false, capHeight = true } = options;
  if (typeof document === "undefined") {
    return { release: () => {} };
  }

  if (lockCount === 0) {
    // First acquirer — capture original values before we mutate.
    originalOverflow = document.body.style.overflow;
    originalMaxHeight = document.body.style.maxHeight;
    originalHtmlOverflow = document.documentElement.style.overflow;
    originalHtmlMaxHeight = document.documentElement.style.maxHeight;
    document.body.style.overflow = "hidden";
    if (capHeight) document.body.style.maxHeight = "100vh";
    if (lockHtml) {
      document.documentElement.style.overflow = "hidden";
      if (capHeight) document.documentElement.style.maxHeight = "100vh";
    }
  }
  lockCount++;

  let released = false;
  return {
    release: () => {
      if (released) return;
      released = true;
      lockCount = Math.max(0, lockCount - 1);
      if (lockCount === 0 && originalOverflow !== null) {
        document.body.style.overflow = originalOverflow;
        document.body.style.maxHeight = originalMaxHeight ?? "";
        if (lockHtml) {
          document.documentElement.style.overflow = originalHtmlOverflow ?? "";
          document.documentElement.style.maxHeight = originalHtmlMaxHeight ?? "";
        }
        originalOverflow = null;
        originalMaxHeight = null;
        originalHtmlOverflow = null;
        originalHtmlMaxHeight = null;
      }
    },
  };
}

/** Test helper: reset module state. */
export function _resetScrollLockForTests(): void {
  lockCount = 0;
  originalOverflow = null;
  originalMaxHeight = null;
  originalHtmlOverflow = null;
  originalHtmlMaxHeight = null;
}
