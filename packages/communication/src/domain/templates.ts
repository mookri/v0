export interface TemplateVariableSchema {
  [name: string]: 'string' | 'number';
}

export interface Template {
  id: string;
  version: string;
  locale: string;
  status: 'DRAFT' | 'ACTIVE' | 'DEPRECATED' | 'ARCHIVED';
  variables: TemplateVariableSchema;
  body: string;
}

export function renderTemplate(template: Template, values: Record<string, string | number>): string {
  const missing = Object.keys(template.variables).filter((k) => values[k] === undefined);
  if (missing.length > 0) throw new Error(`TEMPLATE_VARIABLE_MISSING:${missing.join(',')}`);
  const extra = Object.keys(values).filter((k) => !(k in template.variables));
  if (extra.length > 0) throw new Error(`TEMPLATE_VARIABLE_UNKNOWN:${extra.join(',')}`);
  if (/<script[\s>]/i.test(template.body)) throw new Error('TEMPLATE_UNSAFE');
  return template.body.replace(/\{\{(\w+)\}\}/g, (_, name: string) => String(values[name]));
}

// Deduplication identity: rule + event + recipient + channel.
export function dedupKey(input: { rule: string; eventId: string; userId: string; channel: string }): string {
  return `${input.rule}:${input.eventId}:${input.userId}:${input.channel}`;
}

export type RetryClass = 'RETRYABLE' | 'PERMANENT';

export function classifyProviderError(code: string): RetryClass {
  if (['TIMEOUT', 'RATE_LIMITED', 'TEMPORARY', 'UNAVAILABLE'].includes(code)) return 'RETRYABLE';
  return 'PERMANENT';
}

export function backoffMs(attempt: number): number {
  return Math.min(30_000, 500 * 2 ** attempt);
}
