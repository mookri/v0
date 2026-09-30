import { AdFormat } from '@navdhya/ads/src/domain/creative/Creative';

export interface Placement {
  id: string;
  surface: string;
  format: AdFormat;
  supportedFormats: AdFormat[];
}

export function supportsFormat(p: Placement, format: AdFormat): boolean {
  return p.supportedFormats.includes(format);
}
