// domain:check — verify a package's domain.manifest.ts claims against its
// scanned tree. Usage: node tooling/scripts/domain-check.js <name> [--all]
// Drift policy: undeclared cross-package imports are ERRORS (fix code or
// manifest); declared-but-unused consumes are WARNINGS (stale manifest).
const fs = require('fs');
const path = require('path');

const ROOT = path.join(__dirname, '..', '..');

function srcFiles(pkgDir) {
  const out = [];
  const walk = (dir) => {
    for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
      const full = path.join(dir, e.name);
      if (e.isDirectory()) {
        if (['node_modules', 'dist'].includes(e.name)) continue;
        walk(full);
      } else if (/\.(ts|tsx)$/.test(e.name)) out.push(full);
    }
  };
  walk(path.join(ROOT, 'packages', pkgDir, 'src'));
  return out;
}

const SPEC_RE = /from\s+['\"](@navdhya\/[^\/'\"]+)\/[^'\"]*['\"]/g;

function checkOne(name) {
  const errors = [];
  const warnings = [];
  const manifestPath = path.join(ROOT, 'packages', name, 'domain.manifest.ts');
  if (!fs.existsSync(manifestPath)) {
    return { name, errors: ['MANIFEST_MISSING'], warnings };
  }
  const content = fs.readFileSync(manifestPath, 'utf8');
  const getList = (key) => {
    const m = content.match(new RegExp(`${key}:\\s*\\[([^\\]]*)\\]`));
    if (!m) return null;
    return (m[1].match(/['\"]([^'\"]+)['\"]/g) || []).map((s) => s.replace(/['\"]/g, ''));
  };
  const domain = (content.match(/domain:\s*['\"]([^'\"]+)['\"]/) || [])[1];
  if (domain !== name) errors.push(`MANIFEST_DOMAIN_MISMATCH:${domain}`);
  const consumes = getList('consumes') || [];
  const produces = getList('produces') || [];

  const used = new Set();
  for (const file of srcFiles(name)) {
    const src = fs.readFileSync(file, 'utf8');
    let m;
    SPEC_RE.lastIndex = 0;
    while ((m = SPEC_RE.exec(src)) !== null) {
      const dep = m[1].replace('@navdhya/', '');
      if (dep !== name) used.add(dep);
    }
  }
  for (const dep of used) {
    if (!consumes.includes(dep)) errors.push(`UNDECLARED_DEP:${dep}`);
  }
  for (const dep of consumes) {
    if (!used.has(dep)) warnings.push(`UNUSED_CONSUME:${dep}`);
  }
  const indexPath = path.join(ROOT, 'packages', name, 'src', 'index.ts');
  if (!fs.existsSync(indexPath)) errors.push('MANIFEST_NO_PUBLIC_INDEX');

  // produces[] should name events the package actually defines (best-effort:
  // match event names/kinds in src).
  const allSrc = srcFiles(name).map((f) => fs.readFileSync(f, 'utf8')).join('\n');
  for (const event of produces) {
    if (!allSrc.includes(event)) warnings.push(`UNPROVEN_PRODUCES:${event}`);
  }
  return { name, errors, warnings };
}

const args = process.argv.slice(2);
const names = args.includes('--all')
  ? fs.readdirSync(path.join(ROOT, 'packages')).filter((d) => fs.statSync(path.join(ROOT, 'packages', d)).isDirectory())
  : args.filter((a) => !a.startsWith('-'));

if (names.length === 0) {
  console.log('Usage: node tooling/scripts/domain-check.js <name> [--all]');
  process.exit(1);
}

let failed = 0;
for (const name of names) {
  const { errors, warnings } = checkOne(name);
  for (const w of warnings) console.log(`WARN ${name}: ${w}`);
  if (errors.length > 0) {
    failed += 1;
    for (const e of errors) console.log(`FAIL ${name}: ${e}`);
  } else {
    console.log(`OK ${name}${warnings.length > 0 ? ` (${warnings.length} warnings)` : ''}`);
  }
}
process.exit(failed > 0 ? 2 : 0);
