import { domains } from "@navdhya/config";

export function resolveDestination(options: { route: string; env: "development" | "staging" | "production" }): string {
  // Mock logic - in reality depends on route class
  const domainConfig = domains[options.env];
  return domainConfig.web + options.route;
}
