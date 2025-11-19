# DLO-X Naming Conventions

## Shell Components

- Use PascalCase: `MainShell`, `AuthShell`, `DashboardShell`
- Suffix with `Shell`: `*[Shell]`
- Examples: `MinimalShell`, `MainShell`, `AuthShell`

## Policy Files

- Use kebab-case for filenames: `user-dashboard-policy.ts`
- Use PascalCase for policy names: `UserDashboardPolicy`
- Suffix with `Policy`: `*[Policy]`

## Layout Policies

- Use descriptive IDs: `home-page`, `user-dashboard`, `admin-panel`
- Use kebab-case for paths: `/user/dashboard`, `/admin/panel`

## TypeScript Types

- Use PascalCase for interfaces: `LayoutPolicy`, `RequestContext`
- Use PascalCase for types: `Role`, `Tenant`, `DeviceType`

## Functions

- Use camelCase: `resolveLayoutTree`, `findMatchingPolicies`
- Be descriptive: `generateShell`, `validatePolicy`

## Variables

- Use camelCase: `layoutPolicies`, `requestContext`
- Be descriptive: `selectedPolicy`, `matchingPolicies`

## Constants

- Use UPPER_SNAKE_CASE: `LAYOUT_POLICY_VERSION`, `DEFAULT_TENANT`
- Be descriptive: `MAX_CACHE_SIZE`, `DEFAULT_PRIORITY`

## CLI Commands

- Use kebab-case: `dlo generate-shell`, `dlo validate-policies`
- Be consistent: `dlo init`, `dlo inspect`, `dlo coverage`