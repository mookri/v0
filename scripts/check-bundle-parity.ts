#!/usr/bin/env node
/**
 * scripts/check-bundle-parity.ts — fail if the en/hi bundles drift.
 *
 * Used in CI (PR 14a) and locally via `npm run i18n:check`. Exits 0
 * when the leaf-key set matches, exits 1 with a diff when it does not.
 */

import { flattenKeys, loadMessages, _resetMessageCache } from "@/lib/i18n/messages";
import { LOCALES, type Locale } from "@/i18n";

async function main() {
  _resetMessageCache();
  const en = await loadMessages("en");
  const enKeys = new Set(flattenKeys(en));

  let failed = false;

  for (const locale of LOCALES) {
    if (locale === "en") continue;
    const bundle = await loadMessages(locale as Locale);
    const keys = new Set(flattenKeys(bundle));

    const missing = [...enKeys].filter((k) => !keys.has(k));
    const extra = [...keys].filter((k) => !enKeys.has(k));

    if (missing.length === 0 && extra.length === 0) {
      console.log(`OK: en and ${locale} have ${enKeys.size} keys in sync.`);
    } else {
      failed = true;
      console.error(`\n[${locale}] parity check FAILED`);
      if (missing.length > 0) {
        console.error(`  ${missing.length} keys missing in ${locale}:`);
        for (const k of missing.slice(0, 20)) console.error(`    - ${k}`);
        if (missing.length > 20) console.error(`    ... and ${missing.length - 20} more`);
      }
      if (extra.length > 0) {
        console.error(`  ${extra.length} keys extra in ${locale} (not in en):`);
        for (const k of extra.slice(0, 20)) console.error(`    + ${k}`);
        if (extra.length > 20) console.error(`    ... and ${extra.length - 20} more`);
      }
    }
  }

  if (failed) process.exit(1);
}

main().catch((e) => {
  console.error("[i18n:check] Fatal:", e);
  process.exit(1);
});
