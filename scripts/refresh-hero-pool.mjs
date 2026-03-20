#!/usr/bin/env node
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║  NAVDHYA — HERO POOL AUTOMATION  (custom host · Next.js · 2027) ║
 * ║  Validate · Generate LQIP · Optimise · Sync CDN · Update JSON  ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * What this script does
 * ──────────────────────
 *  1.  Reads hero-images.json
 *  2.  Validates every URL — marks broken images inactive
 *  3.  [Optional] Generates WebP + AVIF + thumbnail variants via sharp
 *      for any image that has a source file in /public/images/hero/src/
 *  4.  [Optional] Generates base-64 blurDataURL LQIPs (20×9 px)
 *  5.  [Optional] Syncs /public/images/hero/ → CDN (S3/R2/SFTP)
 *  6.  Updates _meta.lastRefreshed and totalImages in JSON
 *  7.  Atomically writes updated hero-images.json
 *  8.  Prints a rich diff report + exits CI-safe (0 = ok, 1 = fatal, 2 = warnings)
 *
 * Usage
 * ──────
 *  node scripts/refresh-hero-pool.mjs                   # validate only
 *  node scripts/refresh-hero-pool.mjs --generate        # + generate image variants
 *  node scripts/refresh-hero-pool.mjs --lqip            # + regenerate blurDataURLs
 *  node scripts/refresh-hero-pool.mjs --sync-cdn        # + sync to CDN
 *  node scripts/refresh-hero-pool.mjs --generate --lqip --sync-cdn  # full pipeline
 *  node scripts/refresh-hero-pool.mjs --dry-run         # show what would change
 *
 * package.json scripts to add:
 *  "refresh:heroes":      "node scripts/refresh-hero-pool.mjs",
 *  "refresh:heroes:full": "node scripts/refresh-hero-pool.mjs --generate --lqip --sync-cdn",
 *  "refresh:lqip":        "node scripts/refresh-hero-pool.mjs --lqip",
 *
 * Weekly cron (Monday 03:00):
 *  0 3 * * 1  cd /app && node scripts/refresh-hero-pool.mjs --sync-cdn >> /var/log/hero-pool.log 2>&1
 *
 * Required for --generate and --lqip:
 *  npm install sharp
 *
 * Required for --sync-cdn (S3/R2):
 *  npm install @aws-sdk/client-s3 @aws-sdk/lib-storage
 *  Set: AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, CDN_BUCKET, CDN_ENDPOINT, CDN_REGION
 */

import fs        from "fs";
import path      from "path";
import https     from "https";
import http      from "http";
import { fileURLToPath } from "url";

const __dirname  = path.dirname(fileURLToPath(import.meta.url));
const ROOT       = path.join(__dirname, "..");
const POOL_FILE  = path.join(ROOT, "src", "data", "hero-images.json");
const PUBLIC_DIR = path.join(ROOT, "public", "images", "hero");
const SRC_DIR    = path.join(PUBLIC_DIR, "src");     // drop raw source files here
const BACKUP_DIR = path.join(ROOT, ".hero-backups");

// ─── CLI ──────────────────────────────────────────────────────────────────────
const args        = process.argv.slice(2);
const DRY_RUN     = args.includes("--dry-run");
const DO_GENERATE = args.includes("--generate");
const DO_LQIP     = args.includes("--lqip");
const DO_SYNC_CDN = args.includes("--sync-cdn");

// ─── Logger ───────────────────────────────────────────────────────────────────
const C = {
  reset: "\x1b[0m", bold: "\x1b[1m", dim: "\x1b[2m",
  green: "\x1b[32m", yellow: "\x1b[33m", red: "\x1b[31m",
  cyan: "\x1b[36m", blue: "\x1b[34m", grey: "\x1b[90m",
};
const ok   = (...a) => console.log(`${C.green}  ✔${C.reset}`, ...a);
const warn = (...a) => console.warn(`${C.yellow}  ⚠${C.reset}`, ...a);
const err  = (...a) => console.error(`${C.red}  ✖${C.reset}`, ...a);
const info = (...a) => console.log(`${C.cyan}  ℹ${C.reset}`, ...a);
const step = (...a) => console.log(`\n${C.bold}${C.blue}▶${C.reset}`, ...a);
const dim  = (...a) => console.log(`${C.grey}   `, ...a);

