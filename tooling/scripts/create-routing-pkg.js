const fs = require('fs');
const path = require('path');

const routingDir = path.join(process.cwd(), 'packages/routing/src');
fs.mkdirSync(routingDir, { recursive: true });

const domainsContent = `export type Domain = "web" | "customer" | "admin" | "api";
`;
fs.writeFileSync(path.join(routingDir, 'domains.ts'), domainsContent);

const routeClassContent = `export type RouteClass =
  | "public-content"
  | "public-transaction"
  | "auth"
  | "customer"
  | "partner"
  | "admin"
  | "operations"
  | "system";

export function classifyRoute(path: string): RouteClass {
  if (path.startsWith("/admin")) return "admin";
  if (path.startsWith("/customer") || path.startsWith("/user") || path.startsWith("/settings")) return "customer";
  if (path.startsWith("/book")) return "public-transaction";
  if (path.startsWith("/pandit") || path.startsWith("/vendor")) return "partner";
  return "public-content"; // fallback
}
`;
fs.writeFileSync(path.join(routingDir, 'route-class.ts'), routeClassContent);

const destinationsContent = `import { domains } from "@navdhya/config";

export function resolveDestination(options: { route: string; env: "development" | "staging" | "production" }): string {
  // Mock logic - in reality depends on route class
  const domainConfig = domains[options.env];
  return domainConfig.web + options.route;
}
`;
fs.writeFileSync(path.join(routingDir, 'destinations.ts'), destinationsContent);

const returnUrlContent = `export function isSafeReturnUrl(url: string): boolean {
  if (url.startsWith("http://") || url.startsWith("https://") || url.startsWith("//") || url.startsWith("javascript:")) {
    return false;
  }
  return url.startsWith("/");
}
`;
fs.writeFileSync(path.join(routingDir, 'return-url.ts'), returnUrlContent);

const indexContent = `export * from "./domains";
export * from "./route-class";
export * from "./destinations";
export * from "./return-url";
`;
fs.writeFileSync(path.join(routingDir, 'index.ts'), indexContent);

// Update package.json to depend on config
const pkgPath = path.join(process.cwd(), 'packages/routing/package.json');
let pkg = JSON.parse(fs.readFileSync(pkgPath, 'utf8'));
pkg.dependencies = {
  "@navdhya/config": "workspace:*"
};
fs.writeFileSync(pkgPath, JSON.stringify(pkg, null, 2), 'utf8');
