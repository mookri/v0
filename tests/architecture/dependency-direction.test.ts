import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';

const packagesDir = path.join(process.cwd(), 'packages');

// Allowed consumption graph (§5.31). Empty = foundation only.
// Keys are consumers, values are allowed providers (public @navdhya/* imports).
const ALLOWED: Record<string, string[]> = {
  user: [],
  puja: [],
  pandit: ['user'],
  availability: ['pandit', 'puja'],
  pricing: ['puja'],
  booking: ['user', 'puja', 'pandit', 'availability', 'pricing'],
  search: ['puja', 'pandit'],
  recommendations: ['user', 'puja', 'pandit', 'booking'],
};

function getAllFiles(dir: string): string[] {
  let results: string[] = [];
  if (!fs.existsSync(dir)) return results;
  const list = fs.readdirSync(dir, { withFileTypes: true });
  for (const file of list) {
    const fullPath = path.join(dir, file.name);
    if (file.isDirectory()) results = results.concat(getAllFiles(fullPath));
    else if (file.name.endsWith('.ts') || file.name.endsWith('.tsx')) results.push(fullPath);
  }
  return results;
}

test('capability dependencies stay within allowed graph', () => {
  for (const [consumer, allowed] of Object.entries(ALLOWED)) {
    const srcDir = path.join(packagesDir, consumer, 'src');
    for (const file of getAllFiles(srcDir)) {
      const content = fs.readFileSync(file, 'utf8');
      const importRe = /from\s+["'](@navdhya\/[^"'\/]+)(?:\/[^"']*)?["']/g;
      let m: RegExpExecArray | null;
      while ((m = importRe.exec(content)) !== null) {
        const depPkg = m[1].replace('@navdhya/', '');
        // contracts is foundation, always allowed
        if (depPkg === 'contracts') continue;
        // self-import is fine (should not happen, but allow)
        if (depPkg === consumer) continue;
        expect(
          allowed,
          `${consumer} imports disallowed capability @navdhya/${depPkg} in ${file}. Allowed: [${allowed.join(', ')}]`,
        ).toContain(depPkg);
      }
    }
  }
});

test('no cycles: providers must not import their consumers', () => {
  // Build reverse map: for each pair A->B allowed, B must NOT import A
  // unless the reverse is also explicitly allowed.
  for (const [consumer, allowed] of Object.entries(ALLOWED)) {
    for (const provider of allowed) {
      const reverseAllowed = ALLOWED[provider] ?? [];
      if (reverseAllowed.includes(consumer)) continue; // mutual allowed, skip
      const providerDir = path.join(packagesDir, provider, 'src');
      for (const file of getAllFiles(providerDir)) {
        const content = fs.readFileSync(file, 'utf8');
        const pattern = new RegExp(`from\\s+["']@navdhya/${consumer}["']`);
        expect(content, `${provider} must not import consumer ${consumer} in ${file} (would create cycle)`).not.toMatch(pattern);
      }
    }
  }
});
