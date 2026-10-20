import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';

const adsDir = path.join(process.cwd(), 'packages', 'ads', 'src');
const CORE_LAYERS = ['domain', 'contracts', 'ports', 'decision', 'policies', 'application'];

function getAllFiles(dir: string): string[] {
  let results: string[] = [];
  if (!fs.existsSync(dir)) return results;
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, e.name);
    if (e.isDirectory()) results = results.concat(getAllFiles(full));
    else if (e.name.endsWith('.ts')) results.push(full);
  }
  return results;
}

test('ads core never imports booking/payment/pricing or Next/React', () => {
  const banned = ['@navdhya/booking', '@navdhya/payment', 'booking/', 'payment', 'pricing', 'next/', 'next"', 'from "react"', "from 'react'"];
  for (const layer of CORE_LAYERS) {
    for (const file of getAllFiles(path.join(adsDir, layer))) {
      const content = fs.readFileSync(file, 'utf8');
      for (const b of banned) expect(content, `no ${b} in ${file}`).not.toContain(b);
    }
  }
});

test('ads core never imports provider SDKs or raw user records', () => {
  const sdks = ['pg', 'drizzle-orm', 'ioredis', 'razorpay', 'stripe', 'aws-sdk'];
  for (const layer of CORE_LAYERS) {
    for (const file of getAllFiles(path.join(adsDir, layer))) {
      const content = fs.readFileSync(file, 'utf8');
      for (const sdk of sdks) {
        expect(content, `no ${sdk} in ${file}`).not.toMatch(new RegExp(`from\\s+["']${sdk}["']`));
      }
      expect(content, `no process.env in ${file}`).not.toContain('process.env');
    }
  }
});

test('ads persistence owned: README documents tables', () => {
  const readme = fs.readFileSync(path.join(process.cwd(), 'packages', 'ads', 'README.md'), 'utf8');
  for (const t of ['campaigns', 'creatives', 'placements', 'frequency_state', 'ad_events']) {
    expect(readme).toMatch(new RegExp(t));
  }
});
