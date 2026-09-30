import { AppBarConfig } from '../shells/shell-contract';

export function AppBar({ config }: { config: AppBarConfig }) {
  return (
    <header data-shell="app-bar" data-mode={config.mode ?? 'standard'}>
      {config.mode === 'back' ? <a href=".." aria-label="Back">Back</a> : null}
      {config.title ? <h1>{config.title}</h1> : null}
      {config.subtitle ? <p>{config.subtitle}</p> : null}
      {config.trailing?.map((a) => (
        <a key={a.id} href={a.href ?? '#'}>
          {a.label}
        </a>
      ))}
    </header>
  );
}
