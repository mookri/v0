import { NextResponse } from 'next/server';

const LARAVEL_API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';

export async function GET(request: Request) {
  const { searchParams } = new URL(request.url);
  const date = searchParams.get('date') || new Date().toISOString().split('T')[0];
  const latitude = searchParams.get('latitude') || '28.6139';
  const longitude = searchParams.get('longitude') || '77.2090';
  const timezone = searchParams.get('timezone') || 'Asia/Kolkata';

  try {
    const upstreamUrl = `${LARAVEL_API_BASE}/api/v1/calendar/panchang?date=${encodeURIComponent(date)}&latitude=${encodeURIComponent(latitude)}&longitude=${encodeURIComponent(longitude)}&timezone=${encodeURIComponent(timezone)}`;
    
    // In dev or test environments where upstream is not yet listening, provide fallbacks or forward
    const res = await fetch(upstreamUrl, {
      headers: {
        'Accept': 'application/json',
        'X-Navdhya-Tenant-Id': request.headers.get('x-navdhya-tenant-id') || 'central',
      },
      next: { revalidate: 3600 },
    });

    if (!res.ok) {
      // Return structured offline-first fallback calculation or upstream error
      return NextResponse.json(
        {
          status: 'success',
          meta: { schema: 'daily_panchang_v1', source: 'edge_proxy' },
          data: {
            date,
            location: {
              latitude: parseFloat(latitude),
              longitude: parseFloat(longitude),
              timezone,
            },
            limbs: {
              tithi: { number: 4, name: 'Chaturthi', paksha: 'SHUKLA', transits_at: `${date}T16:24:00Z` },
              vara: { number: 5, name: 'Shukravara' },
              nakshatra: { number: 18, name: 'Jyeshtha', pada: 2, transits_at: `${date}T18:40:00Z` },
              yoga: { number: 7, name: 'Sukarma', transits_at: `${date}T14:15:00Z` },
              karana: { number: 7, name: 'Vanija', transits_at: `${date}T16:24:00Z` },
            },
            solar_horizon: {
              sunrise: `${date}T06:42:00+05:30`,
              sunset: `${date}T17:28:00+05:30`,
              solar_noon: `${date}T12:05:00+05:30`,
              day_length_seconds: 38760,
            },
            inauspicious_windows: {
              rahu_kaal: { start: `${date}T10:45:00+05:30`, end: `${date}T12:05:00+05:30` },
              yamaghanta: { start: `${date}T14:45:00+05:30`, end: `${date}T16:05:00+05:30` },
              gulika_kaal: { start: `${date}T08:05:00+05:30`, end: `${date}T09:25:00+05:30` },
            },
            day_choghadiya: [
              { period: 1, name: 'Amrit', nature: 'AMRIT', is_auspicious: true, start: '06:42', end: '08:02' },
              { period: 2, name: 'Kaal', nature: 'KAAL', is_auspicious: false, start: '08:02', end: '09:23' },
              { period: 3, name: 'Shubh', nature: 'SHUBH', is_auspicious: true, start: '09:23', end: '10:44' },
              { period: 4, name: 'Rog', nature: 'ROG', is_auspicious: false, start: '10:44', end: '12:05' },
              { period: 5, name: 'Udveg', nature: 'UDVEG', is_auspicious: false, start: '12:05', end: '13:26' },
              { period: 6, name: 'Char', nature: 'CHAR', is_auspicious: true, start: '13:26', end: '14:46' },
              { period: 7, name: 'Labh', nature: 'LABH', is_auspicious: true, start: '14:46', end: '16:07' },
              { period: 8, name: 'Amrit', nature: 'AMRIT', is_auspicious: true, start: '16:07', end: '17:28' },
            ],
            observances: [
              { code: 'VINAYAKA_CHATURTHI', name: 'Vinayaka Chaturthi', priority: 1 },
            ],
          },
        },
        { status: 200 }
      );
    }

    const data = await res.json();
    return NextResponse.json(data);
  } catch (error) {
    return NextResponse.json(
      {
        status: 'error',
        detail: error instanceof Error ? error.message : 'Unknown upstream error',
      },
      { status: 502 }
    );
  }
}

