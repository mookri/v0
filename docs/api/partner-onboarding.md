# Partner Onboarding API (`partner.navdhya.com`, vendor + individual tracks)

Same engine and envelopes as pandit onboarding
(`./pandit-onboarding.md`); this page covers track differences only.

## Tracks

| Track | Version | SLA | Distinct steps |
|---|---|---|---|
| `partner-vendor` | `partner-vendor-v1` | 72h | `business` (GSTIN + bank account), `capacity` (weekly slots) |
| `partner-individual` | `partner-individual-v1` | 72h | subset of pandit (availability optional) |

All seven endpoints are identical in shape; only `track` at start and
the step catalog differ. `needs[]` for a vendor missing business data:

```json
{ "data": { "status": "DRAFT", "needs": ["business", "capacity"] }, "requestId": "req_z" }
```

## Vendor specifics

- Bank proof is mandatory; payout name-match is required before first
  settlement (finance rule, stub-checked v1).
- Capacity (`weeklySlots > 0`) gates dispatch eligibility alongside the
  shared readiness rules.
- Business identity (GSTIN format) is validated structurally in v1;
  authoritative registry checks plug into the verification worker later
  without API changes.
