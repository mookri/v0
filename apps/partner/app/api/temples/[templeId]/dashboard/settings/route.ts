import { getDashboardSection } from '@/server/temples';

// Mock payload is a fully shaped (zeroed) settings object so the page
// renders without fixture data; the laravel branch passes the kernel
// `{temple, managers, amenities}` payload through untouched.
export function GET(req: Request, ctx: { params: Promise<{ templeId: string }> }) {
  return getDashboardSection(req, ctx, {
    section: 'settings',
    mockPayload: {
      temple: {
        id: '',
        slug: '',
        name: '—',
        trust_name: '',
        type: 'individual',
        address: '',
        city: '',
        state: '',
        lat: null,
        lng: null,
        images: [],
        amenities: [],
        description: null,
        status: 'INACTIVE',
        verification_status: 'PENDING',
      },
      managers: [],
      amenities: [],
    },
    message: 'Dashboard settings are unavailable.',
  });
}