// ─── HTTP Validator ───────────────────────────────────────────────────────────

function headRequest(url, timeoutMs = 5000) {
  return new Promise(resolve => {
    // For root-relative paths, resolve against localhost dev server
    const fullUrl = url.startsWith("/")
      ? `http://localhost:${process.env.DEV_PORT || 3000}${url}`
      : url;
    const mod = fullUrl.startsWith("https") ? https : http;
    const req = mod.request(fullUrl, { method: "HEAD" }, res => {
      resolve({ ok: res.statusCode >= 200 && res.statusCode < 400, status: res.statusCode });
    });
    req.setTimeout(timeoutMs, () => { req.destroy(); resolve({ ok: false, status: "timeout" }); });
    req.on("error", () => {
      // For local paths, check file exists instead
      if (url.startsWith("/")) {
        const localPath = path.join(ROOT, "public", url);
        resolve({ ok: fs.existsSync(localPath), status: "file-check" });
      } else {
        resolve({ ok: false, status: "error" });
      }
    });
    req.end();
  });
}

async function validateUrls(images, concurrency = 8) {
  const results = new Map();
  const queue   = [...images];
  async function worker() {
    while (queue.length) {
      const img  = queue.shift();
      const res  = await headRequest(img.src);
      results.set(img.id, res);
      const icon = res.ok
        ? `${C.green}✔${C.reset}` : `${C.red}✖${C.reset}`;
      dim(`${icon} [${img.id}] HTTP ${res.status}  ${img.src}`);
    }
  }
  await Promise.all(Array.from({ length: Math.min(concurrency, images.length) }, worker));
  return results;
}

// ─── Image Generation (sharp) ─────────────────────────────────────────────────

async function generateVariants(images) {
  let sharp;
  try {
    sharp = (await import("sharp")).default;
  } catch {
    warn("sharp not installed — skipping image generation. Run: npm install sharp");
    return;
  }

  fs.mkdirSync(PUBLIC_DIR, { recursive: true });
  fs.mkdirSync(SRC_DIR, { recursive: true });

  let generated = 0;
  for (const img of images) {
    // Look for a source file named {id}.* in /src/ directory
    const srcFiles = fs.existsSync(SRC_DIR)
      ? fs.readdirSync(SRC_DIR).filter(f => f.startsWith(img.id + "."))
      : [];

    if (srcFiles.length === 0) {
      dim(`[${img.id}] no source file in src/ — skipping generation`);
      continue;
    }

    const srcFile = path.join(SRC_DIR, srcFiles[0]);
    const base    = path.join(PUBLIC_DIR, img.id);

    const variants = [
      { suffix: "",        format: "webp", width: 1200, height: 500, q: 85 },
      { suffix: "",        format: "avif", width: 1200, height: 500, q: 80 },
      { suffix: "",        format: "jpg",  width: 1200, height: 500, q: 85 },
      { suffix: "-thumb",  format: "webp", width: 40,   height: 17,  q: 40 },
    ];

    for (const v of variants) {
      const dest = `${base}${v.suffix}.${v.format}`;
      if (!DRY_RUN) {
        await sharp(srcFile)
          .resize(v.width, v.height, { fit: "cover", position: "entropy" })
          [v.format]({ quality: v.q })
          .toFile(dest);
      }
      dim(`  → ${path.relative(ROOT, dest)}${DRY_RUN ? " (dry)" : ""}`);
    }

    generated++;
    ok(`[${img.id}] all variants generated`);
  }

  info(`Generated variants for ${generated} image(s)`);
}

// ─── LQIP Generator ───────────────────────────────────────────────────────────

