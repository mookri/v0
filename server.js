'use strict';

/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║         NAVDHYA ENTERPRISE — HYPER-PRODUCTION RUNTIME           ║
 * ║  Cluster · Graceful Drain · Health API · Structured Logging     ║
 * ╚══════════════════════════════════════════════════════════════════╝
 *
 * Architecture:
 *  Primary  →  spins up N workers (one per vCPU)
 *  Workers  →  each runs the Next.js standalone server
 *  Health   →  /_health  (liveness) · /_ready  (readiness)
 *  Shutdown →  SIGTERM / SIGINT drain in-flight requests, then exit 0
 */

const cluster = require('cluster');
const os = require('os');
const path = require('path');
const fs = require('fs');
const http = require('http');
const net = require('net');

// ─── Constants ────────────────────────────────────────────────────────────────

const PKG = (() => { try { return require('./package.json'); } catch { return { name: 'navdhya', version: '0.0.0' }; } })();
const STANDALONE_DIR = path.join(__dirname, '.next', 'standalone');
const SERVER_FILE = path.join(STANDALONE_DIR, 'server.js');

const PORT = parseInt(process.env.PORT, 10) || 3000;
const HEALTH_PORT = parseInt(process.env.HEALTH_PORT, 10) || 3001;
const HOSTNAME = process.env.HOSTNAME || '0.0.0.0';
const WORKERS = parseInt(process.env.WEB_CONCURRENCY, 10) || os.cpus().length;
const DRAIN_TIMEOUT = parseInt(process.env.DRAIN_TIMEOUT_MS, 10) || 30_000;   // 30 s
const RESTART_DELAY = parseInt(process.env.RESTART_DELAY_MS, 10) || 1_000;    // 1 s

// ─── Structured Logger ────────────────────────────────────────────────────────

const LOG_JSON = process.env.LOG_FORMAT === 'json';

function log(level, msg, extra = {}) {
  const entry = {
    ts: new Date().toISOString(),
    level,
    service: PKG.name,
    version: PKG.version,
    pid: process.pid,
    role: cluster.isPrimary ? 'primary' : 'worker',
    msg,
    ...extra,
  };
  const out = LOG_JSON
    ? JSON.stringify(entry)
    : `${entry.ts} [${level.toUpperCase().padEnd(5)}] [${entry.role}:${entry.pid}] ${msg}${Object.keys(extra).length ? '  ' + JSON.stringify(extra) : ''
    }`;
  (level === 'error' || level === 'fatal' ? console.error : console.log)(out);
}

const logger = {
  info: (m, e) => log('info', m, e),
  warn: (m, e) => log('warn', m, e),
  error: (m, e) => log('error', m, e),
  fatal: (m, e) => log('fatal', m, e),
  debug: (m, e) => process.env.DEBUG && log('debug', m, e),
};

// ─── Port Scanner ─────────────────────────────────────────────────────────────

/**
 * Probes a single TCP port. Resolves true if free, false if in use.
 */
function isPortFree(port, host) {
  return new Promise(resolve => {
    const probe = net.createServer();
    probe.once('error', () => resolve(false));
    probe.once('listening', () => probe.close(() => resolve(true)));
    probe.listen(port, host);
  });
}

/**
 * Starting from `preferred`, scans upward until a free port is found.
 * Caps at preferred + MAX_PORT_SCAN to avoid runaway searches.
 */
const MAX_PORT_SCAN = parseInt(process.env.MAX_PORT_SCAN, 10) || 20;

async function findAvailablePort(preferred, host) {
  for (let p = preferred; p <= preferred + MAX_PORT_SCAN; p++) {
    if (await isPortFree(p, host)) return p;
    logger.warn(`Port ${p} is in use — trying ${p + 1}…`, { port: p });
  }
  logger.fatal(`No free port found in range ${preferred}–${preferred + MAX_PORT_SCAN}`);
  process.exit(1);
}

// ─── Pre-flight Validation ────────────────────────────────────────────────────

async function preflight() {
  if (!fs.existsSync(SERVER_FILE)) {
    logger.fatal('Standalone build artifact not found — run `npm run build`', { path: SERVER_FILE });
    process.exit(1);
  }

  // Validate required env vars (extend as needed)
  const REQUIRED_ENV = (process.env.REQUIRED_ENV_VARS || '').split(',').filter(Boolean);
  const missing = REQUIRED_ENV.filter(k => !process.env[k]);
  if (missing.length) {
    logger.fatal('Required environment variables are missing', { missing });
    process.exit(1);
  }

  // Auto-select an available port
  const resolvedPort = await findAvailablePort(PORT, HOSTNAME);
  if (resolvedPort !== PORT) {
    logger.warn(`Preferred port ${PORT} unavailable — bound to ${resolvedPort}`, { resolvedPort });
  }
  return resolvedPort;
}

