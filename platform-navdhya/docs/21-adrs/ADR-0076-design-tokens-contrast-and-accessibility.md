# ADR-0076: Design Tokens, Sacred Ember Contrast Policy & Accessibility Floor

## Status
Accepted (2026-11-11)

## Context
Ad-hoc color classes across apps (`text-green-600`, indigo buttons, low-contrast orange text on ivory) violated brand and accessibility rules (`A-11`, `A-12`, `AGENTS.md`).

## Decision
Establish `@navdhya/design-tokens` locking Sacred Ember hex tokens (`#FFF7ED`, `#2C2033`, `#F16A24`, `#EFE3D3`, `#B74717`, `#2A2421`), banning all green tokens/classes (`containsDisallowedGreen`), and failing builds when `#F16A24` body text is paired on `#FFF7ED` (`assertValidSacredEmberPairing` enforces `#B74717` / `#2C2033` / `#2A2421` for WCAG AA text contrast). Provide shared skip-link, focus-ring, live-region, and roving-tabindex helpers in `@navdhya/accessibility`.

