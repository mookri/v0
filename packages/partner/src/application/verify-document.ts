import {
  ApplicationDocument,
  DocumentKind,
  DocumentRejectReason,
  allowedMimes,
  markDocumentVerified,
  markDocumentRejected,
  MAX_DOCUMENT_BYTES,
} from '@navdhya/partner/src/onboarding/documents';

// Verification worker logic (pure; queue binding lands separately).
// Every rejection carries a machine-readable reasonCode — the applicant
// message renders from the taxonomy, never from free text.
export interface DocumentEvidence {
  mime: string;
  sizeBytes: number;
  sha256: string;
  expired?: boolean;
}

export function verifyDocument(
  doc: ApplicationDocument,
  evidence: DocumentEvidence,
  nowIso: string,
): { document: ApplicationDocument; ok: true } | { document: ApplicationDocument; ok: false; reason: DocumentRejectReason } {
  const reject = (reasonCode: DocumentRejectReason) => ({
    document: markDocumentRejected(doc, reasonCode, nowIso),
    ok: false as const,
    reason: reasonCode,
  });
  if (!allowedMimes(doc.kind as DocumentKind).includes(evidence.mime)) return reject('MIME_NOT_ALLOWED');
  if (evidence.sizeBytes <= 0 || evidence.sizeBytes > MAX_DOCUMENT_BYTES) return reject('TOO_LARGE');
  if (doc.sha256 && doc.sha256 !== evidence.sha256) return reject('HASH_MISMATCH');
  if (evidence.expired) return reject('EXPIRED_EVIDENCE');
  return { document: markDocumentVerified({ ...doc, sha256: evidence.sha256 }, nowIso), ok: true };
}