// ─── Health-check Side-car (Primary only) ────────────────────────────────────

function startHealthServer(workerMap) {
  const startTime = Date.now();
  let isShuttingDown = false;

  const server = http.createServer((req, res) => {
    const url = new URL(req.url, `http://${req.headers.host}`);
    const send = (code, body) => {
      res.writeHead(code, { 'Content-Type': 'application/json', 'Cache-Control': 'no-store' });
      res.end(JSON.stringify(body));
    };

    // Liveness — are we alive?
    if (url.pathname === '/_health') {
      return send(200, {
        status: 'ok',
        uptime: Math.floor((Date.now() - startTime) / 1000),
        version: PKG.version,
      });
    }

    // Readiness — do we have live workers?
    if (url.pathname === '/_ready') {
      const alive = Object.values(workerMap).filter(w => !w.isDead()).length;
      if (isShuttingDown || alive === 0) {
        return send(503, { status: 'unavailable', workers: alive, shuttingDown: isShuttingDown });
      }
      return send(200, { status: 'ready', workers: alive, target: WORKERS });
    }

    // Metrics stub — wire to Prometheus / Datadog in prod
    if (url.pathname === '/_metrics') {
      const mem = process.memoryUsage();
      return send(200, {
        uptime_seconds: Math.floor((Date.now() - startTime) / 1000),
        workers_alive: Object.values(workerMap).filter(w => !w.isDead()).length,
        memory_rss_mb: (mem.rss / 1024 / 1024).toFixed(2),
        memory_heap_mb: (mem.heapUsed / 1024 / 1024).toFixed(2),
      });
    }

    send(404, { error: 'not found' });
  });

  server.listen(HEALTH_PORT, HOSTNAME, () =>
    logger.info('Health-check sidecar listening', { port: HEALTH_PORT })
  );

  // Expose a setter so the shutdown handler can flip the flag
  server._setShuttingDown = (v) => { isShuttingDown = v; };
  return server;
}

// ─── Primary Process ──────────────────────────────────────────────────────────

async function runPrimary() {
  const resolvedPort = await preflight();

  // Propagate resolved port + critical env into workers
  process.env.PORT = resolvedPort.toString();
  process.env.HOSTNAME = HOSTNAME;
  process.env.NODE_ENV = 'production';

  logger.info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
  logger.info(`▶  ${PKG.name} v${PKG.version} — Hyper-Production Runtime`);
  logger.info(`   Binding  : http://${HOSTNAME}:${resolvedPort}`);
  logger.info(`   Workers  : ${WORKERS}  (${os.cpus()[0]?.model?.trim() ?? 'unknown CPU'})`);
  logger.info(`   Drain    : ${DRAIN_TIMEOUT} ms`);
  logger.info(`   Build    : ${SERVER_FILE}`);
  logger.info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

  const workerMap = {};

  // Fork initial pool
  for (let i = 0; i < WORKERS; i++) {
    const w = cluster.fork();
    workerMap[w.id] = w;
    logger.info('Worker spawned', { workerId: w.id, pid: w.process.pid });
  }

  const healthServer = startHealthServer(workerMap);

  // Auto-restart dead workers (unless we're shutting down)
  let shuttingDown = false;
  cluster.on('exit', (worker, code, signal) => {
    delete workerMap[worker.id];
    logger.warn('Worker exited', { workerId: worker.id, pid: worker.process.pid, code, signal });

    if (!shuttingDown) {
      logger.info(`Restarting worker in ${RESTART_DELAY} ms…`);
      setTimeout(() => {
        const w = cluster.fork();
        workerMap[w.id] = w;
        logger.info('Worker restarted', { workerId: w.id, pid: w.process.pid });
      }, RESTART_DELAY);
    }
  });

  cluster.on('online', worker =>
    logger.info('Worker online', { workerId: worker.id, pid: worker.process.pid })
  );

  // ── Graceful shutdown ──
  async function gracefulShutdown(signal) {
    if (shuttingDown) return;
    shuttingDown = true;
    healthServer._setShuttingDown(true);

    logger.info(`Received ${signal} — initiating graceful drain (${DRAIN_TIMEOUT} ms max)`);

    // Tell every worker to stop accepting new connections and drain
    for (const worker of Object.values(workerMap)) {
      worker.send('shutdown');
    }

    // Give workers time to drain; then hard-kill any stragglers
    const deadline = setTimeout(() => {
      logger.warn('Drain timeout exceeded — force-killing remaining workers');
      for (const worker of Object.values(workerMap)) {
        if (!worker.isDead()) worker.process.kill('SIGKILL');
      }
    }, DRAIN_TIMEOUT);
    deadline.unref();

    // Wait for all workers to exit
    await new Promise(resolve => {
      const check = () => {
        if (Object.values(workerMap).every(w => w.isDead())) resolve();
        else setTimeout(check, 200);
      };
      check();
    });

    clearTimeout(deadline);
    healthServer.close();
    logger.info('All workers drained. Primary exiting cleanly.');
    process.exit(0);
  }

  process.on('SIGTERM', () => gracefulShutdown('SIGTERM'));
  process.on('SIGINT', () => gracefulShutdown('SIGINT'));
}

