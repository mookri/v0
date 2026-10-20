export interface CustomerActor {
  userId: string;
  name?: string;
}

export interface CustomerSession {
  authenticated: boolean;
  actor?: CustomerActor;
}

export interface SessionAdapter {
  getSession(): Promise<CustomerSession>;
}

export class DevelopmentSessionAdapter implements SessionAdapter {
  async getSession(): Promise<CustomerSession> {
    if (process.env.NODE_ENV === 'production' && !process.env.DEV_CUSTOMER_SESSION) {
      return { authenticated: false };
    }
    if (!process.env.DEV_CUSTOMER_SESSION) return { authenticated: false };
    return { authenticated: true, actor: { userId: 'dev-user-1', name: 'Dev User' } };
  }
}

export class ProductionSessionAdapter implements SessionAdapter {
  async getSession(): Promise<CustomerSession> {
    // SSO/handoff lands here in a later phase. Never localStorage tokens.
    return { authenticated: false };
  }
}
