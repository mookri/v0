export interface TransactionContext {
  readonly id: string;
}

export interface TransactionManager {
  run<T>(operation: (tx: TransactionContext) => Promise<T>): Promise<T>;
}
