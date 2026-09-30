import { ProfileRepository, SavedRepository, PreferenceRepository } from '@navdhya/customer-profile/src/ports/repositories';
import { resolveLifecycle } from '@navdhya/customer-profile/src/domain/lifecycle';
import { CustomerSelfView } from '@navdhya/customer-profile/src/ports/repositories';

// Parallel fan-out with per-section degradation: one source failing never fails the view.
export async function composeSelfView(deps: {
  profiles: ProfileRepository;
  preferences: PreferenceRepository;
  saved: SavedRepository;
  lifecycleInputs: (customerId: string) => Promise<{ registered: boolean; profileStarted: boolean; completedBookings: number; lastActivityAt?: string }>;
  inactivityDays: number;
  nowIso: string;
  customerId: string;
}): Promise<CustomerSelfView> {
  const [profile, prefs, saved, facts] = await Promise.all([
    deps.profiles.get(deps.customerId).catch(() => null),
    deps.preferences.list(deps.customerId).catch(() => ({ explicit: [], inferred: [] })),
    deps.saved.list(deps.customerId).catch(() => []),
    deps.lifecycleInputs(deps.customerId),
  ]);
  const lifecycle = resolveLifecycle({ ...facts, inactivityDays: deps.inactivityDays, nowIso: deps.nowIso });
  return {
    customerId: deps.customerId,
    profile,
    preferences: prefs.explicit,
    savedCount: {
      pujas: saved.filter((s) => s.entityType === 'PUJA').length,
      pandits: saved.filter((s) => s.entityType === 'PANDIT').length,
    },
    lifecycle: lifecycle.state,
    projectionVersion: 'customer360-v1',
    generatedAt: deps.nowIso,
  };
}
