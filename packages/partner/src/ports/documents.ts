import { ApplicationDocument } from '@navdhya/partner/src/onboarding/documents';

// Document metadata port (partner-owned). Object bytes live behind the
// object-storage port at composition time; this store holds metadata only.
export interface DocumentStore {
  save(doc: ApplicationDocument): Promise<void>;
  findById(id: string): Promise<ApplicationDocument | null>;
  findByApplication(applicationId: string): Promise<ApplicationDocument[]>;
  update(doc: ApplicationDocument): Promise<void>;
}

// Scoped upload URL issuance (partner-owned). Production implementation
// signs object-storage URLs bound to (applicationId, kind, size, hash);
// memory adapter returns deterministic fake URLs for dev/staging.
export interface UploadUrlPort {
  issue(input: {
    applicationId: string;
    kind: string;
    contentLength: number;
    sha256: string;
    nowIso: string;
  }): Promise<{ url: string; objectKey: string; expiresAtIso: string }>;
}
