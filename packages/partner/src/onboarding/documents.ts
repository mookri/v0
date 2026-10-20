export type DocumentKind = 'id-proof' | 'address-proof' | 'certification' | 'bank-proof' | 'photo';

export type OnboardingDocumentStatus = 'PENDING' | 'VERIFIED' | 'REJECTED';

export type DocumentRejectReason =
  | 'MIME_NOT_ALLOWED'
  | 'TOO_LARGE'
  | 'HASH_MISMATCH'
  | 'EXPIRED_EVIDENCE'
  | 'ILLEGIBLE';

export interface ApplicationDocument {
  id: string;
  applicationId: string;
  kind: DocumentKind;
  status: OnboardingDocumentStatus;
  objectKey: string;
  sha256?: string;
  reasonCode?: DocumentRejectReason;
  createdAtIso: string;
  verifiedAtIso?: string;
}

const KIND_MIMES: Record<DocumentKind, string[]> = {
  'id-proof': ['image/jpeg', 'image/png', 'application/pdf'],
  'address-proof': ['image/jpeg', 'image/png', 'application/pdf'],
  certification: ['image/jpeg', 'image/png', 'application/pdf'],
  'bank-proof': ['image/jpeg', 'image/png', 'application/pdf'],
  photo: ['image/jpeg', 'image/png'],
};

export const MAX_DOCUMENT_BYTES = 10 * 1024 * 1024;

export function allowedMimes(kind: DocumentKind): string[] {
  return KIND_MIMES[kind];
}

export function markDocumentVerified(doc: ApplicationDocument, nowIso: string): ApplicationDocument {
  if (doc.status !== 'PENDING') throw new Error(`DOCUMENT_NOT_PENDING:${doc.id}`);
  return { ...doc, status: 'VERIFIED', verifiedAtIso: nowIso };
}

export function markDocumentRejected(
  doc: ApplicationDocument,
  reasonCode: DocumentRejectReason,
  nowIso: string,
): ApplicationDocument {
  if (doc.status !== 'PENDING') throw new Error(`DOCUMENT_NOT_PENDING:${doc.id}`);
  void nowIso;
  return { ...doc, status: 'REJECTED', reasonCode };
}
