export interface AeoFact {
  claim: string;
  source: string;
}

export interface AeoDocument {
  entityId?: string;
  title: string;
  summary: string;
  canonicalUrl: string;
  facts: AeoFact[];
  sourceUpdatedAt: string;
}

export function buildSummary(title: string, summary?: string): string {
  return summary ?? title;
}

export function buildLlmsTxt(docs: { title: string; canonicalUrl: string; summary: string }[]): string {
  return docs.map((d) => `- [${d.title}](${d.canonicalUrl}): ${d.summary}`).join('\n');
}

export function buildLlmsFullTxt(docs: AeoDocument[]): string {
  return docs
    .map((d) => `# ${d.title}\nCanonical: ${d.canonicalUrl}\n\n${d.summary}\n${d.facts.map((f) => `- ${f.claim}`).join('\n')}`)
    .join('\n\n---\n\n');
}
