// Evidence for extraction-readiness scoring. Read-only.
const fs = require('fs');
const path = require('path');
const ROOT = path.join(__dirname, '..', '..');
const abs = path.join(ROOT, 'packages');

function files(dir) {
  let out = [];
  if (!fs.existsSync(dir)) return out;
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, e.name);
    if (e.isDirectory()) out = out.concat(files(full));
    else if (/\.(ts|tsx)$/.test(e.name)) out.push(full);
  }
  return out;
}

for (const pkg of fs.readdirSync(abs).sort()) {
  const src = path.join(abs, pkg, 'src');
  if (!fs.existsSync(src)) { console.log(`${pkg}: NO-SRC`); continue; }
  const layers = ['domain', 'application', 'contracts', 'ports', 'adapters', 'events', 'ui'].filter((l) =>
    fs.existsSync(path.join(src, l)),
  );
  const all = files(src);
  const has = (re) => all.some((f) => re.test(fs.readFileSync(f, 'utf8')));
  const flags = [];
  if (has(/from\s+['"]react['"]|from\s+['"]next\//)) flags.push('UI-DEP');
  if (has(/from\s+['"]@apps\//)) flags.push('APP-DEP');
  if (has(/@navdhya\//)) flags.push('XPKG');
  const deep = all.some((f) => {
    const c = fs.readFileSync(f, 'utf8');
    return /@navdhya\/[^/'"]+\/src\//.test(c);
  });
  if (deep) flags.push('DEEP');
  console.log(`${pkg}: n=${all.length} layers=[${layers.join(',')}] ${flags.join(' ') || 'clean'}`);
}
