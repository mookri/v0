# E-SC-01 Astronomical Consumer Audit & Impact Analysis

**Date:** 2026-11-10  
**Authors:** Platform Core & Calendar Architecture  
**Scope:** Exhaustive enumeration of all consumers of `nakshatra`, `masa`, and `vara` in PHP and TypeScript, assessing blast radius for P-01 and P-02 remediation.

---

## 1. Nakshatra Consumer Audit (Defect P-01)

### Shipped State
- `App\Modules\Customers\Domain\Panchang\PanchangCalculator::calculate()` computes tropical Moon longitude `LunarLongitude::calculate($t)` and directly invokes `NakshatraCalculator::calculate($moonLon)`.
- Tropical lunar longitude without Ayanamsa ($A \approx 24.21^{\circ}$ for 2026) shifts every nakshatra by $+1.82$ to $+2$ lunar mansions.

### All Callsites & Consumers
1. **Calculation Engine**: `PanchangCalculator.php:54` $\to$ populates `$nakshatra` array in `PanchangOccurrence`.
2. **Anchor Resolution**: `AnchorOccurrenceResolver.php` **never reads Nakshatra**. It matches on `tithi`, `paksha`, and `masa` only.
3. **Database Columns**:
   - `customer_spiritual_profiles.nakshatra` (string, max 50, nullable)
   - `customer_family_members.nakshatra` (string, max 50, nullable)
   - Populated via `CustomerSpiritualController::updateSpiritualProfile` and `addFamilyMember`.
   - Validated as free-text strings (`['nullable', 'string', 'max:50']`).
4. **Telemetry & Governance**:
   - `WarehouseExportService` explicitly bans `nakshatra` alongside Gotra and Rashi under special-category data exclusion guards.
5. **Frontend UI**:
   - `apps/customer/app/(protected)/profile/spiritual/page.tsx` renders text input.
   - `apps/web/app/resources/tools/panchang/` (does not exist today; P-10).

### Blast Radius of P-01 Fix
- **Active Reminder Impact**: Zero. No reminder queries or depends on the computed `PanchangOccurrence::$nakshatra`.
- **User Profile Data**: User-typed strings remain untouched, but must now be validated against `NakshatraCalculator::NAKSHATRAS` (Task T-SC01-09).
- **Future Impact**: Essential for Muhurat suitability (`Tara Bala`), Rohini-bound Janmashtami, and public Daily Panchang pages.

---

## 2. Masa Consumer Audit (Defect P-02)

### Shipped State
- `PanchangCalculator.php:57` invokes `LunarMonthCalculator::calculate($sunLon, $tithi['number'])`.
- Tropical Sun longitude is bucketed into 30° rashi slices. Because $A \approx 24.21^{\circ}$, the calculated tropical rashi matches sidereal rashi for only $(30 - 24.21)/30 \approx 19.3\%$ of the solar year.
- For the remaining $\approx 80.7\%$ of the year, the emitted Masa name is shifted by $+1$ month ahead of real sidereal Panchang observation.

### All Callsites & Consumers
1. **Anchor Resolver (`AnchorOccurrenceResolver.php:52-54, 91-109`)**:
   - Evaluates `$targetMasa = strtoupper($anchorRule['masa'])`.
   - Checks `$resolvedMasa = $panchang->resolvedMasa()`.
   - Blocks or matches occurrence based on equality: `$resolvedMasa === $targetMasa`.
2. **Reminder Scheduler (`ProcessRitualRemindersCommand.php:101-108`)**:
   - Builds period dedup key: `{reminder_id}:{year}-{masa}-{paksha}-{tithi}:{daysUntil}d`.
   - Renders notification message: `"Sacred reminder: {title} occurs {slotDesc} ({resolvedMasa} {tithi}, {date})."`
3. **Database Records**:
   - `customer_ritual_reminders.anchor_rule` JSON:
     - Types: `SHRADDHA`, `CUSTOM_TITHI`.
     - Fields: `{"tithi": 11, "paksha": "KRISHNA", "masa": "KARTIKA", "tradition": "SMARTHA"}`.

### Blast Radius & Migration Hazard of P-02 Fix
- **Live User Impact**: High.
- Applying Ayanamsa shifts the evaluated `resolvedMasa()` on $\approx 81\%$ of dates. An existing reminder created by a user under the uncorrected UI (e.g., intending a rite in Kartika) was matching dates that were actually Ashvina.
- **Remediation Strategy**:
  - We must version rule resolution via `rules_version`:
    - `rules_version: "1.0.0"` evaluates legacy tropical Masa matching for pre-existing reminder rows.
    - `rules_version: "2.0.0"` (or new rows) strictly evaluates sidereal Masa matching.
  - Formally documented in `ADR-0035`.

---

## 3. Vara Consumer Audit (Defect P-07)

### Shipped State
- Vara is completely absent from `PanchangOccurrence` and `PanchangCalculator`.
- Zero consumers exist in the codebase.

### Remediation Strategy
- Task `T-SC04-03` adds dual-anchored Vara:
  - `vara_civil`: Midnight-to-midnight civil weekday.
  - `vara_sunrise`: Sunrise-to-next-sunrise Vedic Ahargana weekday.

