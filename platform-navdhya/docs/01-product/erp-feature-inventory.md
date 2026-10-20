# ERP Feature Inventory

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27 · **Migrated from:** planning/02 (documentation tree migration; completeness totals corrected per migration decision D8)

This inventory catalogs the **complete feature surface of enterprise ERP
suites** (SAP Business One / Odoo / NetSuite / Microsoft Dynamics 365
Business Central class, 2025–2026 surface) and assigns each feature a v1
disposition with the Navdhya architecture reference that implements or
absorbs it.

Disposition codes per [spec methodology §2](../specs/README.md): `COVERED`, `NEW-v1`,
`DEFERRED`, `OUT`.

Architecture targets for `NEW-v1` rows:
**[[ERP parity domains](../14-domains/crm/crm.md) — Phase 4a: ERP Feature Parity](../14-domains/crm/crm.md)**
unless the row says otherwise.

---

## 1. CRM (Customer Relationship Management)

| ID | Feature | ERP behavior (summary) | Disposition | Navdhya implementation |
|----|---------|------------------------|-------------|------------------------|
| ERP-001 | Leads (prospects) | Capture, qualify, convert to opportunities | NEW-v1 | [ERP parity domains §2](../14-domains/crm/crm.md) (CRM module `crm`) |
| ERP-002 | Opportunities/deals | Sales pipeline stages with amounts | NEW-v1 | [ERP parity domains §2](../14-domains/crm/crm.md) |
| ERP-003 | Pipeline kanban & stage automation | Drag-drop, stage-entry actions | NEW-v1 | [ERP parity domains §2.3](../14-domains/crm/crm.md) (workflow state machine per pipeline) |
| ERP-004 | Activities (calls/meetings/tasks/notes) | Activity timeline per record | NEW-v1 | [ERP parity domains §2.4](../14-domains/crm/crm.md) (activities, kernel-attached polymorphic) |
| ERP-005 | Lead/opportunity → quote conversion | Convert to sales quote | NEW-v1 | [ERP parity domains §2.6](../14-domains/crm/crm.md) → sales quote ([commercial core domains §5](../14-domains/sales/sales.md)) |
| ERP-006 | Campaigns & lead sources | Marketing campaign tracking | NEW-v1 | [ERP parity domains §2.7](../14-domains/crm/crm.md) (campaigns, lead-source attribution) |
| ERP-007 | Sales forecasting from pipeline | Weighted forecast | COVERED | [intelligence §2](../17-ai/intelligence.md) forecasting consumes pipeline events ([ERP parity domains §2.5](../14-domains/crm/crm.md)) |
| ERP-008 | Customer 360 view | Unified customer timeline | NEW-v1 | [ERP parity domains §2.8](../14-domains/crm/crm.md) (read-model joining customers + sales + service via contracts/events) |
| ERP-009 | Contact relationship roles | Decision-maker/influencer roles | NEW-v1 | [ERP parity domains §2.4](../14-domains/crm/crm.md) contact-role field |
| ERP-010 | Quotas & commission | Sales targets and commissions | DEFERRED (P4a+) | seam: payroll module + analytics |

## 2. Sales (advanced)

| ID | Feature | ERP behavior | Disposition | Navdhya implementation |
|----|---------|--------------|-------------|------------------------|
| ERP-020 | Quoting with approvals | Quote lifecycle | COVERED | [commercial core domains §5](../14-domains/sales/sales.md) quotes |
| ERP-021 | Sales orders with partial delivery | Backorders | COVERED | [commercial core domains §5](../14-domains/sales/sales.md) + [commerce domains §4](../14-domains/commerce/promotions-shipping-returns.md) partial shipments |
| ERP-022 | Blanket/framework orders | Long-term order with call-offs | NEW-v1 | [ERP parity domains §3.2](../14-domains/sales/sales.md) (blanket orders, release against) |
| ERP-023 | Credit management & holds | Credit limits, holds, release | COVERED | [commercial core domains §2](../14-domains/crm/customers.md) credit chain + workflow approval |
| ERP-024 | Returns & RMA | Return authorization | COVERED | [commerce domains §5](../14-domains/commerce/promotions-shipping-returns.md) returns |
| ERP-025 | Price lists & discounts | Tiered pricing | COVERED | [commercial core domains §4](../14-domains/sales/pricing.md) pricing + [commerce domains §3](../14-domains/commerce/promotions-shipping-returns.md) discounts |
| ERP-026 | Sales contracts / subscriptions | Recurring billing | COVERED | [commerce domains §7](../14-domains/commerce/giftcards-subscriptions-pos.md) subscriptions |
| ERP-027 | Backorder management | Track & fulfill backorders | NEW-v1 | [ERP parity domains §3.3](../14-domains/sales/sales.md) (backorder report + allocation run) |
| ERP-028 | Consignment sales | Stock at customer until sold | DEFERRED | seam: inventory location kinds + customer-owned stock |
| ERP-029 | Pro-forma invoices | Pre-invoice documents | NEW-v1 | [ERP parity domains §3.4](../14-domains/finance/invoicing.md) (pro-forma kind on invoicing) |

