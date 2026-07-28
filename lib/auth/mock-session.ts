import { cookies } from "next/headers";
import type { Role } from "@/lib/dlo-x/types";
import type {
  AccountProfile,
  AuthSession,
  BookingRecord,
  DashboardSummary,
  ManagerQueueItem,
  PanditLead,
  RoleLanding,
  SeoQueueItem,
  VendorOrder,
} from "@/lib/auth/contracts";
import { getDefaultRoleForPath, getRouteMetadata } from "@/lib/routes/metadata";
export { DEV_ROLE_COOKIE, isValidDevRole } from "@/lib/auth/cookie-names";
import { DEV_ROLE_COOKIE, isValidDevRole } from "@/lib/auth/cookie-names";
import type { DevRole } from "@/lib/auth/cookie-names";

/**
 * Placeholder phone number used for mock profiles. Real PII was scrubbed
 * in PR 0.25 (audit finding P-ARCH-26). Never replace with a real number.
 */
export const MOCK_PHONE_PLACEHOLDER = "+91 00000 00000";

export const ROLE_LANDINGS: Record<Role, RoleLanding> = {
  User: {
    role: "User",
    label: "User Workspace",
    href: "/user/overview",
    description: "Bookings, profile, saved services, payments, and support.",
  },
  Pandit: {
    role: "Pandit",
    label: "Pandit Workspace",
    href: "/pandit/overview",
    description: "Leads, calendar, profile, reviews, and verification.",
  },
  Vendor: {
    role: "Vendor",
    label: "Vendor Workspace",
    href: "/vendor/overview",
    description: "Catalog, fulfillment, invoices, and material orders.",
  },
  Manager: {
    role: "Manager",
    label: "Manager Workspace",
    href: "/manager/overview",
    description: "Operations, assignments, city rollout, and reporting.",
  },
  Admin: {
    role: "Admin",
    label: "Admin Workspace",
    href: "/admin/overview",
    description: "Platform operations, people, content, payouts, and SEO QA.",
  },
  SuperAdmin: {
    role: "SuperAdmin",
    label: "SuperAdmin Workspace",
    href: "/platform/overview",
    description: "Global platform control, system health, security, and billing.",
  },
};

function isRole(value: string | undefined): value is Role {
  return isValidDevRole(value);
}

/**
 * Realistic verification status per role. P1 audit follow-up: the
 * original code marked `Pandit` as "verified" and everything else
 * as "pending", which is the inverse of realistic onboarding:
 *   - Customers (User) verify email/phone on signup, so "verified"
 *   - Pandits submit credentials for KYC review, so "pending"
 *     (or "verified" once approved — picked per-id below)
 *   - Vendors go through business-doc review, so "pending"
 *   - Manager / Admin / SuperAdmin are seeded by ops, "verified"
 * A subset of mock pandits are pre-approved ("verified") to exercise
 * the "approved pandit can be booked" code path in the UI.
 */
const MOCK_VERIFIED_PANDITS: ReadonlySet<string> = new Set([
  "mock-pandit",
  "mock-pandit-1",
  "mock-pandit-2",
]);

function deriveVerificationStatus(
  role: Role,
  userId: string,
): AuthSession["verificationStatus"] {
  switch (role) {
    case "User":
    case "Manager":
    case "Admin":
    case "SuperAdmin":
      return "verified";
    case "Pandit":
      return MOCK_VERIFIED_PANDITS.has(userId) ? "verified" : "pending";
    case "Vendor":
      return "pending";
  }
}

export async function getMockSession(path: string): Promise<AuthSession> {
  const cookieStore = await cookies();
  const cookieRole = cookieStore.get(DEV_ROLE_COOKIE)?.value;
  const routeMeta = getRouteMetadata(path);
  const requestedRole = isRole(cookieRole) ? cookieRole : getDefaultRoleForPath(path);
  const role = routeMeta.roleAllowlist.length > 0 && !routeMeta.roleAllowlist.includes(requestedRole)
    ? getDefaultRoleForPath(path)
    : requestedRole;

  const userId = `mock-${role.toLowerCase()}`;

  return {
    userId,
    email: `${role.toLowerCase()}@navdhya.dev`,
    role,
    isAuthenticated: true,
    profileCompleted: true,
    verificationStatus: deriveVerificationStatus(role, userId),
  };
}

