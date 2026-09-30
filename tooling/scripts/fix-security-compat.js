const fs = require('fs');
const path = require('path');

const destDir = path.join(process.cwd(), 'packages/security/src');
const pkgIndex = `export * from "./csrf";
export * from "./rate-limit";
export * from "./sanitize";
`;
fs.writeFileSync(path.join(destDir, 'index.ts'), pkgIndex);

const srcDir = path.join(process.cwd(), 'apps/web/lib/security');
fs.writeFileSync(path.join(srcDir, 'csrf.ts'), `export { issueCsrfToken, validateCsrfToken, extractCsrfToken, CSRF_CONSTANTS } from "@navdhya/security";\nexport type { CsrfTokenOptions, CsrfTokenResult, CsrfValidation } from "@navdhya/security";\n`);
fs.writeFileSync(path.join(srcDir, 'rate-limit.ts'), `export { InProcessRateLimiter, clientIp } from "@navdhya/security";\nexport type { RateLimitConfig, RateLimitResult } from "@navdhya/security";\n`);
fs.writeFileSync(path.join(srcDir, 'sanitize.ts'), `export { sanitizePlainText, sanitizeMarkdown, sanitizeUrl } from "@navdhya/security";\nexport type { SanitizeOptions } from "@navdhya/security";\n`);

const webPkgPath = path.join(process.cwd(), 'apps/web/package.json');
let webPkg = JSON.parse(fs.readFileSync(webPkgPath, 'utf8'));
webPkg.dependencies = webPkg.dependencies || {};
webPkg.dependencies["@navdhya/security"] = "workspace:*";
fs.writeFileSync(webPkgPath, JSON.stringify(webPkg, null, 2), 'utf8');