async function generateLQIPs(images, poolJson) {
  let sharp;
  try {
    sharp = (await import("sharp")).default;
  } catch {
    warn("sharp not installed — skipping LQIP generation. Run: npm install sharp");
    return;
  }

  let updated = 0;
  for (const img of images) {
    const localPath = path.join(ROOT, "public", img.srcSet.thumb.split("?")[0]);
    if (!fs.existsSync(localPath)) {
      dim(`[${img.id}] thumb not found — skipping LQIP`);
      continue;
    }

    const buf     = await sharp(localPath).resize(20, 9).webp({ quality: 20 }).toBuffer();
    const dataUrl = `data:image/webp;base64,${buf.toString("base64")}`;

    // Write back into the raw JSON array
    const entry = poolJson.images.find(i => i.id === img.id);
    if (entry && entry.blurDataURL !== dataUrl) {
      entry.blurDataURL = dataUrl;
      updated++;
      dim(`[${img.id}] LQIP updated (${buf.length} bytes)`);
    }
  }

  ok(`Updated ${updated} LQIP placeholder(s)`);
  return updated > 0;
}

// ─── CDN Sync (S3 / Cloudflare R2) ───────────────────────────────────────────

async function syncCDN() {
  const {
    AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY,
    CDN_BUCKET, CDN_ENDPOINT, CDN_REGION = "auto",
  } = process.env;

  if (!CDN_BUCKET) {
    warn("CDN_BUCKET not set — skipping CDN sync");
    return;
  }

  let S3Client, Upload;
  try {
    ({ S3Client } = await import("@aws-sdk/client-s3"));
    ({ Upload }   = await import("@aws-sdk/lib-storage"));
  } catch {
    warn("@aws-sdk/* not installed — skipping CDN sync. Run: npm install @aws-sdk/client-s3 @aws-sdk/lib-storage");
    return;
  }

  const s3 = new S3Client({
    region: CDN_REGION,
    endpoint: CDN_ENDPOINT,
    credentials: { accessKeyId: AWS_ACCESS_KEY_ID, secretAccessKey: AWS_SECRET_ACCESS_KEY },
  });

  const files = fs.readdirSync(PUBLIC_DIR).filter(f => !fs.statSync(path.join(PUBLIC_DIR, f)).isDirectory());
  info(`Syncing ${files.length} file(s) to s3://${CDN_BUCKET}/images/hero/`);

  let synced = 0;
  for (const file of files) {
    const filePath = path.join(PUBLIC_DIR, file);
    const key      = `images/hero/${file}`;
    const ext      = path.extname(file).slice(1);
    const mimeMap  = { webp: "image/webp", avif: "image/avif", jpg: "image/jpeg", jpeg: "image/jpeg", png: "image/png" };

    if (DRY_RUN) { dim(`  → s3://${CDN_BUCKET}/${key} (dry)`); continue; }

    const upload = new Upload({
      client: s3,
      params: {
        Bucket:       CDN_BUCKET,
        Key:          key,
        Body:         fs.createReadStream(filePath),
        ContentType:  mimeMap[ext] ?? "application/octet-stream",
        CacheControl: "public, max-age=31536000, immutable",
        ACL:          "public-read",
      },
    });
    await upload.done();
    synced++;
  }

  ok(`Synced ${synced} file(s) to CDN`);
}

// ─── Atomic Write ─────────────────────────────────────────────────────────────

function atomicWrite(filePath, data) {
  fs.mkdirSync(BACKUP_DIR, { recursive: true });
  const stamp = new Date().toISOString().replace(/[:.]/g, "-");
  const bak   = path.join(BACKUP_DIR, `hero-images.${stamp}.json`);
  if (fs.existsSync(filePath)) fs.copyFileSync(filePath, bak);

  const tmp = `${filePath}.tmp_${process.pid}`;
  fs.writeFileSync(tmp, JSON.stringify(data, null, 2) + "\n", "utf8");
  fs.renameSync(tmp, filePath);

  // Prune backups — keep last 10
  const all = fs.readdirSync(BACKUP_DIR).filter(f => f.endsWith(".json")).sort().reverse();
  for (const old of all.slice(10)) fs.unlinkSync(path.join(BACKUP_DIR, old));
}

