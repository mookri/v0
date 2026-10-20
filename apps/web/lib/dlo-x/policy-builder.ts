// lib/dlo-x/policy-builder.ts
import { z } from 'zod';
import { LayoutPolicy, Role, Tenant, DeviceType, ShellName } from './types';

// Zod schema for strict policy validation
export const layoutPolicySchema = z.object({
  id: z.string().min(1),
  path: z.string().min(1),
  role: z.enum(['Admin', 'User', 'Pandit', 'Vendor', 'Manager', 'SuperAdmin']).optional(),
  tenant: z.enum(['default', 'tenant2']).optional(),
  device: z.enum(['mobile', 'desktop', 'tablet']).optional(),
  shells: z.array(z.enum([
    'MinimalShell',
    'MainShell',
    'AuthShell',
    'DashboardShell',
    'AdminShell',
    'VendorShell',
    'PoojaShell',
    'MobileShell',
    'ChromeShell'
  ])).min(1),
  priority: z.number().min(0),
  abTest: z.object({
    seed: z.string().min(1),
    percentage: z.number().min(0).max(100)
  }).optional()
});

// Policy builder DSL
export class PolicyBuilder {
  private policy: Partial<LayoutPolicy> = {
    shells: []
  };

  static create(): PolicyBuilder {
    return new PolicyBuilder();
  }

  id(id: string): this {
    this.policy.id = id;
    return this;
  }

  path(path: string): this {
    this.policy.path = path;
    return this;
  }

  role(role: Role): this {
    this.policy.role = role;
    return this;
  }

  tenant(tenant: Tenant): this {
    this.policy.tenant = tenant;
    return this;
  }

  device(device: DeviceType): this {
    this.policy.device = device;
    return this;
  }

  use(...shells: ShellName[]): this {
    this.policy.shells = shells;
    return this;
  }

  priority(priority: number): this {
    this.policy.priority = priority;
    return this;
  }

  abTest(seed: string, percentage: number): this {
    this.policy.abTest = { seed, percentage };
    return this;
  }

  build(): LayoutPolicy {
    if (!this.policy.id) throw new Error('Policy ID is required');
    if (!this.policy.path) throw new Error('Policy path is required');
    if (!this.policy.shells || this.policy.shells.length === 0) throw new Error('At least one shell is required');
    if (this.policy.priority === undefined) throw new Error('Priority is required');

    // Validate with Zod
    const validatedPolicy = layoutPolicySchema.parse(this.policy);
    return validatedPolicy as LayoutPolicy;
  }
}