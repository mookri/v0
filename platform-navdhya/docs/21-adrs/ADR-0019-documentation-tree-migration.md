# ADR-0019: Documentation tree migration to the 24-directory production layout

**Status:** Accepted · **Date:** 2026-08-27 · **Deciders:** Platform Architect

## Context

The documentation set lived in `docs/architecture/` (docs 00–17) and `docs/planning/` (inventories, gap analysis, methodology). The production documentation plan requires a concern-based 24-directory tree (`00-constitution` … `23-evidence`) with stable section-level document IDs, lifecycle metadata, and a `docs/specs/` execution layer.

## Decision

Migrate to the exact numbered tree per the plan §36 layout, plus `docs/specs/`. Document IDs are section-level registers within concern files (not one file per ID). Migrated content is ACTIVE; newly authored canonical content is APPROVED with derived_from metadata. Legacy `doc NN §x` citations are eliminated repo-wide and replaced with relative links; the master README carries the old→new mapping. ERP inventory completeness totals are corrected to NEW-v1 47 / COVERED 53 / DEFERRED 16 (D8). Old directories are removed after migration; machine-readable contract extraction is deferred to implementation.

## Consequences

All internal links use relative paths with ID anchors and are CI-verified. The old→new mapping in docs/README.md lets external references be repaired. ADR-0002…0017 preserve the locked decisions as first-class decision records. No code, config, or migration files are touched.