// ─── Main ─────────────────────────────────────────────────────────────────────

async function main() {
  const t0 = Date.now();

  console.log(`\n${C.bold}${C.blue}╔══════════════════════════════════════════════════════════════════╗
║      NAVDHYA — HERO POOL AUTOMATION  (custom host)              ║
╚══════════════════════════════════════════════════════════════════╝${C.reset}`);

  if (DRY_RUN)     info("DRY RUN — nothing will be written or uploaded");
  if (DO_GENERATE) info("--generate  : will create WebP / AVIF / thumb variants");
  if (DO_LQIP)     info("--lqip      : will regenerate blurDataURL placeholders");
  if (DO_SYNC_CDN) info("--sync-cdn  : will upload to CDN bucket");

  // 1. Load JSON
  step("Loading hero-images.json");
  if (!fs.existsSync(POOL_FILE)) { err(`Not found: ${POOL_FILE}`); process.exit(1); }
  const poolJson = JSON.parse(fs.readFileSync(POOL_FILE, "utf8"));
  const active   = poolJson.images.filter(i => i.active !== false);
  info(`Loaded ${poolJson.images.length} total / ${active.length} active`);

  // 2. Generate variants from source files
  if (DO_GENERATE) {
    step("Generating image variants (WebP · AVIF · thumb)");
    await generateVariants(poolJson.images);
  }

  // 3. Validate URLs / local files
  step("Validating image sources");
  const validation  = await validateUrls(active);
  const broken      = active.filter(img => !validation.get(img.id)?.ok);
  const healthy     = active.filter(img =>  validation.get(img.id)?.ok);

  ok(`Healthy: ${healthy.length}`);
  if (broken.length) warn(`Broken / missing: ${broken.length}`);

  let changed = false;
  for (const img of broken) {
    warn(`  Deactivating [${img.id}]: ${img.src}`);
    const entry = poolJson.images.find(i => i.id === img.id);
    if (entry) { entry.active = false; changed = true; }
  }

  // 4. Regenerate LQIPs
  if (DO_LQIP) {
    step("Regenerating blurDataURL LQIPs");
    const lqipChanged = await generateLQIPs(active, poolJson);
    if (lqipChanged) changed = true;
  }

  // 5. Sync to CDN
  if (DO_SYNC_CDN) {
    step("Syncing assets to CDN");
    await syncCDN();
  }

  // 6. Update metadata
  if (changed || DO_LQIP || DO_GENERATE) {
    poolJson._meta.lastRefreshed = new Date().toISOString();
    poolJson._meta.totalImages   = poolJson.images.filter(i => i.active !== false).length;
  }

  // 7. Write JSON
  if ((changed || DO_LQIP) && !DRY_RUN) {
    step("Writing updated hero-images.json");
    atomicWrite(POOL_FILE, poolJson);
    ok("JSON written");
  } else if (DRY_RUN) {
    warn("DRY RUN — write skipped");
  } else {
    info("No JSON changes — pool is up to date");
  }

  // 8. Summary
  const elapsed = ((Date.now() - t0) / 1000).toFixed(2);
  const byCategory = {};
  for (const img of poolJson.images.filter(i => i.active !== false)) {
    byCategory[img.category] = (byCategory[img.category] || 0) + 1;
  }

  console.log(`\n${C.bold}${C.green}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  Done in ${elapsed}s
  Active images : ${poolJson._meta.totalImages}
  Broken flagged: ${broken.length}
  Last refreshed: ${poolJson._meta.lastRefreshed}
  By category   :${C.reset}`);
  for (const [cat, count] of Object.entries(byCategory)) {
    console.log(`${C.grey}    ${cat.padEnd(12)} ${count}${C.reset}`);
  }
  console.log(C.bold + C.green + "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" + C.reset);

  process.exit(broken.length > 0 ? 2 : 0);
}

main().catch(e => {
  err("Fatal:", e.message);
  console.error(e.stack);
  process.exit(1);
});
