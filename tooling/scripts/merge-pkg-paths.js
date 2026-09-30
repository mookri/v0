const fs = require('fs');
const path = require('path');
const ROOT = path.join(__dirname, '..', '..');
const abs = path.join(ROOT, 'packages');
for (const pkg of fs.readdirSync(abs)) {
  const f = path.join(abs, pkg, 'tsconfig.json');
  if (!fs.existsSync(f) || !fs.statSync(path.join(abs, pkg)).isDirectory()) continue;
  const j = JSON.parse(fs.readFileSync(f, 'utf8'));
  j.compilerOptions = j.compilerOptions || {};
  if (!j.compilerOptions.baseUrl) j.compilerOptions.baseUrl = '.';
  j.compilerOptions.paths = j.compilerOptions.paths || {};
  if (!j.compilerOptions.paths['@/*']) j.compilerOptions.paths['@/*'] = ['./src/*'];
  if (!j.compilerOptions.paths['@navdhya/*']) j.compilerOptions.paths['@navdhya/*'] = ['../../packages/*'];
  if (!j.compilerOptions.paths['@infra/*']) j.compilerOptions.paths['@infra/*'] = ['../../infrastructure/*'];
  fs.writeFileSync(f, JSON.stringify(j, null, 4));
  console.log('merged: ' + pkg);
}
