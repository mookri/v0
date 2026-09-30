export interface DbClient {
  query<T = unknown>(text: string, params?: unknown[]): Promise<T[]>;
}

export interface PostgresConfig {
  connectionString: string;
}

export function createPostgresClient(_config: PostgresConfig): DbClient {
  // Driver instantiation lives here only. Business code never imports a driver.
  // Real driver wiring (pg/pgbouncer) is added when DATABASE_URL is provisioned;
  // until then adapters accept any DbClient (including in-memory fakes in tests).
  throw new Error('Postgres client not configured: provide DATABASE_URL via @navdhya/config');
}

export function createNoopDbClient(): DbClient {
  return { query: async <T>() => [] as T[] };
}
