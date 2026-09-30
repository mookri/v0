export type Environment =
  | "development"
  | "staging"
  | "production";

export function getEnvironment(): Environment {
  const value = process.env.NODE_ENV;
  if (value === "production") return "production";
  if (value === "test") return "development";
  return "development";
}
