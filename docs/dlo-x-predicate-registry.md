# DLO-X Predicate Registry

## Path Predicates

Predicates that match specific paths or path patterns:

```typescript
// Exact path match
path('/dashboard')

// Wildcard path match
path('/dashboard/*')

// Regex path match
path(/^\/admin\/.+$/)
```

## Role Predicates

Predicates that match specific user roles:

```typescript
// Single role match
role('Admin')

// Multiple role match
role(['Admin', 'Manager'])

// Any authenticated user
role('User')
```

## Tenant Predicates

Predicates that match specific tenants:

```typescript
// Single tenant match
tenant('default')

// Multiple tenant match
tenant(['default', 'tenant2'])
```

## Device Predicates

Predicates that match specific device types:

```typescript
// Single device match
device('mobile')

// Multiple device match
device(['mobile', 'tablet'])
```

## Custom Predicates

You can create custom predicates for complex conditions:

```typescript
// Custom predicate function
const isWeekend = (context: RequestContext) => {
  const day = new Date().getDay();
  return day === 0 || day === 6;
};

// Use in policy
PolicyBuilder.create()
  .id('weekend-layout')
  .path('/')
  .use(isWeekend) // Custom predicate
  .use('MainShell')
  .priority(15)
  .build();
```

## Combining Predicates

Predicates can be combined using logical operators:

```typescript
// AND condition (all predicates must match)
PolicyBuilder.create()
  .id('admin-mobile')
  .path('/admin/*')
  .role('Admin')
  .device('mobile')
  .use('AdminShell', 'MobileShell')
  .priority(25)
  .build();

// OR condition (any predicate can match)
// This would require a custom implementation
const isAdminOrManager = (context: RequestContext) => {
  return context.userRole === 'Admin' || context.userRole === 'Manager';
};
```