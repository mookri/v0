import { VerificationsPageView } from '@/presentation/verifications/VerificationsPage';
import { getServerCustomerApi } from '@/adapters/backend/customer-api-server';
import type { CustomerVerification } from '@/application/verifications/verifications';

export const dynamic = 'force-dynamic';

// The backend status endpoint returns `{object: contact_verification,
// phone: {...}, email: {...}}`, not a list. Map it to the card model the
// view renders; pending challenge ids never appear here, so these cards
// are informational (confirmation happens right after the OTP request).
interface ContactVerificationPayload {
  phone?: { value_masked?: string | null; verified_at?: string | null };
  email?: { value_masked?: string | null; verified_at?: string | null };
}

function toVerificationCards(payload: unknown): CustomerVerification[] {
  const data = (payload ?? {}) as ContactVerificationPayload;
  const cards: CustomerVerification[] = [];
  for (const type of ['phone', 'email'] as const) {
    const entry = data[type];
    if (!entry?.value_masked) continue;
    cards.push({
      id: '',
      type,
      contact: entry.value_masked,
      status: entry.verified_at ? 'verified' : 'pending',
      createdAt: '',
      confirmedAt: entry.verified_at ?? undefined,
    });
  }
  return cards;
}

export default async function VerificationsPage() {
  try {
    const api = await getServerCustomerApi();
    const data = await api.getVerifications();
    return <VerificationsPageView verifications={toVerificationCards(data)} />;
  } catch (e: any) {
    if (e.status === 401) {
      return <VerificationsPageView verifications={[]} error="Please sign in to view verifications" />;
    }
    return <VerificationsPageView verifications={[]} error="Failed to load verifications" />;
  }
}