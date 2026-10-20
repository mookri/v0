import { ApplicationDocument } from '@navdhya/partner/src/onboarding/documents';
import { DocumentStore, UploadUrlPort } from '@navdhya/partner/src/ports/documents';

export class MemoryDocumentStore implements DocumentStore {
  private readonly byId = new Map<string, ApplicationDocument>();

  async save(doc: ApplicationDocument): Promise<void> {
    if (this.byId.has(doc.id)) throw new Error(`DOCUMENT_EXISTS:${doc.id}`);
    this.byId.set(doc.id, { ...doc });
  }

  async findById(id: string): Promise<ApplicationDocument | null> {
    const found = this.byId.get(id);
    return found ? { ...found } : null;
  }

  async findByApplication(applicationId: string): Promise<ApplicationDocument[]> {
    return [...this.byId.values()].filter((d) => d.applicationId === applicationId).map((d) => ({ ...d }));
  }

  async update(doc: ApplicationDocument): Promise<void> {
    if (!this.byId.has(doc.id)) throw new Error(`DOCUMENT_NOT_FOUND:${doc.id}`);
    this.byId.set(doc.id, { ...doc });
  }
}

const UPLOAD_TTL_SECONDS = 900;

export class MemoryUploadUrls implements UploadUrlPort {
  async issue(input: {
    applicationId: string;
    kind: string;
    contentLength: number;
    sha256: string;
    nowIso: string;
  }): Promise<{ url: string; objectKey: string; expiresAtIso: string }> {
    if (input.contentLength <= 0) throw new Error('UPLOAD_EMPTY');
    const objectKey = `onboarding/${input.applicationId}/${input.kind}/${input.sha256}`;
    return {
      url: `memory://upload/${objectKey}`,
      objectKey,
      expiresAtIso: new Date(Date.parse(input.nowIso) + UPLOAD_TTL_SECONDS * 1000).toISOString(),
    };
  }
}
