// Crash fingerprinting (plan B8, crash subsystem): groups the same logical
// failure across MULTIPLE BUILDS into one error group, and separates two
// distinct failures — the evaluation criterion, fixture-tested.
//
// Method (plan wording): normalized error class + selected stack frames,
// with build hashes and framework frames STRIPPED before hashing. The
// message is deliberately NOT part of the fingerprint (plan specifies class
// + frames); it stays available for display, redacted by redact.ts.
//
// Honesty notes:
//   - First-party web instrumentation only. This normalizer consumes what a
//     browser/Node surface actually reports (V8-style stacks); native app
//     crashes never reach it.
//   - Fingerprint hashing is a non-crypto FNV-1a variant (4 lanes → 128
//     bits): deterministic, dependency-free, adequate for group keys — it
//     is NOT a security primitive.

export interface StackFrame {
  file: string;
  fn: string;
  line: number | null;
}

export interface NormalizedError {
  errorClass: string;
  message: string;
  frames: StackFrame[];
}

/** Frames hashed into the fingerprint (top of the app stack). */
export const FINGERPRINT_FRAMES = 5;
/** Hard cap on parsed frames (payload bound — a 10k-frame stack is an
 *  attack, not a bug report). */
export const MAX_PARSED_FRAMES = 30;

/** `main.9f8e7d6a.js`, `login-a1b2c3d4e5.js`, `vendor.5c9f8e7d6a1b.chunk.js`
 *  → `<name>.<hash>.js` — build artifacts vary per deploy; the SOURCE does
 *  not, so stripping them is what makes cross-build grouping work. */
const BUILD_HASH_RE = /[0-9a-f]{8,20}/gi;

export function stripBuildArtifacts(file: string): string {
  return file.replace(BUILD_HASH_RE, '<hash>');
}

/** Framework/vendor frames change independently of app logic and would
 *  explode every error into its own group (or glue unrelated errors
 *  together at the framework boundary). */
const FRAMEWORK_RE =
  /node_modules|next[\\/]dist|react-dom?|webpack-runtime|webpack\/runtime|\/vendor\/|\/_next\/static\/chunks\/framework|\.min\.js$/i;

export function isFrameworkFrame(file: string): boolean {
  return FRAMEWORK_RE.test(file);
}

// V8 styles: "    at fn (file:line:col)" / "    at file:line:col"
const FRAME_WITH_FN = /^\s*at\s+(.+?)\s+\((.+?):(\d+):(\d+)\)\s*$/;
const FRAME_BARE = /^\s*at\s+(.+?):(\d+):(\d+)\s*$/;

export function parseStack(stack: string): StackFrame[] {
  const frames: StackFrame[] = [];
  for (const line of stack.split('\n')) {
    if (frames.length >= MAX_PARSED_FRAMES) break;
    const withFn = FRAME_WITH_FN.exec(line);
    if (withFn) {
      frames.push({ fn: withFn[1]!, file: withFn[2]!, line: Number(withFn[3]) });
      continue;
    }
    const bare = FRAME_BARE.exec(line);
    if (bare) {
      frames.push({ fn: '(anonymous)', file: bare[1]!, line: Number(bare[2]) });
    }
  }
  return frames;
}

export interface NormalizerInput {
  name?: string;
  message: string;
  stack?: string;
}

export function normalizeError(input: NormalizerInput): NormalizedError {
  const errorClass = (input.name ?? 'Error').trim() || 'Error';
  const frames = (input.stack ? parseStack(input.stack) : []).filter(
    (frame) => !isFrameworkFrame(frame.file),
  );
  return { errorClass, message: input.message, frames: frames.slice(0, MAX_PARSED_FRAMES) };
}

const FNV_OFFSET = 0x811c9dc5;
const FNV_PRIME = 0x01000193;

function fnv1a32(text: string, seed: number): number {
  let hash = seed >>> 0;
  for (let index = 0; index < text.length; index += 1) {
    hash ^= text.charCodeAt(index);
    hash = Math.imul(hash, FNV_PRIME) >>> 0;
  }
  return hash >>> 0;
}

/** Stable 32-hex-char group key from error class + top app frames. */
export function fingerprintError(normalized: NormalizedError): string {
  const frames = normalized.frames
    .slice(0, FINGERPRINT_FRAMES)
    .map((frame) => `${stripBuildArtifacts(frame.file)}:${frame.fn}:${frame.line ?? ''}`)
    .join('|');
  const material = `${normalized.errorClass}|${frames}`;
  // 4 FNV lanes with distinct offsets ≈ 128-bit key; adequate group space.
  const lanes = [0x811c9dc5, 0x9e3779b9, 0x85ebca6b, 0xc2b2ae35].map((offset) =>
    fnv1a32(material, offset),
  );
  return lanes.map((lane) => lane.toString(16).padStart(8, '0')).join('');
}