## 3. Purchasing (advanced)

| ID | Feature | ERP behavior | Disposition | Navdhya implementation |
|----|---------|--------------|-------------|------------------------|
| ERP-040 | Purchase requisitions | Internal request → PO | NEW-v1 | [ERP parity domains §4.2](../14-domains/procurement/purchasing.md) (requisitions with approval workflow) |
| ERP-041 | RFQ / bid comparison | Multi-vendor comparison | COVERED | [commercial core domains §6.4](../14-domains/procurement/purchasing.md) flow 4 RFQ |
| ERP-042 | Purchase orders with approvals | PO lifecycle | COVERED | [commercial core domains §6](../14-domains/procurement/purchasing.md) |
| ERP-043 | Blanket purchase agreements | Framework POs | NEW-v1 | [ERP parity domains §4.3](../14-domains/procurement/purchasing.md) (blanket POs, release orders) |
| ERP-044 | Goods receipt & inspection | Receive + QC | COVERED (+QC NEW) | [commercial core domains §6](../14-domains/procurement/purchasing.md) GRN; inspection → [ERP parity domains §7](../14-domains/manufacturing/quality.md) quality |
| ERP-045 | Landed costs | Freight/duty allocation to item cost | NEW-v1 | [ERP parity domains §4.4](../14-domains/procurement/purchasing.md) (landed-cost allocation to inventory valuation) |
| ERP-046 | Vendor evaluation & scorecards | Performance rating | NEW-v1 | [ERP parity domains §4.5](../14-domains/procurement/suppliers.md) (extends [commercial core domains §3.1](../14-domains/procurement/suppliers.md) `supplier_performance`) |
| ERP-047 | 3-way match (PO/GRN/invoice) | Bill validation | COVERED | [commercial core domains §6.4](../14-domains/procurement/purchasing.md) flow 3 + bill hold workflow |
| ERP-048 | Subcontracting | Send components to vendor, receive finished | NEW-v1 | [ERP parity domains §4.6](../14-domains/procurement/purchasing.md) (subcontract POs linked to work orders) |

## 4. Inventory & Warehousing (advanced)

| ID | Feature | ERP behavior | Disposition | Navdhya implementation |
|----|---------|--------------|-------------|------------------------|
| ERP-060 | Multi-warehouse & bin locations | Location hierarchy | COVERED | [commercial core domains §7](../14-domains/inventory/inventory.md) warehouses/locations |
| ERP-061 | Serial number tracking | Per-unit lifecycle | NEW-v1 | [ERP parity domains §8.2](../14-domains/inventory/inventory.md) (serial ledger: receive → issue → service) |
| ERP-062 | Lot/batch & expiry tracking | Batch genealogy, FEFO | NEW-v1 | [ERP parity domains §8.3](../14-domains/inventory/inventory.md) (batch ledger + FEFO allocation strategy) |
| ERP-063 | Cycle counting & stock takes | Periodic counts | COVERED | [commercial core domains §7](../14-domains/inventory/inventory.md) cycle counts |
| ERP-064 | Stock valuation methods | FIFO/LIFO/weighted average/standard | NEW-v1 | [ERP parity domains §8.4](../14-domains/inventory/inventory.md) (valuation layers; v1 ships weighted avg + FIFO, standard-cost seam) |
| ERP-065 | Stock aging analysis | Days-in-stock reports | NEW-v1 | [ERP parity domains §8.5](../14-domains/inventory/inventory.md) (aging report on movements) |
| ERP-066 | Reorder points & safety stock | Min/max replenishment | COVERED | [commercial core domains §7](../14-domains/inventory/inventory.md) `ReorderSuggestions` + [enterprise ops domains §6](../14-domains/supply-chain/supply-chain.md) supply chain |
| ERP-067 | Warehouse operations (pick/pack/put-away) | Directed workflows | NEW-v1 | [ERP parity domains §8.6](../14-domains/inventory/inventory.md) (pick lists, put-away suggestions; full WMS DEFERRED) |
| ERP-068 | Consignment stock (vendor-owned) | Vendor stock on premises | DEFERRED | seam: inventory ownership flag |
| ERP-069 | Barcode & scanning workflows | RF scanning | NEW-v1 | [Shopify parity extensions §7.3](../14-domains/commerce/giftcards-subscriptions-pos.md) barcode ops (shared with commerce) |
| ERP-070 | Inter-warehouse transfers with approval | Controlled movement | COVERED | [commercial core domains §7](../14-domains/inventory/inventory.md) transfers + workflow |

