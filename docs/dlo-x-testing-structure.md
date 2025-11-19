# DLO-X Testing Structure

## Unit Tests

### Policy Builder Tests

```typescript
// __tests__/policy-builder.test.ts
import { PolicyBuilder } from '../lib/dlo-x/policy-builder';

describe('PolicyBuilder', () => {
  it('should create a valid policy', () => {
    const policy = PolicyBuilder.create()
      .id('test-policy')
      .path('/test')
      .use('MainShell')
      .priority(10)
      .build();
      
    expect(policy.id).toBe('test-policy');
    expect(policy.path).toBe('/test');
    expect(policy.shells).toEqual(['MainShell']);
    expect(policy.priority).toBe(10);
  });
  
  it('should validate required fields', () => {
    expect(() => {
      PolicyBuilder.create()
        .path('/test')
        .use('MainShell')
        .priority(10)
        .build();
    }).toThrow('Policy ID is required');
  });
});
```

### Layout Resolver Tests

```typescript
// __tests__/layout-resolver.test.ts
import { resolveLayoutTree } from '../lib/dlo-x/layout-resolver';
import { RequestContext } from '../lib/dlo-x/types';

describe('resolveLayoutTree', () => {
  it('should resolve layout for home page', async () => {
    const context: RequestContext = {
      requestId: 'test-request',
      tenantId: 'default',
      userRole: 'User',
      device: 'desktop',
      seed: 'test-seed',
      path: '/'
    };
    
    const result = await resolveLayoutTree(context);
    expect(result.shells).toContain('MainShell');
  });
  
  it('should handle unmatched paths with minimal shell', async () => {
    const context: RequestContext = {
      requestId: 'test-request',
      tenantId: 'default',
      userRole: 'User',
      device: 'desktop',
      seed: 'test-seed',
      path: '/non-existent-path'
    };
    
    const result = await resolveLayoutTree(context);
    expect(result.shells).toEqual(['MinimalShell']);
  });
});
```

### Shell Component Tests

```typescript
// __tests__/shells/MainShell.test.tsx
import { render, screen } from '@testing-library/react';
import { MainShell } from '../../components/dlo-x/shells/MainShell';

describe('MainShell', () => {
  it('should render children', () => {
    render(
      <MainShell>
        <div>Test Content</div>
      </MainShell>
    );
    
    expect(screen.getByText('Test Content')).toBeInTheDocument();
  });
});
```

## Integration Tests

### End-to-End Layout Resolution

```typescript
// __tests__/integration/layout-resolution.test.ts
import { resolveLayoutTree } from '../../lib/dlo-x/layout-resolver';
import { PolicyBuilder } from '../../lib/dlo-x/policy-builder';

describe('Layout Resolution Integration', () => {
  beforeAll(() => {
    // Add test policies
    const testPolicy = PolicyBuilder.create()
      .id('test-policy')
      .path('/test')
      .role('Admin')
      .use('AdminShell')
      .priority(20)
      .build();
      
    // Add to layout policies (this would require a mock)
  });
  
  it('should resolve AdminShell for admin users on /test path', async () => {
    const context = {
      requestId: 'test-request',
      tenantId: 'default',
      userRole: 'Admin',
      device: 'desktop',
      seed: 'test-seed',
      path: '/test'
    };
    
    const result = await resolveLayoutTree(context);
    expect(result.shells).toContain('AdminShell');
  });
});
```

## CLI Tool Tests

```bash
# Test CLI commands
npm run dlo init
npm run dlo generate shell TestShell
npm run dlo generate page test-page
npm run dlo validate
```

## Test Configuration

### Jest Configuration

```javascript
// jest.config.ts
export default {
  testEnvironment: 'node',
  roots: ['<rootDir>/__tests__'],
  testMatch: ['**/*.test.ts', '**/*.test.tsx'],
  transform: {
    '^.+\\.tsx?$': 'ts-jest'
  },
  setupFilesAfterEnv: ['<rootDir>/jest.setup.ts'],
  moduleNameMapping: {
    '^@/(.*)$': '<rootDir>/$1'
  }
};
```

### Test Setup

```typescript
// jest.setup.ts
import '@testing-library/jest-dom';

// Mock layout registry
jest.mock('../lib/dlo-x/layout-registry', () => ({
  layoutRegistry: {
    MinimalShell: () => '<div>MinimalShell</div>',
    MainShell: () => '<div>MainShell</div>',
    AuthShell: () => '<div>AuthShell</div>',
    DashboardShell: () => '<div>DashboardShell</div>',
    AdminShell: () => '<div>AdminShell</div>',
    VendorShell: () => '<div>VendorShell</div>',
    PoojaShell: () => '<div>PoojaShell</div>',
    MobileShell: () => '<div>MobileShell</div>',
    ChromeShell: () => '<div>ChromeShell</div>'
  },
  registerShell: jest.fn(),
  getShell: jest.fn()
}));
```