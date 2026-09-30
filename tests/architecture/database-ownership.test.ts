import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';

const packagesDir = path.join(process.cwd(), 'packages');

// Logical table ownership (§5.16). One capability owns mutation of its tables.
const OWNERSHIP: Record<string, string[]> = {
  user: ['users', 'profiles'],
  puja: ['pujas', 'categories', 'rituals'],
  pandit: ['pandits', 'verification', 'expertise'],
  availability: ['slots', 'schedules', 'capacity'],
  pricing: ['prices', 'rules', 'discounts'],
  booking: ['bookings', 'booking_items', 'booking_status_history'],
  search: [],
  recommendations: [],
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

test('only adapters may reference persistence internals', () => {
  const BUSINESS = Object.keys(OWNERSHIP);
  for (const cap of BUSINESS) {
    for (const layer of ['domain', 'application', 'contracts', 'ports']) {
      const dir = path.join(packagesDir, cap, 'src', layer);
      for (const file of getAllFiles(dir)) {
        const content = fs.readFileSync(file, 'utf8');
        expect(content, `${cap}/${layer} must not contain SQL in ${file}`).not.toMatch(/SELECT\s+.*\s+FROM|INSERT\s+INTO|UPDATE\s+\w+\s+SET/i);
        expect(content, `${cap}/${layer} must not import orm in ${file}`).not.toMatch(/from\s+["'](drizzle-orm|@prisma\/client|kysely|typeorm)["']/);
      }
    }
  }
});

test('each capability documents its persistence ownership', () => {
  for (const [cap, tables] of Object.entries(OWNERSHIP)) {
    const readme = path.join(packagesDir, cap, 'README.md');
    expect(fs.existsSync(readme), `packages/${cap}/README.md must exist`).toBe(true);
    const content = fs.readFileSync(readme, 'utf8');
    expect(content, `packages/${cap}/README.md must document ownership`).toMatch(/Owns|Ownership/i);
    for (const table of tables) {
      expect(content, `packages/${cap}/README.md must list table ${table}`).toMatch(new RegExp(table.replace(/_/g, '[_ ]?')));
    }
  }
});
