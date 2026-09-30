const requiredServerEnv = [
  // "DATABASE_URL",
  // "SESSION_SIGNING_KEY",
];

export function validateEnv() {
  if (typeof window !== "undefined") return; // Server-only validation
  
  for (const key of requiredServerEnv) {
    if (!process.env[key]) {
      console.warn(`Missing required server environment variable: ${key}`);
    }
  }
}
