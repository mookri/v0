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
