const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const componentsToMove = [
  'button.tsx',
  'input.tsx',
  'label.tsx',
  'select.tsx',
  'sheet.tsx',
  'separator.tsx',
  'accordion.tsx',
  'dropdown-menu.tsx',
  'textarea.tsx'
];

const webUiDir = path.join(process.cwd(), 'apps/web/components/ui');
const dsPrimitivesDir = path.join(process.cwd(), 'packages/design-system/src/primitives');
const pkgIndexLines = [];

for (const comp of componentsToMove) {
  const src = path.join(webUiDir, comp);
  
  // the component name (e.g. 'button.tsx' -> 'button')
  const baseName = comp.replace('.tsx', '');
  
  const compDir = path.join(dsPrimitivesDir, baseName);
  fs.mkdirSync(compDir, { recursive: true });
  
  const dest = path.join(compDir, 'index.tsx');
  
  // Move file via git mv
  try {
    execSync(`git mv "${src}" "${dest}"`, { stdio: 'inherit' });
  } catch (e) {
    console.warn(`Failed to git mv ${comp}:`, e);
    continue;
  }
  
  // Write compat layer
  const compatCode = `export * from "@navdhya/design-system";\n`;
  fs.writeFileSync(src, compatCode);
  
  // Add to index
  pkgIndexLines.push(`export * from "./primitives/${baseName}";`);
}

// Write the main design-system index
fs.writeFileSync(path.join(process.cwd(), 'packages/design-system/src/index.ts'), pkgIndexLines.join('\n') + '\n');

// Add dependency to web
const webPkgPath = path.join(process.cwd(), 'apps/web/package.json');
let webPkg = JSON.parse(fs.readFileSync(webPkgPath, 'utf8'));
webPkg.dependencies = webPkg.dependencies || {};
webPkg.dependencies["@navdhya/design-system"] = "workspace:*";
fs.writeFileSync(webPkgPath, JSON.stringify(webPkg, null, 2), 'utf8');

// Also move NavLink to navigation/ and ProgressBar to feedback/
const navLinkSrc = path.join(webUiDir, 'NavLink.tsx');
const navLinkDestDir = path.join(process.cwd(), 'packages/design-system/src/navigation');
fs.mkdirSync(navLinkDestDir, { recursive: true });
execSync(`git mv "${navLinkSrc}" "${path.join(navLinkDestDir, 'NavLink.tsx')}"`);
fs.writeFileSync(navLinkSrc, `export * from "@navdhya/design-system";\n`);
fs.appendFileSync(path.join(process.cwd(), 'packages/design-system/src/index.ts'), `export * from "./navigation/NavLink";\n`);

const progBarSrc = path.join(webUiDir, 'ProgressBar.tsx');
const progBarDestDir = path.join(process.cwd(), 'packages/design-system/src/feedback');
fs.mkdirSync(progBarDestDir, { recursive: true });
execSync(`git mv "${progBarSrc}" "${path.join(progBarDestDir, 'ProgressBar.tsx')}"`);
fs.writeFileSync(progBarSrc, `export * from "@navdhya/design-system";\n`);
fs.appendFileSync(path.join(process.cwd(), 'packages/design-system/src/index.ts'), `export * from "./feedback/ProgressBar";\n`);
