import type { Role } from "@/lib/dlo-x/types";

export interface AuthSession {
  userId: string;
  email: string;
  role: Role;
  isAuthenticated: boolean;
  profileCompleted: boolean;
  verificationStatus: "pending" | "verified" | "restricted";
}

export interface AccountProfile {
  id: string;
  name: string;
  email: string;
  phone: string;
  role: Role;
  city: string;
  country: string;
  timezone: string;
  languages: string[];
  completionPercent: number;
  memberSince: string;
}

export interface RoleLanding {
  role: Role;
  label: string;
  href: string;
  description: string;
}

export interface BookingRecord {
  id: string;
  title: string;
  serviceSlug: string;
  scheduledFor: string;
  location: string;
  status: "pending" | "confirmed" | "completed" | "cancelled";
  counterpartName: string;
}

export interface PanditLead {
  id: string;
  ceremony: string;
  city: string;
  requestedFor: string;
  status: "new" | "shortlisted" | "confirmed" | "closed";
  householdName: string;
}

export interface VendorOrder {
  id: string;
  category: string;
  destination: string;
  scheduledFor: string;
  status: "queued" | "packing" | "dispatched" | "delivered";
  itemCount: number;
}

export interface ManagerQueueItem {
  id: string;
  queue: "operations" | "quality" | "city-launch" | "support";
  title: string;
  owner: string;
  status: "open" | "active" | "blocked" | "done";
  dueDate: string;
}

export interface SeoQueueItem {
  id: string;
  slug: string;
  cluster: string;
  market: string;
  status: "draft" | "qa" | "approved" | "published";
  pageFamily: string;
}

export interface DashboardSummary {
  primaryStat: {
    label: string;
    value: string;
    change: string;
  };
  secondaryStats: Array<{
    label: string;
    value: string;
    helper: string;
  }>;
}
