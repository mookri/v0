# Dependency Rules
1. Applications may depend on packages.
2. Applications must not depend directly on other applications.
3. Packages must not depend on apps.
4. Domain logic must not depend on Next.js.
5. Domain logic must not depend on React.
6. Domain logic must not depend directly on infrastructure.
7. Infrastructure implements ports/contracts.
8. Package internals are private unless exported.
9. No deep imports into another package's internal directories.
10. Business logic must not be implemented in route components.
11. UI components must not become the source of business truth.
12. Database access must follow capability ownership.
13. Packages are self-contained: no imports from another package's source
    (enforced by per-package `rootDir` in `tsc -p`; cross-package source
    imports fail the build). Cross-capability needs are met by
    consumer-owned ports (dependency inversion: the capability declares
    the port, the provider satisfies it structurally — e.g. partner
    `ChallengePort` vs identity `MemoryOtpProvider`). Apps, infrastructure,
    and tests may compose across packages.
14. Always `@` imports (`@/`, `@navdhya/`, `@infra/`); no `..` in any
    specifier (CI-enforced by `tests/architecture/import-alias.test.ts`).
