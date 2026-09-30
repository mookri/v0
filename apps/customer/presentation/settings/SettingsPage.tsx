import { customerSettingsSections } from '@/application/settings/settings';

export function SettingsPageView() {
  return (
    <main>
      <h1>Settings</h1>
      <ul>
        {customerSettingsSections.map((s) => (
          <li key={s.id}>
            <a href={s.href}>{s.title}</a>
          </li>
        ))}
      </ul>
    </main>
  );
}
