import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';

function getAllFiles(dir: string): string[] {
  let results: string[] = [];
  if (!fs.existsSync(dir)) return results;
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, e.name);
    if (e.isDirectory()) {
      if (e.name === 'node_modules' || e.name === '.next') continue;
      results = results.concat(getAllFiles(full));
    } else if (e.name.endsWith('.ts') || e.name.endsWith('.tsx')) results.push(full);
  }
  return results;
}

test('partner never owns scheduling/fulfillment/settlement', () => {
  const dir = path.join(process.cwd(), 'packages', 'partner', 'src');
  for (const file of getAllFiles(dir)) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no scheduling ownership in ${file}`).not.toMatch(/commitSchedule|ScheduleAssignment/);
    expect(content, `no settlement in ${file}`).not.toMatch(/payable|ledger|payout/);
    expect(content, `no react in ${file}`).not.toMatch(/from\s+["']react["']/);
    expect(content, `no sdk in ${file}`).not.toMatch(/twilio|razorpay|stripe|nodemailer/);
  }
});

test('partner app is task-oriented with no cross-app imports', () => {
  for (const file of getAllFiles(path.join(process.cwd(), 'apps', 'partner'))) {
    const content = fs.readFileSync(file, 'utf8');
    expect(content, `no cross-app in ${file}`).not.toMatch(/apps\/(web|customer)/);
    expect(content, `no raw db in ${file}`).not.toMatch(/SELECT\s+.*FROM|drizzle-orm|@prisma\/client/);
  }
});
