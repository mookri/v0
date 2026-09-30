export type VerificationType = 'IDENTITY' | 'DOCUMENT' | 'CERTIFICATION' | 'BACKGROUND' | 'PROFILE';
export type VerificationStatus = 'PENDING' | 'IN_REVIEW' | 'VERIFIED' | 'REJECTED' | 'EXPIRED';

export interface VerificationCase {
  id: string;
  partnerId: string;
  type: VerificationType;
  status: VerificationStatus;
  reviewedAt?: string;
  reviewerId?: string;
}

export type DocumentStatus = 'UPLOADED' | 'UNDER_REVIEW' | 'VERIFIED' | 'REJECTED' | 'EXPIRED';

export interface PartnerDocument {
  id: string;
  partnerId: string;
  type: string;
  storageReference: string;
  status: DocumentStatus;
  issuedAt?: string;
  expiresAt?: string;
}

export function isDocumentUsable(doc: PartnerDocument, nowIso: string): boolean {
  if (doc.status !== 'VERIFIED') return false;
  if (doc.expiresAt && doc.expiresAt <= nowIso) return false;
  return true;
}