## 5. Finance & Accounting (advanced)

| ID | Feature | ERP behavior | Disposition | Navdhya implementation |
|----|---------|--------------|-------------|------------------------|
| ERP-080 | Chart of accounts & GL | Double-entry ledger | COVERED | [finance domains §1](../14-domains/finance/gl.md) |
| ERP-081 | AP & AR sub-ledgers | Payables/receivables | COVERED | [finance domains §3](../14-domains/finance/ar-ap-banking.md)–4 |
| ERP-082 | Bank reconciliation | Statement matching | COVERED | [finance domains §5](../14-domains/finance/ar-ap-banking.md) |
| ERP-083 | Fixed assets & depreciation | Asset register | COVERED | [finance domains §6](../14-domains/finance/assets-budgets-reports.md) |
| ERP-084 | Budgets & budget control | Budgets with enforcement | COVERED | [finance domains §7](../14-domains/finance/assets-budgets-reports.md) |
| ERP-085 | Financial statements | P&L, BS, CF, TB | COVERED | [finance domains §8](../14-domains/finance/assets-budgets-reports.md) |
| ERP-086 | Cost centers & cost allocation | Departmental cost tracking | NEW-v1 | [ERP parity domains §12.2](../14-domains/finance/gl.md) (dimensions on journal lines + cost-center reports) |
| ERP-087 | Profit centers / profitability analysis | P&L by segment | NEW-v1 | [ERP parity domains §12.3](../14-domains/finance/assets-budgets-reports.md) (profitability views via dimensions + analytics) |
| ERP-088 | Cash-flow management & forecasting | Cash position + forecast | COVERED | [finance domains §5](../14-domains/finance/ar-ap-banking.md) banking + [intelligence §2.4](../17-ai/intelligence.md) cash forecast |
| ERP-089 | Multi-currency & FX revaluation | Currency gains/losses | COVERED | [finance domains §1.5](../14-domains/finance/gl.md) multi-currency + revaluation runs |
| ERP-090 | Inter-company accounting | Cross-entity eliminations | DEFERRED (multi-entity) | seam: org tree + dimensions; multi-company DEFERRED |
| ERP-091 | Consolidation | Group consolidation | DEFERRED | seam: multi-currency translation ([finance domains §1.5](../14-domains/finance/gl.md)) |
| ERP-092 | Recurring journal entries | Scheduled postings | NEW-v1 | [ERP parity domains §12.4](../14-domains/finance/gl.md) (recurring journal templates → posting engine) |
| ERP-093 | Payment reminders / dunning | Collections automation | NEW-v1 | [ERP parity domains §12.5](../14-domains/finance/ar-ap-banking.md) (dunning runs on AR aging, notifications) |
| ERP-094 | Expense claims & reimbursements | Employee expenses | NEW-v1 | [ERP parity domains §6](../14-domains/hr/expenses.md) (expenses module) |

## 6. HR & Payroll

