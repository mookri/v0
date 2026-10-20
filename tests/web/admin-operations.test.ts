import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';

// Phase-28 stance lock: admin stays web-only and never indexed. If admin
// ever moves origins, this test forces the runbook to move with it.
test('admin layout opts out of indexing', () => {
  const layout = fs.readFileSync(
    path.join(process.cwd(), 'apps', 'web', 'app', 'admin', 'layout.tsx'),
    'utf8',
  );
  expect(layout).toMatch(/index:\s*false/);
  expect(layout).toMatch(/follow:\s*false/);
});
