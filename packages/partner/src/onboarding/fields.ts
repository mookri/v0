export type StepFieldType = 'text' | 'tel' | 'number' | 'checkbox' | 'csv' | 'info';

export interface StepField {
  name: string;
  labelKey: string;
  type: StepFieldType;
}

export interface StepLayout {
  // 'object' assembles {field: value}; 'bare' stores the single field's
  // value directly (arrays for services/areas/documents).
  shape: 'object' | 'bare';
  fields: StepField[];
}

export function parseFieldValue(type: StepFieldType, raw: string | boolean): unknown {
  if (type === 'checkbox') return raw === true || raw === 'on' || raw === 'true';
  if (type === 'number') {
    const n = typeof raw === 'string' ? parseInt(raw, 10) : NaN;
    return Number.isNaN(n) ? 0 : n;
  }
  if (type === 'csv') {
    if (typeof raw !== 'string') return [];
    return raw
      .split(',')
      .map((s) => s.trim())
      .filter((s) => s.length > 0);
  }
  return raw;
}

export function assembleStepValue(stepId: string, layout: StepLayout, inputs: Record<string, string | boolean>): unknown {
  void stepId;
  const parsed: Record<string, unknown> = {};
  for (const field of layout.fields) {
    if (field.type === 'info') continue;
    parsed[field.name] = parseFieldValue(field.type, inputs[field.name] ?? '');
  }
  if (layout.shape === 'bare') {
    const only = layout.fields.find((f) => f.type !== 'info');
    return only ? parsed[only.name] : [];
  }
  return parsed;
}
