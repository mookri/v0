// MP payload validation CLI (plan B4): validate a Measurement Protocol
// payload against GOOGLE's debug endpoint (the live counterpart of the local
// spec check that CI runs on the same fixtures).
//
// Driver-gated by design (same posture as every other driver in this repo):
// needs GA4_MEASUREMENT_ID + GA4_API_SECRET in the environment. CI runs the
// local validator (validateMpPayload in packages/analytics/src/adapters/
// ga4-measurement-protocol.ts) on the fixture set — no network there.
//
// Usage:
//   GA4_MEASUREMENT_ID=G-XXXX GA4_API_SECRET=xxxx \
//     npx tsx tooling/scripts/validate-mp-endpoint.ts --payload mp-batch.json
//
// The payload file is a built MpBatch: { client_id, events: [{name, params}] }
// (produce it with buildMpBatch from the adapter). Google's debug endpoint
// validates the shape WITHOUT storing the hit — it never reports "processed",
// only validation errors or an empty error set.
import * as fs from 'node:fs';
import {
  MP_DEBUG_URL,
  validateMpPayload,
} from '@navdhya/analytics/src/adapters/ga4-measurement-protocol';

function arg(name: string): string | undefined {
  const index = process.argv.indexOf(`--${name}`);
  return index >= 0 ? process.argv[index + 1] : undefined;
}

const payloadFile = arg('payload');
const measurementId = process.env['GA4_MEASUREMENT_ID'];
const apiSecret = process.env['GA4_API_SECRET'];

if (!payloadFile) {
  console.error('Usage: validate-mp-endpoint.ts --payload <mp-batch.json>');
  process.exit(1);
}
if (!measurementId || !apiSecret) {
  console.error(
    'validate-mp-endpoint: GA4_MEASUREMENT_ID and GA4_API_SECRET must be set (driver-gated — run with real credentials).',
  );
  process.exit(1);
}

const batch = JSON.parse(fs.readFileSync(payloadFile, 'utf8')) as Parameters<
  typeof validateMpPayload
>[0];

// Local spec check first — fail fast without burning a network call.
const localErrors = validateMpPayload(batch);
if (localErrors.length > 0) {
  console.error('validate-mp-endpoint: local validation failed:');
  for (const error of localErrors) console.error(`  - ${error}`);
  process.exit(1);
}

const url =
  `${MP_DEBUG_URL}?measurement_id=${encodeURIComponent(measurementId)}` +
  `&api_secret=${encodeURIComponent(apiSecret)}`;

const response = await fetch(url, {
  method: 'POST',
  headers: { 'content-type': 'application/json' },
  body: JSON.stringify(batch),
});
const body = (await response.text()) || '{}';

let validationMessage: unknown = null;
try {
  validationMessage = (JSON.parse(body) as { validationMessages?: unknown }).validationMessages;
} catch {
  validationMessage = body;
}

const messages = Array.isArray(validationMessage) ? validationMessage : [];
if (response.ok && messages.length === 0) {
  console.log(`validate-mp-endpoint: OK — payload accepted by Google's validator (${response.status}).`);
  console.log('Note: acceptance by the debug endpoint is validation only — a real 2xx from /mp/collect is a RECEIPT, never "processed".');
} else {
  console.error(`validate-mp-endpoint: FAILED (HTTP ${response.status})`);
  console.error(JSON.stringify(validationMessage, null, 2));
  process.exit(1);
}
