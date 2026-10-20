const fs = require('fs');
const path = require('path');

const i18nPkgDir = path.join(process.cwd(), 'packages/i18n/src');
fs.writeFileSync(path.join(i18nPkgDir, 'index.ts'), `export * from "./locale";
export * from "./formatter";
export * from "./translator";
`);

// Rename formatters.ts to formatter.ts and translate.ts to translator.ts if needed
const { execSync } = require('child_process');
execSync(`git mv "${path.join(i18nPkgDir, 'formatters.ts')}" "${path.join(i18nPkgDir, 'formatter.ts')}"`);
execSync(`git mv "${path.join(i18nPkgDir, 'translate.ts')}" "${path.join(i18nPkgDir, 'translator.ts')}"`);

const webI18nDir = path.join(process.cwd(), 'apps/web/lib/i18n');
fs.writeFileSync(path.join(webI18nDir, 'locale.ts'), `export * from "@navdhya/i18n";\n`);
fs.writeFileSync(path.join(webI18nDir, 'formatters.ts'), `export * from "@navdhya/i18n";\n`);
fs.writeFileSync(path.join(webI18nDir, 'translate.ts'), `export * from "@navdhya/i18n";\n`);

const webPkgPath = path.join(process.cwd(), 'apps/web/package.json');
let webPkg = JSON.parse(fs.readFileSync(webPkgPath, 'utf8'));
webPkg.dependencies = webPkg.dependencies || {};
webPkg.dependencies["@navdhya/i18n"] = "workspace:*";
fs.writeFileSync(webPkgPath, JSON.stringify(webPkg, null, 2), 'utf8');
