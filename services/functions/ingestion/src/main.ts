// HTTP entrypoint (deploy shell). Behavior lives in ingest.ts / worker-auth.ts
// behind ports; this maps HTTP semantics onto them:
//   401 → auth failure (missing/malformed/expired/replayed)
//   400 → invalid JSON, invalid batch, or schema rejection (with per-index
//         errors so the sender can drop rejected events and retry the rest)
//   200 → DURABLE-ACCEPTANCE ACK, only after the sink put resolved — this
//         ack is the only success signal the collector may trust.

import * as http from 'node:http';
import { isSignalSource, type SignalSource } from '@navdhya/analytics/src/contracts/signal-source';
import { ingestBatch, type IngestDeps } from './ingest';
import { verifyWorkerAuth, type NonceStore } from './worker-auth';

export interface HttpIngestDeps extends IngestDeps {
  workerSecret: string;
  nonceStore: NonceStore;
}

export interface HttpRequestOptions {
  /** `x-signals-source` header — per-request provenance (collector stamps
   *  `shadow` during B3 comparison); defaults to the server-configured
   *  source. Invalid values are rejected, never coerced. */
  sourceHeader?: string | null;
}

export interface HttpResponse {
  status: number;
  body: Record<string, unknown>;
}

export async function handleIngestRequest(
  rawBody: string,
  authHeader: string | null,
  deps: HttpIngestDeps,
  options: HttpRequestOptions = {},
): Promise<HttpResponse> {
  const auth = await verifyWorkerAuth(authHeader, rawBody, {
    secret: deps.workerSecret,
    nonceStore: deps.nonceStore,
  });
  if (!auth.ok) {
    return { status: 401, body: { ok: false, error: auth.reason } };
  }

  let parsed: unknown;
  try {
    parsed = JSON.parse(rawBody);
  } catch {
    return { status: 400, body: { ok: false, error: 'invalid_json' } };
  }
  const events = (parsed as { events?: unknown } | null)?.events;

  let source: SignalSource = deps.source;
  const headerSource = options.sourceHeader;
  if (headerSource !== undefined && headerSource !== null && headerSource !== '') {
    if (!isSignalSource(headerSource)) {
      return { status: 400, body: { ok: false, error: 'invalid_source' } };
    }
    source = headerSource;
  }

  const result = await ingestBatch(events, {
    sink: deps.sink,
    source,
    nowMs: deps.nowMs,
    onDeadLetter: deps.onDeadLetter,
  });
  if (!result.ok) {
    return {
      status: 400,
      body: {
        ok: false,
        rejected: result.rejected,
        deferred: result.deferred,
        persisted: result.persisted,
      },
    };
  }
  return { status: 200, body: { ok: true, persisted: result.persisted } };
}

export function createIngestServer(deps: HttpIngestDeps): http.Server {
  return http.createServer((req, res) => {
    const chunks: Buffer[] = [];
    req.on('data', (c: Buffer) => chunks.push(c));
    req.on('end', () => {
      void handleIngestRequest(
        Buffer.concat(chunks).toString('utf8'),
        req.headers['x-signals-auth'] as string | undefined ?? null,
        deps,
        { sourceHeader: (req.headers['x-signals-source'] as string | undefined) ?? null },
      )
        .then((out) => {
          res.statusCode = out.status;
          res.setHeader('content-type', 'application/json');
          res.end(JSON.stringify(out.body));
        })
        .catch((err) => {
          res.statusCode = 500; // never ack what we could not process
          res.end(JSON.stringify({ ok: false, error: String(err) }));
        });
    });
  });
}
