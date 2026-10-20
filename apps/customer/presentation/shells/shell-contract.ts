export type PresentationMode = 'compact' | 'medium' | 'expanded';

export type NetworkState = 'online' | 'offline' | 'degraded';

export type SessionStatus = 'valid' | 'expiring' | 'expired' | 'reauth-required';

export interface AppBarAction {
  id: string;
  label: string;
  href?: string;
}

export interface AppBarConfig {
  title?: string;
  subtitle?: string;
  mode?: 'standard' | 'back' | 'transparent';
  trailing?: AppBarAction[];
}

export interface SurfaceRequest {
  kind: 'modal' | 'sheet' | 'drawer' | 'fullscreen';
  surfaceId: string;
  routeAware?: boolean;
}

export interface FeedbackMessage {
  kind: 'toast' | 'banner' | 'inline' | 'dialog';
  text: string;
}

export interface ShellContext {
  setAppBar(config: AppBarConfig): void;
  openSurface(request: SurfaceRequest): void;
  showFeedback(feedback: FeedbackMessage): void;
}

export const CUSTOMER_SHELL_VERSION = 'customer-shell-v1';
