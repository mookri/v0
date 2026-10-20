// Rewrites parent-directory imports to @ aliases. DRY RUN by default.
// Usage: node tooling/scripts/rewrite-at-imports.js [--write]
// Rules: packages/<p>/src -> @/ | apps/<a> -> @/ | infrastructure -> @infra/
// Cross-root targets -> @navdhya/<pkg>/… | @infra/… | @apps/<a>/…
// Aborts loudly on cross-app or cross-package (inside packages/) violations.
const fs = require('fs');
const path = require('path');

const ROOT = path.join(__dirname, '..', '..');
const WRITE = process.argv.includes('--write');
const SCAN_ROOTS = ['packages', 'apps', 'infrastructure', 'tests', 'workers'];
const SKIP_DIRS = new Set(['node_modules', '.next', '.turbo', 'dist']);
const EXT_RE = /\.(ts|tsx)$/;

function walk(dir, out) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    if (entry.isDirectory()) {
      if (SKIP_DIRS.has(entry.name)) continue;
      walk(path.join(dir, entry.name), out);
    } else if (EXT_RE.test(entry.name)) {
      out.push(path.join(dir, entry.name));
    }
  }
  return out;
}

function baseOf(relPosix) {
  const parts = relPosix.split('/');
  if (parts[0] === 'packages' && parts.includes('src')) {
    const srcIdx = parts.indexOf('src');
    return { base: parts.slice(0, srcIdx + 1).join('/'), prefix: '@/' };
  }
  if (parts[0] === 'apps' && parts.length > 1) {
    return { base: `apps/${parts[1]}`, prefix: '@/' };
  }
  if (parts[0] === 'infrastructure') {
    return { base: 'infrastructure', prefix: '@infra/' };
  }
  return null;
}

function targetAlias(targetPosix) {
  const parts = targetPosix.split('/');
  if (parts[0] === 'packages' && parts.includes('src')) {
    const srcIdx = parts.indexOf('src');
    const pkg = parts[1];
    return `@navdhya/${pkg}/${parts.slice(srcIdx + 1).join('/')}`;
  }
  if (parts[0] === 'infrastructure') {
    return `@infra/${parts.slice(1).join('/')}`;
  }
  if (parts[0] === 'apps' && parts.length > 1) {
    return `@apps/${parts[1]}/${parts.slice(2).join('/')}`;
  }
  return null;
}

const SPEC_RE = /((?:import|export)[^'"]*?from\s*|import\s+)(['"])(\.[^'"]*)\2/g;

let rewritten = 0;
let checked = 0;
const violations = [];
const unmapped = [];

for (const root of SCAN_ROOTS) {
  const abs = path.join(ROOT, root);
  if (!fs.existsSync(abs)) continue;
  for (const file of walk(abs, [])) {
    checked++;
    const rel = path.relative(ROOT, file).split(path.sep).join('/');
    const base = baseOf(rel);
    let content = fs.readFileSync(file, 'utf8');
    let changed = false;
    content = content.replace(SPEC_RE, (full, head, quote, spec) => {
      if (!spec.includes('..')) return full;
      const target = path.posix.normalize(path.posix.join(path.posix.dirname('/' + rel), spec)).slice(1);
      if (base && (target === base.base || target.startsWith(base.base + '/'))) {
        changed = true;
        rewritten++;
        return `${head}${quote}${base.prefix}${target === base.base ? '' : target.slice(base.base.length + 1)}${quote}`;
      }
      const alias = targetAlias(target);
      if (alias) {
        const importerRoot = rel.split('/')[0] === 'packages' ? 'packages' : rel.split('/')[0];
        if (importerRoot === 'packages' && alias.startsWith('@navdhya/')) {
          violations.push(`${rel} -> ${spec} (cross-package import inside packages/)`);
          return full;
        }
        if (rel.startsWith('apps/') && alias.startsWith('@apps/')) {
          const importerApp = rel.split('/')[1];
          const targetApp = alias.split('/')[1];
          if (importerApp !== targetApp) {
            violations.push(`${rel} -> ${spec} (cross-app import)`);
            return full;
          }
        }
        changed = true;
        rewritten++;
        return `${head}${quote}${alias}${quote}`;
      }
      unmapped.push(`${rel} -> ${spec}`);
      return full;
    });
    if (changed && WRITE) fs.writeFileSync(file, content);
  }
}

console.log(`checked=${checked} rewrites=${rewritten} violations=${violations.length} unmapped=${unmapped.length} write=${WRITE}`);
for (const v of violations) console.log('VIOLATION ' + v);
for (const u of unmapped.slice(0, 20)) console.log('UNMAPPED ' + u);
if (violations.length > 0) process.exitCode = 2;