| ID | Feature | ERP behavior | Disposition | Navdhya implementation |
|----|---------|--------------|-------------|------------------------|
| ERP-110 | Employee master & org structure | HR records | COVERED | [enterprise ops domains §1](../14-domains/hr/hr.md) HR |
| ERP-111 | Leave management | Requests, balances, accrual | COVERED | [enterprise ops domains §1](../14-domains/hr/hr.md) leave |
| ERP-112 | Attendance & time tracking | Clock in/out | COVERED | [enterprise ops domains §1](../14-domains/hr/hr.md) attendance |
| ERP-113 | Payroll runs | Salary computation | COVERED | [enterprise ops domains §2](../14-domains/payroll/payroll.md) payroll |
| ERP-114 | Statutory deductions (PF/ESI/PT) | India compliance | NEW-v1 | [ERP parity domains §9.4](../15-integrations/connectors/india-statutory.md) (India statutory: PF, ESI, PT computation rules) |
| ERP-115 | Payslips & payroll reports | Employee self-service | COVERED | [enterprise ops domains §2](../14-domains/payroll/payroll.md) payslips |
| ERP-116 | Recruitment/ATS | Job postings, applicants | DEFERRED | seam: HR module structure |
| ERP-117 | Performance management | Appraisals, goals | DEFERRED | seam: HR + workflow |
| ERP-118 | Employee onboarding/offboarding checklists | Process automation | NEW-v1 | [ERP parity domains §6.4](../14-domains/hr/expenses.md) (onboarding checklists via workflow engine) |
| ERP-119 | Shift scheduling & rostering | Workforce scheduling | DEFERRED | seam: attendance policies ([enterprise ops domains §1.2](../14-domains/hr/hr.md)) |
| ERP-120 | Travel & expense requests | Trip approvals | NEW-v1 | [ERP parity domains §6](../14-domains/hr/expenses.md) (expenses module covers travel claims) |
| ERP-121 | Loan & advance management | Employee loans, salary deduction | DEFERRED | seam: payroll line components |

## 7. Projects

| ID | Feature | ERP behavior | Disposition | Navdhya implementation |
|----|---------|--------------|-------------|------------------------|
| ERP-130 | Project & task management | WBS, tasks, milestones | COVERED | [enterprise ops domains §3](../14-domains/projects/projects.md) projects |
| ERP-131 | Timesheets | Time capture | COVERED | [enterprise ops domains §3](../14-domains/projects/projects.md) timesheets |
| ERP-132 | Project billing (T&M/fixed/milestone) | Billing schedules | COVERED | [enterprise ops domains §3](../14-domains/projects/projects.md) project billing |
| ERP-133 | Project budgets & cost tracking | Budget vs actual | COVERED | [enterprise ops domains §3](../14-domains/projects/projects.md) + budgets contract |
| ERP-134 | Resource planning/allocation | Capacity planning | DEFERRED | seam: projects + HR org |
| ERP-135 | Project profitability | Margin analysis | NEW-v1 | [ERP parity domains §12.3](../14-domains/finance/assets-budgets-reports.md) profitability (project dimension) |

## 8. Service / Helpdesk

| ID | Feature | ERP behavior | Disposition | Navdhya implementation |
|----|---------|--------------|-------------|------------------------|
| ERP-150 | Tickets & cases | Incident management | COVERED | [enterprise ops domains §4](../14-domains/service/service.md) service tickets |
| ERP-151 | SLA management | Response/resolution targets | COVERED | [enterprise ops domains §4](../14-domains/service/service.md) SLAs |
| ERP-152 | Knowledge base | Articles | COVERED | [enterprise ops domains §4](../14-domains/service/service.md) KB |
| ERP-153 | Field service & dispatch | On-site visits, work orders | DEFERRED | seam: service module + scheduling |
| ERP-154 | Service contracts & warranties | Coverage tracking | NEW-v1 | [ERP parity domains §10.3](../14-domains/sales/contracts.md) (service contracts linked to serials, [ERP parity domains §8.2](../14-domains/inventory/inventory.md)) |
| ERP-155 | Customer portal (self-service) | Ticket submission | DEFERRED | seam: public buyer API pattern ([commerce domains §1.5](../14-domains/commerce/store-checkout.md)) |

## 9. Manufacturing

