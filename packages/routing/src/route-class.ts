export type RouteClass =
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
