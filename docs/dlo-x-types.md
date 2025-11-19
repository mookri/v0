# DLO-X TypeScript Types

## Core Types

```typescript
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
```

## LayoutPolicy

```typescript
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
```

## RequestContext

```typescript
export interface RequestContext {
  requestId: string;
  tenantId: Tenant;
  userRole: Role;
  device: DeviceType;
  seed: string;
  path: string;
}
```

## ResolvedLayout

```typescript
export interface ResolvedLayout {
  shells: ShellName[];
  policyId: string;
  abTestVariant?: 'A' | 'B';
}
```

## DLOXHeaders

```typescript
interface DLOXHeaders {
  'x-request-id': string;
  'x-layout-policy-version': string;
  'x-layout-shells': string;
  'x-tenant-id': string;
  'x-user-role': string;
}
```