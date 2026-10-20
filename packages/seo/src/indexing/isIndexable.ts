export function isIndexable(input: { status: string; noindex?: boolean; env: string }): boolean {
  if (input.env !== 'production') return false;
  if (input.status !== 'published') return false;
  if (input.noindex) return false;
  return true;
}