export function getRoleLanding(role: Role): RoleLanding {
  return ROLE_LANDINGS[role];
}

export function getMockProfile(role: Role): AccountProfile {
  const baseByRole: Record<Role, Omit<AccountProfile, "id" | "role">> = {
    User: {
      name: "Aarav Sharma",
      email: "user@navdhya.dev",
      phone: "+91 00000 00000",
      city: "New Delhi",
      country: "India",
      timezone: "Asia/Kolkata",
      languages: ["English", "Hindi"],
      completionPercent: 88,
      memberSince: "2025-06-15",
    },
    Pandit: {
      name: "Pandit Ravi Mishra",
      email: "pandit@navdhya.dev",
      phone: "+91 00000 00000",
      city: "Ghaziabad",
      country: "India",
      timezone: "Asia/Kolkata",
      languages: ["Hindi", "English", "Sanskrit"],
      completionPercent: 96,
      memberSince: "2024-11-02",
    },
    Vendor: {
      name: "Samagri Supply Desk",
      email: "vendor@navdhya.dev",
      phone: "+91 00000 00000",
      city: "Noida",
      country: "India",
      timezone: "Asia/Kolkata",
      languages: ["English", "Hindi"],
      completionPercent: 92,
      memberSince: "2025-01-20",
    },
    Manager: {
      name: "Ishita Operations",
      email: "manager@navdhya.dev",
      phone: "+91 00000 00000",
      city: "Gurgaon",
      country: "India",
      timezone: "Asia/Kolkata",
      languages: ["English", "Hindi"],
      completionPercent: 100,
      memberSince: "2024-07-10",
    },
    Admin: {
      name: "Platform Admin",
      email: "admin@navdhya.dev",
      phone: "+91 00000 00000",
      city: "New Delhi",
      country: "India",
      timezone: "Asia/Kolkata",
      languages: ["English", "Hindi"],
      completionPercent: 100,
      memberSince: "2024-03-01",
    },
    SuperAdmin: {
      name: "Super Admin",
      email: "superadmin@navdhya.dev",
      phone: "+91 00000 00000",
      city: "New Delhi",
      country: "India",
      timezone: "Asia/Kolkata",
      languages: ["English", "Hindi", "Sanskrit"],
      completionPercent: 100,
      memberSince: "2024-01-01",
    },
  };

  return {
    id: `profile-${role.toLowerCase()}`,
    role,
    ...baseByRole[role],
  };
}

