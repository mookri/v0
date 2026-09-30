const fs = require('fs');
const path = require('path');

const srcDir = path.join(process.cwd(), 'apps/web/lib/security');
const destDir = path.join(process.cwd(), 'packages/security/src');

// We use git mv to move them
// so we'll do that via run_command afterwards. But for now, we'll write the script that generates the compatibility layer.

// After moving them to packages/security/src, we need to create the compatibility layer in apps/web/lib/security.

const csrfReexport = `export * from "@navdhya/security/csrf";`;
const rateLimitReexport = `export * from "@navdhya/security/rate-limit";`;
const sanitizeReexport = `export * from "@navdhya/security/sanitize";`;

fs.writeFileSync(path.join(srcDir, 'csrf.ts'), csrfReexport);
fs.writeFileSync(path.join(srcDir, 'rate-limit.ts'), rateLimitReexport);
fs.writeFileSync(path.join(srcDir, 'sanitize.ts'), sanitizeReexport);

const indexReexport = `export * from "./csrf";
export * from "./rate-limit";
export * from "./sanitize";
export * from "./scroll-lock"; // leave scroll-lock here for now
`;
fs.writeFileSync(path.join(srcDir, 'index.ts'), indexReexport);

// And we need to create the main index.ts for the package
const pkgIndex = `export * as csrf from "./csrf";
export * as rateLimit from "./rate-limit";
export * as sanitize from "./sanitize";
`;
fs.writeFileSync(path.join(destDir, 'index.ts'), pkgIndex);
