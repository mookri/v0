// Fixup: deep @navdhya/<pkg>/<rest> specifiers must keep the src/ segment
// (@navdhya/<pkg>/src/…) to match tsconfig paths + vitest aliases.
// DRY RUN by default. Usage: node tooling/scripts/fix-deep-src.js [--write]
const fs = require('fs');
const path = require('path');

const ROOT = path.join(__dirname, '..', '..');
const WRITE = process.argv.includes('--write');
const SPEC_RE = /((?:import|export)[^'"]*?from\s*|import\s+)(['"])(@navdhya\/[^/'"]+\/.+?)\2/g;

function walk(dir, out) {
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, e.name);
    if (e.isDirectory()) {
      if (['node_modules', '.next', '.turbo', 'dist'].includes(e.name)) continue;
      walk(full, out);
    } else if (/\.(ts|tsx)$/.test(e.name)) out.push(full);
  }
  return out;
}

let fixed = 0;
const problems = [];
for (const root of ['packages', 'apps', 'infrastructure', 'tests', 'workers']) {
  const abs = path.join(ROOT, root);
  if (!fs.existsSync(abs)) continue;
  for (const file of walk(abs, [])) {
    const content = fs.readFileSync(file, 'utf8');
    let changed = false;
    const next = content.replace(SPEC_RE, (full, head, quote, spec) => {
      const m = spec.match(/^@navdhya\/([^/]+)\/(.+)$/);
      if (!m) return full;
      const rest = m[2];
      if (rest.startsWith('src/')) return full;
      const disk = path.join(ROOT, 'packages', m[1], 'src', rest);
      const withExt = [disk + '.ts', disk + '.tsx', disk, path.join(disk, 'index.ts')];
      if (!withExt.some((p) => fs.existsSync(p))) {
        problems.push(`${path.relative(ROOT, file)} -> ${spec} (no file at packages/${m[1]}/src/${rest})`);
        return full;
      }
      changed = true;
      fixed++;
      return `${head}${quote}@navdhya/${m[1]}/src/${rest}${quote}`;
    });
    if (changed && WRITE) fs.writeFileSync(file, next);
  }
}
console.log(`fixed=${fixed} problems=${problems.length} write=${WRITE}`);
for (const p of problems.slice(0, 20)) console.log('PROBLEM ' + p);
if (problems.length > 0) process.exitCode = 2;
