// lib/dlo-x/types.ts
export type Role = 'Admin' | 'User' | 'Pandit' | 'Vendor' | 'Manager';
export type Tenant = 'default' | 'tenant2';
export type DeviceType = 'mobile' | 'desktop' | 'tablet';
export type ShellName = 
  | 'MinimalShell'
  | 'MainShell'
  | 'AuthShell'
  | 'DashboardShell'
  | 'AdminShell'
  | 'VendorShell'
  | 'PoojaShell'
  | 'MobileShell'
  | 'ChromeShell';

export interface LayoutPolicy {
  id: string;
  path: string;
  role?: Role;
  tenant?: Tenant;
  device?: DeviceType;
  shells: ShellName[];
  priority: number;
  abTest?: {
    seed: string;
    percentage: number;
  };
}

export interface RequestContext {
  requestId: string;
  tenantId: Tenant;
  userRole: Role;
  device: DeviceType;
  seed: string;
  path: string;
}

export interface ResolvedLayout {
  shells: ShellName[];
  policyId: string;
  abTestVariant?: 'A' | 'B';
}