export function getMockSummary(role: Role): DashboardSummary {
  const map: Record<Role, DashboardSummary> = {
    User: {
      primaryStat: { label: "Upcoming ceremonies", value: "3", change: "+1 this month" },
      secondaryStats: [
        { label: "Saved pandits", value: "6", helper: "Shortlisted for future rituals" },
        { label: "Payments ready", value: "2", helper: "Awaiting confirmation" },
        { label: "Support threads", value: "1", helper: "Open booking clarification" },
      ],
    },
    Pandit: {
      primaryStat: { label: "New leads", value: "14", change: "+4 since yesterday" },
      secondaryStats: [
        { label: "This week ceremonies", value: "9", helper: "4 confirmed, 5 tentative" },
        { label: "Reviews average", value: "4.9", helper: "From 122 household reviews" },
        { label: "Verification score", value: "98%", helper: "Profile and documents complete" },
      ],
    },
    Vendor: {
      primaryStat: { label: "Orders in flight", value: "18", change: "+3 today" },
      secondaryStats: [
        { label: "Catalog health", value: "95%", helper: "Low-stock alerts under control" },
        { label: "Invoices due", value: "4", helper: "Awaiting payout reconciliation" },
        { label: "Fulfillment SLA", value: "97%", helper: "Measured over last 30 days" },
      ],
    },
    Manager: {
      primaryStat: { label: "Open operation queues", value: "11", change: "-2 from last week" },
      secondaryStats: [
        { label: "City launches", value: "5", helper: "2 blocked, 3 in progress" },
        { label: "Quality escalations", value: "3", helper: "Needing same-day action" },
        { label: "Assignment load", value: "27", helper: "Across ops and content teams" },
      ],
    },
    Admin: {
      primaryStat: { label: "Platform actions", value: "42", change: "Across people, SEO, and payouts" },
      secondaryStats: [
        { label: "SEO queue", value: "58", helper: "Awaiting QA or approval" },
        { label: "User issues", value: "7", helper: "Escalations needing review" },
        { label: "Payout holds", value: "2", helper: "Manual verification required" },
      ],
    },
    SuperAdmin: {
      primaryStat: { label: "Total users", value: "12,400", change: "+12.5% from last month" },
      secondaryStats: [
        { label: "Monthly revenue", value: "₹8.2L", helper: "↑18.3% growth" },
        { label: "System uptime", value: "99.9%", helper: "Last 30 days" },
        { label: "Active cities", value: "24", helper: "+2 new this month" },
      ],
    },
  };

  return map[role];
}

export function getMockBookings(): BookingRecord[] {
  return [
    {
      id: "bk-1001",
      title: "Griha Pravesh Puja",
      serviceSlug: "griha-pravesh",
      scheduledFor: "2026-06-14T10:00:00+05:30",
      location: "South Delhi",
      status: "confirmed",
      counterpartName: "Pandit Ravi Mishra",
    },
    {
      id: "bk-1002",
      title: "Satyanarayan Katha",
      serviceSlug: "satyanarayan-katha",
      scheduledFor: "2026-06-27T17:30:00+05:30",
      location: "Noida",
      status: "pending",
      counterpartName: "Pandit Suresh Upadhyay",
    },
  ];
}

export function getMockPanditLeads(): PanditLead[] {
  return [
    {
      id: "lead-201",
      ceremony: "Mundan Ceremony",
      city: "Gurgaon",
      requestedFor: "2026-06-09",
      status: "new",
      householdName: "Kapoor Family",
    },
    {
      id: "lead-202",
      ceremony: "Navagraha Puja",
      city: "Dwarka",
      requestedFor: "2026-06-12",
      status: "shortlisted",
      householdName: "Sharma Family",
    },
  ];
}

export function getMockVendorOrders(): VendorOrder[] {
  return [
    {
      id: "vo-310",
      category: "Puja Samagri",
      destination: "West Delhi",
      scheduledFor: "2026-06-08",
      status: "packing",
      itemCount: 16,
    },
    {
      id: "vo-311",
      category: "Havan Materials",
      destination: "Noida",
      scheduledFor: "2026-06-10",
      status: "queued",
      itemCount: 12,
    },
  ];
}

export function getMockManagerQueue(): ManagerQueueItem[] {
  return [
    {
      id: "mq-401",
      queue: "operations",
      title: "Resolve late-arrival trend for Noida weekend bookings",
      owner: "Field Ops",
      status: "active",
      dueDate: "2026-06-04",
    },
    {
      id: "mq-402",
      queue: "quality",
      title: "Review refreshed diaspora brand pages before publish",
      owner: "SEO QA",
      status: "open",
      dueDate: "2026-06-06",
    },
  ];
}

export function getMockSeoQueue(): SeoQueueItem[] {
  return [
    {
      id: "seo-501",
      slug: "us-new-york-online-puja",
      cluster: "services",
      market: "US",
      status: "qa",
      pageFamily: "intl-service",
    },
    {
      id: "seo-502",
      slug: "voice-faq-choosing-pandit-for-housewarming",
      cluster: "faq",
      market: "India",
      status: "approved",
      pageFamily: "voice-faq",
    },
  ];
}
