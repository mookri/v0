import type { Role } from "@/lib/dlo-x/types";

export interface RoleSectionConfig {
  slug: string;
  title: string;
  description: string;
}

export interface RoleWorkspaceConfig {
  role: Role;
  basePath: string;
  label: string;
  headline: string;
  intro: string;
  sections: RoleSectionConfig[];
}

export const ROLE_WORKSPACES: Record<Role, RoleWorkspaceConfig> = {
  User: {
    role: "User",
    basePath: "/user",
    label: "User Dashboard",
    headline: "Customer workspace",
    intro: "Bookings, favorites, payments, and profile tasks stay together so families can move from interest to ceremony without extra friction.",
    sections: [
      { slug: "overview", title: "Overview", description: "A quick picture of bookings, saved services, and open support needs." },
      { slug: "bookings", title: "Bookings", description: "Track ceremonies, dates, assigned pandits, and request status." },
      { slug: "favorites", title: "Favorites", description: "Saved pandits, rituals, and comparison pages." },
      { slug: "reviews", title: "Reviews", description: "Feedback history and ceremony follow-up prompts." },
      { slug: "payments", title: "Payments", description: "Invoices, payment state, and refund or reschedule context." },
      { slug: "profile", title: "Profile", description: "Household details, communication preferences, and language setup." },
      { slug: "settings", title: "Settings", description: "Preferences for reminders, notifications, and default ritual planning flows." },
      { slug: "support", title: "Support", description: "Booking clarifications and account assistance." },
    ],
  },
  Pandit: {
    role: "Pandit",
    basePath: "/pandit",
    label: "Pandit Dashboard",
    headline: "Pandit workspace",
    intro: "Lead handling, service quality, calendar readiness, and profile trust signals live in one operating view.",
    sections: [
      { slug: "overview", title: "Overview", description: "Lead velocity, ceremony load, and pending profile work." },
      { slug: "leads", title: "Leads", description: "Incoming ceremony requests, locality fit, and confirmation workflow." },
      { slug: "calendar", title: "Calendar", description: "Scheduled ceremonies, preparation windows, and travel planning." },
      { slug: "services", title: "Services", description: "Declared rituals, pricing guidance, and regional specialization tags." },
      { slug: "earnings", title: "Earnings", description: "Payout summaries, completed ceremonies, and adjustments." },
      { slug: "verification", title: "Verification", description: "Documents, training refresh, and profile trust coverage." },
      { slug: "reviews", title: "Reviews", description: "Household feedback and quality coaching signals." },
      { slug: "profile", title: "Profile", description: "Bio, languages, expertise, and travel coverage." },
      { slug: "settings", title: "Settings", description: "Availability, contact preferences, and account controls." },
    ],
  },
  Vendor: {
    role: "Vendor",
    basePath: "/vendor",
    label: "Vendor Dashboard",
    headline: "Vendor workspace",
    intro: "Material supply, catalog upkeep, and delivery reliability are organized around the real order flow.",
    sections: [
      { slug: "overview", title: "Overview", description: "Order status, fulfillment health, and stock pressure." },
      { slug: "orders", title: "Orders", description: "Active material orders and schedule commitments." },
      { slug: "catalog", title: "Catalog", description: "Products, bundles, and packaging readiness." },
      { slug: "fulfillment", title: "Fulfillment", description: "Dispatch flow, SLA view, and destination tracking." },
      { slug: "invoices", title: "Invoices", description: "Billing, reconciliation, and payout visibility." },
      { slug: "profile", title: "Profile", description: "Business details, supply areas, and contact identity." },
      { slug: "settings", title: "Settings", description: "Notification rules and operating preferences." },
    ],
  },
  Manager: {
    role: "Manager",
    basePath: "/manager",
    label: "Manager Dashboard",
    headline: "Manager workspace",
    intro: "Operations, quality, and city rollout queues are grouped so managers can act across teams without losing context.",
    sections: [
      { slug: "overview", title: "Overview", description: "Operational workload, quality alerts, and launch readiness." },
      { slug: "operations", title: "Operations", description: "Service flow issues, escalations, and assignment pressure." },
      { slug: "assignments", title: "Assignments", description: "Work allocation across content, support, and ops teams." },
      { slug: "quality", title: "Quality", description: "Review anomalies, process drift, and corrective actions." },
      { slug: "cities", title: "Cities", description: "Coverage expansion, locality onboarding, and launch blockers." },
      { slug: "reports", title: "Reports", description: "Operational and market reporting snapshots." },
      { slug: "settings", title: "Settings", description: "Workspace rules and escalation preferences." },
    ],
  },
  Admin: {
    role: "Admin",
    basePath: "/admin",
    label: "Admin Dashboard",
    headline: "Platform administration",
    intro: "People, marketplace quality, SEO operations, support, and payouts are managed through one typed control plane.",
    sections: [
      { slug: "overview", title: "Overview", description: "Platform snapshot across accounts, bookings, and content." },
      { slug: "users", title: "Users", description: "Customer account review and support resolution." },
      { slug: "pandits", title: "Pandits", description: "Pandit verification, quality, and availability monitoring." },
      { slug: "vendors", title: "Vendors", description: "Supply partner management and fulfillment oversight." },
      { slug: "managers", title: "Managers", description: "Operational leadership assignments and permissions." },
      { slug: "bookings", title: "Bookings", description: "Platform-wide booking review and issue handling." },
      { slug: "content", title: "Content", description: "Editorial queue, refreshes, and publication status." },
      { slug: "catalog", title: "Catalog", description: "Catalog items with publish and archive controls." },
      { slug: "seo", title: "SEO", description: "Coverage rollout, QA, structured data, and market expansion status." },
      { slug: "payouts", title: "Payouts", description: "Payout review and finance exceptions." },
      { slug: "support", title: "Support", description: "Escalated customer and partner support issues." },
      { slug: "settings", title: "Settings", description: "Global controls and platform defaults." },
    ],
  },
  SuperAdmin: {
    role: "SuperAdmin",
    basePath: "/platform",
    label: "SuperAdmin Dashboard",
    headline: "Platform control center",
    intro: "Global metrics, system health, user management, security, and platform-wide operations across all services.",
    sections: [
      { slug: "overview", title: "Overview", description: "Global platform metrics, revenue, and system health." },
      { slug: "system", title: "System Health", description: "Server status, uptime, performance, and infrastructure." },
      { slug: "users", title: "Users", description: "All users across all roles with management tools." },
      { slug: "roles", title: "Roles & Permissions", description: "Role configuration, access control, and permissions." },
      { slug: "security", title: "Security", description: "Auth logs, 2FA status, threat detection, and compliance." },
      { slug: "billing", title: "Billing & Plans", description: "Subscriptions, invoices, payments, and financial overview." },
      { slug: "api", title: "API Management", description: "API keys, rate limits, webhooks, and integrations." },
      { slug: "logs", title: "Audit Logs", description: "System-wide activity logs and event tracking." },
      { slug: "settings", title: "Settings", description: "Global config, feature flags, and platform defaults." },
    ],
  },
};

export function getWorkspaceConfig(role: Role): RoleWorkspaceConfig {
  return ROLE_WORKSPACES[role];
}

export function getRoleSection(role: Role, slug?: string): RoleSectionConfig {
  const workspace = getWorkspaceConfig(role);
  return workspace.sections.find((section) => section.slug === (slug ?? "overview")) ?? workspace.sections[0];
}
