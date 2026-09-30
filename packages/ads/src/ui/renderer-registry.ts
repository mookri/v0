export type AdRendererName = 'native' | 'image' | 'video' | 'sponsored-card' | 'hero' | 'rectangle' | 'vertical' | 'text' | 'empty';

// Renderer registry maps format → renderer name. React components live in
// apps/web/presentation/ads/ and @navdhya/ads/ui adapters — never in core.
export const rendererForFormat: Record<string, AdRendererName> = {
  native: 'native',
  image: 'image',
  video: 'video',
  'sponsored-card': 'sponsored-card',
  hero: 'hero',
  rectangle: 'rectangle',
  vertical: 'vertical',
  text: 'text',
};
