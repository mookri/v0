# DLO-X Folder Structure

```
project-root/
├── app/                          # Next.js app directory
│   ├── layout.tsx               # Minimal root layout (no UI)
│   ├── page.tsx                 # Home page using LayoutOrchestrator
│   └── [route-groups]/          # Route groups
│       └── page.tsx            # Pages using LayoutOrchestrator
├── components/
│   └── dlo-x/                  # DLO-X components
│       ├── LayoutOrchestrator.tsx  # Main orchestrator component
│       ├── shells/             # Shell components
│       │   ├── MinimalShell.tsx
│       │   ├── MainShell.tsx
│       │   ├── AuthShell.tsx
│       │   ├── DashboardShell.tsx
│       │   ├── AdminShell.tsx
│       │   ├── VendorShell.tsx
│       │   ├── PoojaShell.tsx
│       │   ├── MobileShell.tsx
│       │   └── ChromeShell.tsx
│       └── [other-components]/ # Other DLO-X components
├── lib/
│   └── dlo-x/                  # DLO-X core logic
│       ├── types.ts            # Type definitions
│       ├── policy-builder.ts   # Policy builder DSL
│       ├── layout-policies.ts  # Layout policies
│       ├── layout-registry.ts  # Shell component registry
│       ├── layout-resolver.ts  # Layout resolution logic
│       ├── ab-testing.ts       # A/B testing utilities
│       ├── cache.ts            # Caching utilities
│       ├── telemetry.ts        # Telemetry service
│       └── backend-client.ts   # Backend integration utilities
├── bin/
│   └── dlo.ts                  # CLI tool
├── middleware.ts               # Next.js middleware
└── docs/
    ├── dlo-x-file-templates.md
    ├── dlo-x-folder-structure.md
    └── dlo-x-naming-conventions.md
```