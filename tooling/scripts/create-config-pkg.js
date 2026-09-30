const fs = require('fs');
const path = require('path');

const configDir = path.join(process.cwd(), 'packages/config/src');
fs.mkdirSync(configDir, { recursive: true });

const environmentContent = `export type Environment =
  | "development"
  | "staging"
  | "production";

export function getEnvironment(): Environment {
  const value = process.env.NODE_ENV;
  if (value === "production") return "production";
  if (value === "test") return "development";
  return "development";
}
`;
fs.writeFileSync(path.join(configDir, 'environment.ts'), environmentContent);

const domainsContent = `export const domains = {
  development: {
    web: "http://navdhya.localhost",
    customer: "http://app.navdhya.localhost",
    api: "http://api.navdhya.localhost",
  },
  staging: {
    web: "https://staging.navdhya.com",
    customer: "https://app.staging.navdhya.com",
    api: "https://api.staging.navdhya.com",
  },
  production: {
    web: "https://navdhya.com",
    customer: "https://app.navdhya.com",
    api: "https://api.navdhya.com",
  },
} as const;
`;
fs.writeFileSync(path.join(configDir, 'domains.ts'), domainsContent);

const featuresContent = `export const features = {
  customerApp: false,
  customerAppBooking: false,
  newAuthHandoff: false,
};
`;
fs.writeFileSync(path.join(configDir, 'features.ts'), featuresContent);

const envContent = `const requiredServerEnv = [
  // "DATABASE_URL",
  // "SESSION_SIGNING_KEY",
];

export function validateEnv() {
  if (typeof window !== "undefined") return; // Server-only validation
  
  for (const key of requiredServerEnv) {
    if (!process.env[key]) {
      console.warn(\`Missing required server environment variable: \${key}\`);
    }
  }
}
`;
fs.writeFileSync(path.join(configDir, 'env.ts'), envContent);

const indexContent = `export * from "./environment";
export * from "./domains";
export * from "./features";
export * from "./env";
`;
fs.writeFileSync(path.join(configDir, 'index.ts'), indexContent);
