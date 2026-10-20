const fs = require('fs');
const path = require('path');

const obsDir = path.join(process.cwd(), 'packages/observability/src');

fs.writeFileSync(path.join(obsDir, 'logging.ts'), `export function logInfo(message: string, context?: Record<string, any>) { console.log(message, context); }\nexport function logError(error: Error | string, context?: Record<string, any>) { console.error(error, context); }\n`);
fs.writeFileSync(path.join(obsDir, 'tracing.ts'), `import type { UUID } from "@navdhya/contracts";\nexport function createTraceId(): UUID { return crypto.randomUUID(); }\n`);
fs.writeFileSync(path.join(obsDir, 'metrics.ts'), `export function recordMetric(name: string, value: number, tags?: Record<string, string>) { /* TODO */ }\n`);
fs.writeFileSync(path.join(obsDir, 'context.ts'), `export interface ObservabilityContext { traceId: string; requestId?: string; }\n`);
fs.writeFileSync(path.join(obsDir, 'index.ts'), `export * from "./logging";\nexport * from "./tracing";\nexport * from "./metrics";\nexport * from "./context";\n`);

const webPkgPath = path.join(process.cwd(), 'apps/web/package.json');
let webPkg = JSON.parse(fs.readFileSync(webPkgPath, 'utf8'));
webPkg.dependencies = webPkg.dependencies || {};
webPkg.dependencies["@navdhya/observability"] = "workspace:*";
fs.writeFileSync(webPkgPath, JSON.stringify(webPkg, null, 2), 'utf8');

const obsPkgPath = path.join(process.cwd(), 'packages/observability/package.json');
let obsPkg = JSON.parse(fs.readFileSync(obsPkgPath, 'utf8'));
obsPkg.dependencies = obsPkg.dependencies || {};
obsPkg.dependencies["@navdhya/contracts"] = "workspace:*";
fs.writeFileSync(obsPkgPath, JSON.stringify(obsPkg, null, 2), 'utf8');

