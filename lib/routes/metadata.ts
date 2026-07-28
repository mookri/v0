import type { Role } from "@/lib/dlo-x/types";

export interface RouteBreadcrumb {
  label: string;
  href: string;
}

export interface RouteMeta {
  protected: boolean;
  roleAllowlist: Role[];
  noindex: boolean;
  breadcrumbs: RouteBreadcrumb[];
}

type RouteRule = {
  pattern: string;
  meta: Partial<RouteMeta>;
};

const DEFAULT_META: RouteMeta = {
  protected: false,
  roleAllowlist: [],
  noindex: false,
  breadcrumbs: [],
};

const ROUTE_RULES: RouteRule[] = [
  {
    pattern: "/login",
    meta: { noindex: true },
  },
  {
    pattern: "/signup",
    meta: { noindex: true },
  },
  {
    pattern: "/forgot-password",
    meta: { noindex: true },
  },
  {
    pattern: "/reset-password",
    meta: { noindex: true },
  },
  {
    pattern: "/new-password",
    meta: { noindex: true },
  },
  {
    pattern: "/two-factor",
    meta: { noindex: true },
  },
  {
    pattern: "/lock",
    meta: { noindex: true },
  },
  {
    pattern: "/pin-login",
    meta: { noindex: true },
  },
  {
    pattern: "/email-verified",
    meta: { noindex: true },
  },
  {
    pattern: "/account-deactivated",
    meta: { noindex: true },
  },
  {
    pattern: "/verify",
    meta: { noindex: true },
  },
  {
    pattern: "/select-role",
    meta: { noindex: true },
  },
  {
    pattern: "/complete-profile",
    meta: { noindex: true },
  },
  {
    pattern: "/invite",
    meta: { noindex: true },
  },
  {
    pattern: "/apply/*",
    meta: { noindex: true },
  },
  {
    pattern: "/auth/*",
    meta: { noindex: true },
  },
  {
    pattern: "/user/*",
    meta: {
      protected: true,
      roleAllowlist: ["User"],
      noindex: true,
    },
  },
  {
    pattern: "/pandit/*",
    meta: {
      protected: true,
      roleAllowlist: ["Pandit"],
      noindex: true,
    },
  },
  {
    pattern: "/vendor/*",
    meta: {
      protected: true,
      roleAllowlist: ["Vendor"],
      noindex: true,
    },
  },
  {
    pattern: "/manager/*",
    meta: {
      protected: true,
      roleAllowlist: ["Manager"],
      noindex: true,
    },
  },
  {
    pattern: "/dashboard/*",
    meta: {
      protected: true,
      noindex: true,
    },
  },
  {
    pattern: "/dashboard/user/*",
    meta: {
      roleAllowlist: ["User"],
    },
  },
  {
    pattern: "/dashboard/pandit/*",
    meta: {
      roleAllowlist: ["Pandit"],
    },
  },
  {
    pattern: "/dashboard/vendor/*",
    meta: {
      roleAllowlist: ["Vendor"],
    },
  },
  {
    pattern: "/dashboard/manager/*",
    meta: {
      roleAllowlist: ["Manager"],
    },
  },
  {
    pattern: "/admin/*",
    meta: {
      protected: true,
      roleAllowlist: ["Admin"],
      noindex: true,
    },
  },
  {
    pattern: "/platform/*",
    meta: {
      protected: true,
      roleAllowlist: ["SuperAdmin"],
      noindex: true,
    },
  },
  {
    pattern: "/vendor/*",
    meta: {
      protected: true,
      roleAllowlist: ["Vendor"],
      noindex: true,
    },
  },
];

function normalizePath(path: string): string {
  if (!path) return "/";
  const trimmed = path.trim();
  if (trimmed === "/") return "/";
  const withLeadingSlash = trimmed.startsWith("/") ? trimmed : `/${trimmed}`;
  return withLeadingSlash.replace(/\/{2,}/g, "/").replace(/\/$/, "");
}

function matchesRoutePattern(pattern: string, path: string): boolean {
  const normalizedPattern = normalizePath(pattern);
  const normalizedPath = normalizePath(path);

  if (normalizedPattern === "*") return true;
  if (normalizedPattern.endsWith("/*")) {
    const prefix = normalizedPattern.slice(0, -2);
    return normalizedPath === prefix || normalizedPath.startsWith(`${prefix}/`);
  }

  return normalizedPattern === normalizedPath;
}

export function buildBreadcrumbs(path: string): RouteBreadcrumb[] {
  const normalized = normalizePath(path);
  const segments = normalized.split("/").filter(Boolean);

  const breadcrumbs: RouteBreadcrumb[] = [{ label: "Home", href: "/" }];
  let cursor = "";

  for (const segment of segments) {
    cursor += `/${segment}`;
    breadcrumbs.push({
      label: segment
        .split("-")
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(" "),
      href: cursor,
    });
  }

  return breadcrumbs;
}

export function getRouteMetadata(path: string): RouteMeta {
  const normalized = normalizePath(path);
  const matchedRules = ROUTE_RULES.filter((rule) => matchesRoutePattern(rule.pattern, normalized));

  const resolved = matchedRules.reduce<RouteMeta>(
    (meta, rule) => ({
      protected: rule.meta.protected ?? meta.protected,
      roleAllowlist: rule.meta.roleAllowlist ?? meta.roleAllowlist,
      noindex: rule.meta.noindex ?? meta.noindex,
      breadcrumbs: rule.meta.breadcrumbs ?? meta.breadcrumbs,
    }),
    { ...DEFAULT_META },
  );

  return {
    ...resolved,
    breadcrumbs: resolved.breadcrumbs.length > 0 ? resolved.breadcrumbs : buildBreadcrumbs(normalized),
  };
}

export function isIndexableRoute(path: string): boolean {
  return !getRouteMetadata(path).noindex;
}

export function getDefaultRoleForPath(path: string): Role {
  const meta = getRouteMetadata(path);
  if (meta.roleAllowlist.length > 0) {
    return meta.roleAllowlist[0];
  }

  return "User";
}
