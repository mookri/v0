export type ProductId = 'web' | 'customer' | 'partner' | 'admin' | 'operations';
export type ViewportMode = 'mobile' | 'tablet' | 'desktop';
export type ExperienceState = 'default' | 'onboarding' | 'campaign' | 'error';

export interface LayoutContext {
  product: { id: ProductId };
  route: { id: string; category: string; intent: string };
  viewport: { mode: ViewportMode };
  actor: { authenticated: boolean; roles: string[] };
  locale: { language: string; region?: string };
  experience: { state: ExperienceState };
  experiment?: { assignments: Record<string, string> };
}