// ─── Worker Process ───────────────────────────────────────────────────────────

// ─── Next.js Banner Filter ────────────────────────────────────────────────────
//
// Each worker independently boots Next.js, which prints its startup banner
// (▲ Next.js, Local/Network URLs, ✓ Starting…, ✓ Ready in Xms) to stdout.
// With 8 workers that's 8 identical blocks of noise.
//
// Strategy:
//   • Worker 1  — lets the banner through so the operator sees it once.
//   • Workers 2+ — silences banner lines; real errors still reach stderr.
//
// We identify banner lines by their well-known prefixes. Any line that
// doesn't match is forwarded normally (app console.log, errors, etc.).

const BANNER_PATTERNS = [
  /^\s*▲\s+Next\.js/,          // ▲ Next.js 16.x.x
  /^\s*-\s+(Local|Network):/,  // - Local: / - Network:
  /^\s*✓\s+(Starting|Ready)/, // ✓ Starting... / ✓ Ready in Xms
  /^\s*○\s+/,                   // ○ Compiling ...
];

function suppressBannerOnWorker() {
  // Worker 1 is the "display" worker — it prints once on behalf of all
  const isDisplayWorker = cluster.worker?.id === 1;
  if (isDisplayWorker) return; // leave stdout untouched

  const isBannerLine = (chunk) =>
    BANNER_PATTERNS.some(re => re.test(typeof chunk === 'string' ? chunk : chunk.toString('utf8')));

  const _write = process.stdout.write.bind(process.stdout);
  process.stdout.write = (chunk, encoding, callback) => {
    if (isBannerLine(chunk)) {
      // Silently drop; invoke callback so streams don't stall
      if (typeof encoding === 'function') encoding();
      else if (typeof callback === 'function') callback();
      return true;
    }
    return _write(chunk, encoding, callback);
  };
}

// ─── Worker Process ───────────────────────────────────────────────────────────

function runWorker() {
  // Suppress duplicate Next.js startup banners on all workers except worker 1
  suppressBannerOnWorker();

  // Surface unhandled promise rejections before they crash silently
  process.on('unhandledRejection', (reason) => {
    logger.error('Unhandled promise rejection in worker', { reason: String(reason) });
  });

  process.on('uncaughtException', (err) => {
    logger.fatal('Uncaught exception in worker — worker will exit', {
      message: err.message,
      stack: err.stack,
    });
    // Let the primary restart us
    process.exit(1);
  });

  // Listen for graceful-drain signal from primary
  process.on('message', async (msg) => {
    if (msg !== 'shutdown') return;
    logger.info('Worker received shutdown signal — draining…');

    // Give in-flight requests a moment to complete, then exit
    setTimeout(() => {
      logger.info('Worker drain complete — exiting');
      process.exit(0);
    }, Math.min(DRAIN_TIMEOUT - 1000, 25_000));
  });

  logger.debug('Worker delegating to Next.js standalone runtime', { file: SERVER_FILE });
  require(SERVER_FILE);
}

// ─── Entry Point ──────────────────────────────────────────────────────────────

if (cluster.isPrimary) {
  runPrimary().catch(err => {
    logger.fatal('Primary startup failed', { message: err.message, stack: err.stack });
    process.exit(1);
  });
} else {
  runWorker();
}