| ID | Feature | ERP behavior | Disposition | Navdhya implementation |
|----|---------|--------------|-------------|------------------------|
| ERP-170 | Bill of materials (BOM) | Multi-level BOMs | COVERED | [enterprise ops domains §5](../14-domains/manufacturing/manufacturing.md) BOMs |
| ERP-171 | Work orders | Production orders | COVERED | [enterprise ops domains §5](../14-domains/manufacturing/manufacturing.md) work orders |
| ERP-172 | MRP (material requirements planning) | Netting & planned orders | COVERED (light) | [enterprise ops domains §5](../14-domains/manufacturing/manufacturing.md) requirement view + [enterprise ops domains §6](../14-domains/supply-chain/supply-chain.md) supply chain; full MRP II DEFERRED |
| ERP-173 | Routings & work centers | Operation sequences | COVERED | [enterprise ops domains §5](../14-domains/manufacturing/manufacturing.md) work centers + routing steps |
| ERP-174 | Shop-floor control | Operation reporting | NEW-v1 | [ERP parity domains §11.2](../14-domains/manufacturing/manufacturing.md) (operation completion reporting) |
| ERP-175 | Quality control (in-process inspection) | QC gates in production | NEW-v1 | [ERP parity domains §7](../14-domains/manufacturing/quality.md) quality (inspection plans, work-order gates) |
| ERP-176 | By-products & scrap | Co-product handling | NEW-v1 | [ERP parity domains §11.3](../14-domains/manufacturing/manufacturing.md) (by-product lines on work orders) |
| ERP-177 | Batch traceability (genealogy) | Lot upstream/downstream trace | NEW-v1 | [ERP parity domains §8.3](../14-domains/inventory/inventory.md) batch ledger + traceability report |
| ERP-178 | Capacity planning | Finite scheduling | DEFERRED | seam: work-center calendars |

## 10. Supply Chain

| ID | Feature | ERP behavior | Disposition | Navdhya implementation |
|----|---------|--------------|-------------|------------------------|
| ERP-190 | Demand planning & forecasting | Statistical forecasting | COVERED | [enterprise ops domains §6](../14-domains/supply-chain/supply-chain.md) + [intelligence §2](../17-ai/intelligence.md) |
| ERP-191 | Replenishment orchestration | Suggestion → PO/transfer | COVERED | [enterprise ops domains §6](../14-domains/supply-chain/supply-chain.md) replenishment |
| ERP-192 | Supplier lead-time tracking | Delivery performance | COVERED | [commercial core domains §3](../14-domains/procurement/suppliers.md) supplier_items lead time |
| ERP-193 | EDI (850/856/810) | Electronic data interchange | DEFERRED (partner adapters) | seam: channel adapters ([API platform §10](../15-integrations/integration-framework.md)) |
| ERP-194 | Drop-shipping | Ship from vendor to customer | NEW-v1 | [ERP parity domains §3.5](../14-domains/sales/sales.md) (drop-ship orders: sales order → PO with customer address) |

## 11. Statutory & Compliance (India focus)

| ID | Feature | ERP behavior | Disposition | Navdhya implementation |
|----|---------|--------------|-------------|------------------------|
| ERP-210 | GST computation & tax engine | CGST/SGST/IGST | COVERED | [finance domains §2](../14-domains/finance/tax.md) tax engine |
| ERP-211 | GST e-invoicing (IRN via NIC) | Invoice registration | NEW-v1 | [ERP parity domains §9.2](../15-integrations/connectors/india-statutory.md) (e-invoice adapter: IRN + QR on invoice) |
| ERP-212 | E-way bills | Transport document | NEW-v1 | [ERP parity domains §9.3](../15-integrations/connectors/india-statutory.md) (e-way bill generation on shipment) |
| ERP-213 | GSTR-1 / GSTR-3B exports | Return filings | NEW-v1 | [ERP parity domains §9.5](../15-integrations/connectors/india-statutory.md) (return export files) |
| ERP-214 | TDS/TCS computation & certificates | Tax deducted at source | NEW-v1 | [ERP parity domains §9.6](../15-integrations/connectors/india-statutory.md) (TDS on payments/bills, certificates) |
| ERP-215 | PF/ESI statutory filings | Provident fund, ESI | NEW-v1 | [ERP parity domains §9.4](../15-integrations/connectors/india-statutory.md) |
| ERP-216 | Input tax credit reconciliation | 2A/2B matching | DEFERRED (v1.1) | seam: tax ledger + import jobs |

## 12. Cross-Cutting Enterprise Features

