// packages/analytics/src/registry/__tests__/generate.test.ts
// B1 acceptance: generator idempotency — regenerate produces ZERO drift
// against the committed generated/ output, and is deterministic.

import * as fs from 'node:fs';
import * as path from 'node:path';
import { describe, expect, it } from 'vitest';
import { EVENTS } from '@navdhya/analytics/src/registry/events/index';
import {
  GENERATED_DIR,
  GENERATED_FILES,
  buildBaseline,
  checkGenerated,
  generateAll,
} from '@navdhya/analytics/src/registry/generate';

describe('registry generator', () => {
  it('committed generated/ output matches registry (zero drift)', () => {
    expect(checkGenerated(EVENTS)).toEqual([]);
  });

  it('is deterministic — two runs produce identical output', () => {
    const a = generateAll(EVENTS);
    const b = generateAll(EVENTS);
    expect([...a.keys()].sort()).toEqual([...b.keys()].sort());
    for (const [file, content] of a) {
      expect(b.get(file)).toBe(content);
    }
  });

  it('emits every declared generated file', () => {
    const produced = generateAll(EVENTS);
    for (const f of GENERATED_FILES) {
      expect(produced.has(f)).toBe(true);
      expect(fs.existsSync(path.join(GENERATED_DIR, f))).toBe(true);
    }
  });

  it('generated event-types covers every registered event with matching names', () => {
    const types = generateAll(EVENTS).get('event-types.ts')!;
    for (const e of EVENTS) {
      expect(types).toContain(`'${e.name}'`);
      expect(types).toContain(`${e.name}: {`);
    }
  });

  it('baseline is stable and versioned per event', () => {
    const baseline = buildBaseline(EVENTS);
    expect(Object.keys(baseline.events).sort()).toEqual(EVENTS.map((e) => e.name).sort());
    for (const e of EVENTS) {
      expect(baseline.events[e.name]!.version).toBe(e.version);
      expect(Object.keys(baseline.events[e.name]!.params).sort()).toEqual(
        e.params.map((p) => p.name).sort(),
      );
    }
  });
});
