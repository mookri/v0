export interface Transaction {
  run<T>(fn: () => Promise<T>): Promise<T>;
}
