import { test, expect } from 'vitest';
import * as fs from 'fs';
import * as path from 'path';

const packagesDir = path.join(process.cwd(), 'packages');
const appsDir = path.join(process.cwd(), 'apps');

const BUSINESS_CAPABILITIES = ['user', 'puja', 'pandit', 'availability', 'pricing', 'booking', 'search', 'recommendations'];

// Helper to recursively get all files in a dir
function getAllFiles(dir: string): string[] {
  let results: string[] = [];
  if (!fs.existsSync(dir)) return results;
  const list = fs.readdirSync(dir, { withFileTypes: true });
  for (const file of list) {
    const fullPath = path.join(dir, file.name);
    if (file.isDirectory()) {
      results = results.concat(getAllFiles(fullPath));
    } else if (file.name.endsWith('.ts') || file.name.endsWith('.tsx')) {
      results.push(fullPath);
    }
  }
  return results;
}

test('domain must not import from Next.js, React, or infrastructure', () => {
  for (const cap of BUSINESS_CAPABILITIES) {
    const domainDir = path.join(packagesDir, cap, 'src', 'domain');
    const files = getAllFiles(domainDir);
    
    for (const file of files) {
      const content = fs.readFileSync(file, 'utf8');
      
      // Domain should not know about React or Next.js
      expect(content, `${cap}/domain must not import react in ${file}`).not.toMatch(/from\s+["']react["']/);
      expect(content, `${cap}/domain must not import next in ${file}`).not.toMatch(/from\s+["']next\/.*?["']/);
      
      // Domain should not depend on infrastructure adapters
      expect(content, `${cap}/domain must not import infrastructure adapters in ${file}`).not.toMatch(/from\s+["'].*\/adapters\/.*?["']/);
    }
  }
});

test('capabilities must communicate through public contracts, no deep imports', () => {
  for (const cap of BUSINESS_CAPABILITIES) {
    const srcDir = path.join(packagesDir, cap, 'src');
    const files = getAllFiles(srcDir);
    
    for (const file of files) {
      const content = fs.readFileSync(file, 'utf8');
      
      // We check for imports from other capabilities.
      // e.g. import { X } from "@navdhya/puja" is ALLOWED.
      // import { X } from "@navdhya/puja/src/domain/Y" is FORBIDDEN.
      
      for (const otherCap of BUSINESS_CAPABILITIES) {
        if (otherCap === cap) continue;
        
        // Match imports starting with @navdhya/{otherCap}/
        // Example: from "@navdhya/puja/src/domain/Puja"
        const forbiddenPattern = new RegExp(`from\\s+["']@navdhya/${otherCap}/.+["']`);
        expect(content, `${cap} must not deep import from ${otherCap} in ${file}`).not.toMatch(forbiddenPattern);
      }
    }
  }
});

test('apps must not import apps', () => {
  const webDir = path.join(appsDir, 'web');
  const files = getAllFiles(webDir);
  for (const file of files) {
    const content = fs.readFileSync(file, 'utf8');
    // Ensure apps/web does not import from other apps
    expect(content, `apps/web must not import from other apps in ${file}`).not.toMatch(/from\s+["'](?:\.\.\/)*apps\/(?!web).*?["']/);
  }
});
