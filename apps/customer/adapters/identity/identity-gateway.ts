import type { SessionAudience } from '@navdhya/identity/src/domain/session';
import { MemoryHandoffStore } from '@navdhya/identity/src/adapters/memory';
import { LocalIdentityGateway } from '@navdhya/identity/src/adapters/local-gateway';

export interface HandoffRequest {
  subject: string;
  source: SessionAudience;
  audience: SessionAudience;
  returnPath?: string;
}

// Customer consumes the IdentityGateway contract — never credential stores.
// Local adapter today; RemoteIdentityGateway later without app changes.
export function createCustomerIdentityGateway() {
  return new LocalIdentityGateway(new MemoryHandoffStore() as never);
}

export type { HandoffRequest as CustomerHandoffRequest };
