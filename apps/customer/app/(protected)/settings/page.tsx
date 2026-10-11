import { SettingsPageView } from '@/presentation/settings/SettingsPage';
import { customerApi } from '@/adapters/backend/customer-api-client';
import type { SettingsSection } from '@/application/settings/settings';

export const dynamic = 'force-dynamic';

export default async function SettingsPage() {
  try {
    const data = await customerApi.getSettings();
    const sections = (data as { sections: SettingsSection[] }).sections || [];
    const values = (data as { values: Record<string, unknown> }).values || {};
    return <SettingsPageView sections={sections} values={values} />;
  } catch (e: any) {
    if (e.status === 401) {
      return <SettingsPageView sections={[]} values={{}} />;
    }
    return <SettingsPageView sections={[]} values={{}} />;
  }
}