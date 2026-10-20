// Pass 2: inside packages/<p>/src, rewrite local aliases to absolute form:
//   @/…                    -> @navdhya/<p>/src/…
//   @navdhya/<p>/<rest>    -> @navdhya/<p>/src/<rest>  (unless rest starts with src/)
// Rationale: @/ resolves per importing program; absolute specifiers resolve
// identically no matter which program includes the file (apps/BFF/tests pull
// package sources into their own compilations).
// DRY RUN by default. Usage: node tooling/scripts/fix-local-alias.js [--write]
const fs = require('fs');
const path = require('path');

const ROOT = path.join(__dirname, '..', '..');
const WRITE = process.argv.includes('--write');
const SPEC_RE = /((?:import|export)[^'"]*?from\s*|import\s+)(['"])(@(?:\/|navdhya\/[^/'"]+\/)[^'"]*)\2/g;

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
const abs = path.join(ROOT, 'packages');
for (const pkg of fs.readdirSync(abs)) {
  const srcDir = path.join(abs, pkg, 'src');
  if (!fs.statSync(path.join(abs, pkg)).isDirectory() || !fs.existsSync(srcDir)) continue;
  for (const file of walk(srcDir, [])) {
    const content = fs.readFileSync(file, 'utf8');
    let changed = false;
    const next = content.replace(SPEC_RE, (full, head, quote, spec) => {
      if (spec.startsWith('@/')) {
        changed = true;
        fixed++;
        return `${head}${quote}@navdhya/${pkg}/src/${spec.slice(2)}${quote}`;
      }
      const m = spec.match(/^@navdhya\/([^/]+)\/(.+)$/);
      if (m && m[1] === pkg && !m[2].startsWith('src/')) {
        changed = true;
        fixed++;
        return `${head}${quote}@navdhya/${pkg}/src/${m[2]}${quote}`;
      }
      return full;
    });
    if (changed && WRITE) fs.writeFileSync(file, next);
  }
}
console.log(`fixed=${fixed} write=${WRITE}`);
