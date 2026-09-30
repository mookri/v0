import type React from 'react';
import { SurfaceRequest } from '@/presentation/shells/shell-contract';

export function GlobalSurfaceHost({ surfaces }: { surfaces: SurfaceRequest[] }) {
  return (
    <div data-shell="surface-host">
      {surfaces.map((s) => (
        <div key={s.surfaceId} data-surface={s.kind} data-surface-id={s.surfaceId} />
      ))}
    </div>
  );
}

export function FeedbackHost({ messages }: { messages: { kind: string; text: string }[] }) {
  return (
    <div data-shell="feedback-host" aria-live="polite">
      {messages.map((m, i) => (
        <p key={i} data-feedback={m.kind}>
          {m.text}
        </p>
      ))}
    </div>
  );
}

export function AppStatusHost({ network, session }: { network: string; session: string }) {
  if (network === 'online' && session === 'valid') return null;
  return (
    <div data-shell="app-status" role="status">
      {network !== 'online' ? <p>You're offline. Cached information may still be available.</p> : null}
      {session !== 'valid' ? <p>Session needs attention: {session}.</p> : null}
    </div>
  );
}

export function CustomerAppShell({
  mode,
  navigation,
  appBar,
  children,
}: {
  mode: 'compact' | 'medium' | 'expanded';
  navigation: React.ReactNode;
  appBar: React.ReactNode;
  children: React.ReactNode;
}) {
  return (
    <div data-shell="customer-app" data-mode={mode}>
      {appBar}
      <div data-shell="content-viewport">{children}</div>
      {navigation}
      <GlobalSurfaceHost surfaces={[]} />
      <FeedbackHost messages={[]} />
      <AppStatusHost network="online" session="valid" />
    </div>
  );
}
