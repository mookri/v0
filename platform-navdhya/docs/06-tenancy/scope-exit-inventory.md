# Tenancy Scope-Exit Inventory

**Status:** Active (T-AP03-01 / T-AP03-02, ADR-0069)  
**Enforced By:** [`tests/Architecture/ScopeExitBudgetTest.php`](../../tests/Architecture/ScopeExitBudgetTest.php)

## Policy

1. **HTTP controllers MUST have zero `withoutGlobalScopes()` calls.** Any legitimate anonymous public browse or webhook tenant-discovery query MUST run inside `App\Platform\Tenancy\PlatformScope::run(reason, closure, tenantId?)` with a non-empty audit reason and automatic `finally` restoration of `TenantContext`.
2. **Legacy `withoutGlobalScopes()` sites are capped at a strict monotonic ceiling (`<= 54` calls).** New code MUST use ambient `TenantContext` or `PlatformScope::run()`; the legacy count may only decrease as services are migrated.
3. **Zero unreviewed cross-tenant exits.** Every site below carries a named owner and technical justification.

---

## 1. Audited `PlatformScope::run()` Call Sites

| File | Reason(s) | Scope Mode | Justification | Owner |
| --- | --- | --- | --- | --- |
| `app/Modules/Catalog/Http/Controllers/CatalogItemController.php` | `public_catalog_browse`, `public_catalog_categories` | Scoped (`$tenantId`) / Platform (`null`) | Public Quick-Book catalog browse for validated ACTIVE tenant & platform-default category tree | Catalog |
| `app/Modules/Customers/Http/Controllers/CustomerWebhookController.php` | `customer_webhook_tenant_lookup` | System (`null`) | Unauthenticated SMS/WhatsApp webhook locates delivery row by `provider_message_id` prior to binding tenant context | Customers |
| `app/Modules/Vendor/Application/StockReservationService.php` | `vendor_stock_hold_kit`, `vendor_stock_commit_hold`, `vendor_stock_release_hold`, `vendor_stock_consume`, `vendor_stock_transfer`, `vendor_stock_resolve_warehouses` | Scoped (`$tenantId`) | Multi-tenant booking & warehouse stock engine invoked from Booking checkout & console with explicit `$tenantId` | Vendor |
| `app/Modules/Vendor/VendorOrderService.php` | `vendor_order_shipment_transition` | Scoped (`$order->tenant_id`) | Shipment row sync during order dispatch transition | Vendor |
| `app/Modules/Catalog/Domain/Pricing/TieredPriceResolver.php` | `catalog_tiered_price_resolution` | Scoped (`$tenantId`) | Multi-axis price resolution when explicit `$tenantId` is passed | Catalog |
| `app/Modules/Catalog/Models/CatalogCategory.php` | `catalog_category_parent_lookup`, `catalog_category_parent_path_lookup` | Scoped (`$category->tenant_id`) | Self-referential materialized-path tree calculation on save/create | Catalog |
| `app/Modules/Onboarding/Services/AppealService.php` | `onboarding_appeal_submit`, `onboarding_appeal_decide` | Scoped (`$tenantId`) | Partner appeal submission & independent reviewer adjudication | Onboarding |
| `app/Domain/Privacy/Consent/ConsentManager.php` | `privacy_consent_withdraw`, `privacy_consent_check`, `privacy_consent_list` | Scoped (`$tenantId`) | DPDP purpose-scoped consent ledger evaluation | Privacy |

---

## 2. Monotonic Legacy `withoutGlobalScopes()` Allowlist (`54` total calls)

> Hard limit enforced by `ScopeExitBudgetTest`: no additions allowed, zero permitted in `app/**/Http/Controllers/`.

