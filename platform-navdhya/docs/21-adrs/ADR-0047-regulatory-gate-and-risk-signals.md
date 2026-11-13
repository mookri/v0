# ADR-0047: Regulatory Applicability Gate (G-LEGAL-0) & Non-Discriminatory Risk Signals

## Status
Accepted (2026-11-11)

## Context
AML/PEP/Sanctions tooling must remain dormant unless legal counsel confirms reporting-entity status, and background checks must avoid binary criminal booleans or caste/region proxies.

## Decision
Enforce fail-closed `RegulatoryApplicabilityGate` before sanctions screening, capture nuanced `PoliceAntecedentStatus` with mandatory human relevance review, and prohibit caste, sect, religion, region, or accent signals in `RiskSignalRegistry`.

