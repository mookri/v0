import { SettingsPageView } from '@/presentation/settings/SettingsPage';
import { getServerCustomerApi } from '@/adapters/backend/customer-api-server';
import { DEFAULT_SETTINGS, type SettingsSection } from '@/application/settings/settings';

export const dynamic = 'force-dynamic';

export default async function SettingsPage() {
  try {
    const api = await getServerCustomerApi();
    const data = await api.getSettings();
    const sections = (data as { sections: SettingsSection[] }).sections || [];
    // Merge the typed defaults so a partial or empty backend map still
    // renders booleans as toggles (not blank text inputs).
    const stored = (data as { values?: Record<string, unknown> }).values ?? {};
    const values = { ...DEFAULT_SETTINGS, ...stored };
    return <SettingsPageView sections={sections} values={values} />;
  } catch (e: any) {
    if (e.status === 401) {
      return <SettingsPageView sections={[]} values={DEFAULT_SETTINGS} />;
    }
    return <SettingsPageView sections={[]} values={DEFAULT_SETTINGS} />;
  }
}