| File | Count | Category | Justification | Owner |
| --- | ---: | --- | --- | --- |
| `app/Domain/Verification/Institutions/TempleAuthorityService.php` | 6 | Cross-entity verification | Institutional authority & duplicate entity graph checks across applications | Verification |
| `app/Platform/Authz/Console/SyncPermissionsCommand.php` | 4 | Console / System | Syncs `Policy` & `CapabilityMetadata` rows from module manifests | Platform Authz |
| `app/Domain/Verification/Claims/ClaimManager.php` | 3 | Verification domain | Cross-surface verification claim aggregation & expiry | Verification |
| `app/Domain/Verification/Fiscal/PayoutEligibilityGuard.php` | 3 | Verification domain | Fiscal gate verifying bank instrument & active claims pre-payout | Verification |
| `app/Domain/Verification/Issuers/IssuerOutreachService.php` | 3 | Verification domain | Credential deduplication & issuer verification callbacks | Verification |
| `app/Modules/Search/Contracts/PanditSearchProvider.php` | 3 | Search indexer | Streaming `cursor()` index feed over active pandit profiles | Search |
| `app/Modules/Vendor/Console/AccruePayoutsCommand.php` | 3 | Console / Batch | Cross-tenant periodic payout accrual batch job | Vendor |
| `app/Domain/Verification/Console/VerifyAuditAndEvidenceChainCommand.php` | 2 | Console / Audit | Walks immutable tamper-evident audit chains across tenants | Verification |
| `app/Domain/Verification/Issuers/CompetencyAssessor.php` | 2 | Verification domain | Competency claim scope lookups | Verification |
| `app/Domain/Verification/Issuers/CredentialRevocationService.php` | 2 | Verification domain | Revokes credential & dependent claims | Verification |
| `app/Domain/Verification/Projector/VerificationProjector.php` | 2 | Verification projector | Projects claim state changes to search/eligibility read models | Verification |
| `app/Domain/Verification/Risk/BackgroundCheckService.php` | 2 | Verification domain | Updates background check state on async provider callbacks | Verification |
| `app/Modules/Catalog/Console/UnmappedCategoriesAuditCommand.php` | 2 | Console / Audit | Audits unmapped catalog items across tenants | Catalog |
| `app/Modules/Vendor/Console/ReconcileStockCommand.php` | 2 | Console / Batch | Cross-tenant stock ledger reconciliation job | Vendor |
| `app/Domain/Privacy/Erasure/ProcessPartnerErasureJob.php` | 1 | Queued erasure job | Executes async partner DPDP erasure across tenant records | Privacy |
| `app/Domain/Verification/Console/BackfillLegacyClaimsCommand.php` | 1 | Console / Migration | Backfills legacy partner assertions into claim rows | Verification |
| `app/Domain/Verification/Console/ExpireVerificationClaimsCommand.php` | 1 | Console / Scheduled | Sweeps expired verification claims across tenants | Verification |
| `app/Domain/Verification/Decisions/DecisionService.php` | 1 | Verification domain | Decision recording lookup with explicit `tenant_id` predicate | Verification |
| `app/Domain/Verification/Fiscal/FiscalIntakeService.php` | 1 | Verification domain | Instrument update in fiscal intake workflow | Verification |
| `app/Domain/Verification/Fiscal/FreezePayoutOnDiscrepancyListener.php` | 1 | Event listener | Freezes payouts across matching bank instrument discrepancy events | Verification |
| `app/Domain/Verification/Institutions/EntityRelationshipGraphService.php` | 1 | Verification domain | Queries entity relationship edges with explicit `tenant_id` | Verification |
| `app/Domain/Verification/Lifecycle/MaterialChangeReverificationListener.php` | 1 | Event listener | Revokes claims upon material profile changes | Verification |
| `app/Domain/Verification/Lifecycle/ReviewerQualityMetricsService.php` | 1 | Verification metrics | Computes overturn/appeal metrics over historical decisions | Verification |
| `app/Modules/Customers/Console/PurgeTelemetryCommand.php` | 1 | Console / Scheduled | Cross-tenant telemetry retention purge | Customers |
| `app/Modules/Customers/Jobs/ProcessCustomerErasureJob.php` | 1 | Queued erasure job | Async customer erasure across tenant records | Customers |
| `app/Modules/Customers/Telemetry/WarehouseExportService.php` | 1 | Telemetry batch | Redacted warehouse export stream | Customers |
| `app/Platform/Audit/AuditWriter.php` | 1 | Audit hash chain | Reads previous tail hash inside audit chain append | Platform Audit |
| `app/Platform/Identity/Federation/AuthEventRecorder.php` | 1 | Auth hash chain | Reads previous tail hash inside auth event chain append | Platform Identity |
| `app/Platform/Identity/Federation/Console/VerifyAuthEventChainCommand.php` | 1 | Console / Audit | Verifies central auth event hash chain integrity | Platform Identity |

