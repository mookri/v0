# ADR 008: Service Extraction Strategy

## Status
Accepted

## Context
The current monolith is growing too large. We need a methodical approach to extracting services.

## Decision
We will follow the Strangler Fig pattern for service extraction. Capabilities will be extracted one-by-one into their own modules/services without breaking the existing monolith until fully migrated.