| ID | Feature | ERP behavior | Disposition | Navdhya implementation |
|----|---------|--------------|-------------|------------------------|
| ERP-230 | Approval workflows (multi-level) | Document approvals | COVERED | kernel workflow engine ([kernel services §4](../11-workflow/workflow-engine.md)) |
| ERP-231 | Document management / attachments | Files on any record | COVERED | kernel Files ([kernel services §7](../07-kernel/kernel-services.md)) |
| ERP-232 | Notes & activity stream | Collaboration on records | NEW-v1 | [ERP parity domains §2.4](../14-domains/crm/crm.md) activities (shared polymorphic surface) |
| ERP-233 | Audit trail | Change history | COVERED | kernel Audit ([kernel services §5](../07-kernel/kernel-services.md)) |
| ERP-234 | User-defined fields (UDF) | Custom columns | COVERED | custom fields ([ecosystem §5](../18-extensions/ecosystem.md), preview [Shopify parity extensions §6](../14-domains/commerce/content.md)) |
| ERP-235 | Custom reports / report builder | Ad-hoc reporting | COVERED | custom reports ([ecosystem §5.6](../18-extensions/ecosystem.md)) + finance_reports ([finance domains §8](../14-domains/finance/assets-budgets-reports.md)) |
| ERP-236 | Dashboards & KPIs | Role dashboards | COVERED | [intelligence §1](../16-analytics/analytics.md) analytics |
| ERP-237 | Scheduled reports & alerts | Push reports | NEW-v1 | [ERP parity domains §13.2](../16-analytics/analytics.md) (scheduled report dispatch via notifications) |
| ERP-238 | Data import wizards (CSV/Excel) | Bulk import | NEW-v1 | [Shopify parity extensions §14](../07-kernel/data-tools.md) import/export (shared surface) |
| ERP-239 | Multi-company / group entities | Multiple legal entities | DEFERRED | seam: org tree + tenant model; v1 = single legal entity per tenant |
| ERP-240 | Multi-branch within one entity | Branch accounting | COVERED | org tree scoping ([authorization & policy §4.2](../04-security/authorization-and-policy.md)) + org dimension on journal lines ([finance domains §1.2](../14-domains/finance/gl.md)) |
| ERP-241 | Inter-branch stock transfers | Branch replenishment | COVERED | inventory transfers with org scoping ([commercial core domains §7](../14-domains/inventory/inventory.md)) |
| ERP-242 | Numbering sequences per document type | Configurable numbering | COVERED | invoicing numbering schemes ([commercial core domains §9.6](../14-domains/finance/invoicing.md)) extended to all documents ([ERP parity domains §13.3](../07-kernel/numbering.md)) |
| ERP-243 | Role-based field masking | Sensitive data control | COVERED (v1 approach) | [enterprise ops domains §7.5](../14-domains/hr/hr.md) sensitivity classes; field-level ACL noted v2 ([authorization & policy §11](../04-security/authorization-and-policy.md)) |
| ERP-244 | Quality management system (QMS) | Inspections, NCR, CAPA | NEW-v1 | [ERP parity domains §7](../14-domains/manufacturing/quality.md) (quality module: inspection plans, NCR, CAPA) |
| ERP-245 | Contracts & renewals | Contract lifecycle | NEW-v1 | [ERP parity domains §10](../14-domains/sales/contracts.md) (contracts module) |

---

## 13. Completeness Checklist (ERP)

Areas swept: CRM, sales (advanced), purchasing (advanced), inventory &
warehousing, finance & accounting, HR & payroll, projects, service/helpdesk,
manufacturing, supply chain, statutory & compliance (India), cross-cutting
enterprise features.

Disposition totals:
- COVERED: 53 (implemented by docs 00–15)
- NEW-v1: 47 (→ [ERP parity domains](../14-domains/crm/crm.md), plus ERP-045 landed cost wiring, ERP-069 barcode via [Shopify parity extensions §7.3](../14-domains/commerce/giftcards-subscriptions-pos.md))
- DEFERRED: 16 (seams recorded: consignment, multi-company, consolidation,
  recruitment, performance, scheduling, field service, full WMS, EDI,
  capacity planning, loans, ITC reconciliation, quotas)
- OUT: 0

Residual-risk note: any ERP feature discovered after this inventory MUST
enter via a gap-analysis amendment ([gap analysis](./gap-analysis.md)) before task-spec, per planning
README §2.5.

> **D8 correction note (2026-08-27):** the completeness totals previously stated COVERED 45 / NEW-v1 37 / DEFERRED 18. A full re-count of the table rows yields **COVERED 53 / NEW-v1 47 / DEFERRED 16 / OUT 0**, which matches the disposition totals now asserted above. No rows were added or removed; only the summary numbers were wrong.
