import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';

// Multilingual stance: applicant-facing onboarding UI renders strings from
// the dictionary only. Raw JSX text nodes (capitalized prose) fail here —
// add a key to presentation/onboarding/strings.ts (en+hi) instead.
// Identifiers, symbols, and single characters are ignored.
const SCAN_DIRS = [
  path.join(process.cwd(), 'apps', 'partner', 'app', 'onboarding'),
  path.join(process.cwd(), 'apps', 'partner', 'presentation', 'onboarding'),
];

const RAW_TEXT = />([A-Z][^<>{}]{2,})</g;

function getAllFiles(dir: string): string[] {
  if (!fs.existsSync(dir)) return [];
  let out: string[] = [];
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, e.name);
    if (e.isDirectory()) out = out.concat(getAllFiles(full));
    else if (e.name.endsWith('.tsx')) out.push(full);
  }
  return out;
}

test('onboarding UI has no hardcoded prose (en+hi dictionary only)', { timeout: 60000 }, () => {
  const violations: string[] = [];
  for (const dir of SCAN_DIRS) {
    for (const file of getAllFiles(dir)) {
      const content = fs.readFileSync(file, 'utf8');
      let m: RegExpExecArray | null;
      RAW_TEXT.lastIndex = 0;
      while ((m = RAW_TEXT.exec(content)) !== null) violations.push(`${file}: ${m[1]!.trim()}`);
    }
  }
  expect(violations).toEqual([]);
});
