#!/usr/bin/env node
/**
 * scripts/translate.ts — translate missing keys from en -> target locale.
 *
 *   npx tsx scripts/translate.ts --to hi
 *   npx tsx scripts/translate.ts --to hi --dry-run
 *   npx tsx scripts/translate.ts --to hi --provider claude
 *   npx tsx scripts/translate.ts --to hi --key "common.learnMore"
 *
 * The script:
 *   1. Loads messages/en.json (the source of truth)
 *   2. Loads messages/<to>.json if it exists, or starts from an empty bundle
 *   3. For every dotted key in en:
 *        - if already present in <to>.json, skip
 *        - if missing, send to the translation client
 *   4. Writes back messages/<to>.json (unless --dry-run)
 *
 * Rate limiting: 1 request / 250ms by default. Override with --rps <n>.
 */

import { promises as fs } from "fs";
import { join } from "path";
import { flattenKeys, get, type MessageBundle } from "@/lib/i18n/messages";
import { getDefaultClient } from "@/lib/i18n/translate";
import { LOCALES, type Locale } from "@/i18n";

interface Args {
  to: Locale;
  dryRun: boolean;
  provider?: "deepl" | "claude";
  onlyKey?: string;
  rps: number;
}

function parseArgs(argv: string[]): Args {
  const args: Args = {
    to: "hi",
    dryRun: false,
    rps: 4,
  };
  for (let i = 2; i < argv.length; i++) {
    const flag = argv[i];
    const next = argv[i + 1];
    switch (flag) {
      case "--to": args.to = next as Locale; i++; break;
      case "--dry-run": args.dryRun = true; break;
      case "--provider": args.provider = next as "deepl" | "claude"; i++; break;
      case "--key": args.onlyKey = next; i++; break;
      case "--rps": args.rps = Number(next) || 4; i++; break;
      case "--help":
        console.log("Usage: translate.ts --to <locale> [--dry-run] [--provider deepl|claude] [--key <dotted>] [--rps N]");
        process.exit(0);
    }
  }
  if (!LOCALES.includes(args.to)) {
    console.error(`Unknown locale: ${args.to}. Known: ${LOCALES.join(", ")}`);
    process.exit(1);
  }
  return args;
}

const MESSAGES_DIR = join(process.cwd(), "messages");

async function loadBundle(locale: Locale): Promise<MessageBundle> {
  const file = join(MESSAGES_DIR, `${locale}.json`);
  try {
    return JSON.parse(await fs.readFile(file, "utf8")) as MessageBundle;
  } catch {
    return {};
  }
}

function setAtPath(bundle: MessageBundle, dottedKey: string, value: string): void {
  const segments = dottedKey.split(".");
  let cursor: Record<string, unknown> = bundle;
  for (let i = 0; i < segments.length - 1; i++) {
    const seg = segments[i];
    if (typeof cursor[seg] !== "object" || cursor[seg] === null) {
      cursor[seg] = {};
    }
    cursor = cursor[seg] as Record<string, unknown>;
  }
  cursor[segments[segments.length - 1]] = value;
}

async function main() {
  const args = parseArgs(process.argv);
  const en = await loadBundle("en");
  const target = await loadBundle(args.to);
  const client = getDefaultClient();

  const allKeys = flattenKeys(en);
  const keysToTranslate = args.onlyKey ? [args.onlyKey] : allKeys;

  const missing = keysToTranslate.filter((k) => get(target, k) === undefined);
  console.log(`[translate] en has ${allKeys.length} keys, ${missing.length} missing in ${args.to}.`);

  if (missing.length === 0) {
    console.log("[translate] Nothing to do.");
    return;
  }

  const delayMs = Math.max(0, Math.round(1000 / args.rps));
  const updated = { ...target };
  let done = 0;
  for (const key of missing) {
    const source = get(en, key);
    if (source === undefined) continue;
    try {
      const res = await client({ text: source, source: "en", target: args.to, context: key });
      setAtPath(updated, key, res.text);
      done++;
      console.log(`  [${done}/${missing.length}] ${res.provider.padEnd(11)} ${key}  ->  ${res.text.slice(0, 60)}${res.text.length > 60 ? "..." : ""}`);
    } catch (e) {
      console.error(`  [ERR] ${key}: ${(e as Error).message}`);
    }
    if (delayMs) await new Promise((r) => setTimeout(r, delayMs));
  }

  if (args.dryRun) {
    console.log(`\n[translate] DRY RUN — would write ${done} keys to messages/${args.to}.json`);
    return;
  }

  await fs.writeFile(
    join(MESSAGES_DIR, `${args.to}.json`),
    JSON.stringify(updated, null, 2) + "\n",
    "utf8",
  );
  console.log(`\n[translate] Wrote ${done} keys to messages/${args.to}.json`);
}

main().catch((e) => {
  console.error("[translate] Fatal:", e);
  process.exit(1);
});
