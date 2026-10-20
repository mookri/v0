// Re-export from the shared @navdhya/search package (S62).
// The implementation now lives in packages/search; this shim keeps
// existing `@/lib/search/bm25` imports working.
export * from '@navdhya/search';