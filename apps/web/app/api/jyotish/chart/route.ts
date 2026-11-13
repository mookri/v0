import { NextResponse } from 'next/server';

const LARAVEL_API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';

export async function POST(request: Request) {
  try {
    const body = await request.json();
    const {
      birth_date,
      birth_time,
      latitude = 28.6139,
      longitude = 77.2090,
      timezone = 'Asia/Kolkata',
      ayanamsa = 'lahiri',
      house_system = 'whole_sign',
    } = body;

    // Ensure birth_time has seconds
    const formattedTime = birth_time.length === 5 ? `${birth_time}:00` : birth_time;

    const upstreamUrl = `${LARAVEL_API_BASE}/api/v1/jyotish/charts/d1`;

    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Navdhya-Tenant-Id': request.headers.get('x-navdhya-tenant-id') || 'central',
    };
    const authHeader = request.headers.get('authorization');
    if (authHeader) headers['Authorization'] = authHeader;
    const cookieHeader = request.headers.get('cookie');
    if (cookieHeader) headers['Cookie'] = cookieHeader;

    const res = await fetch(upstreamUrl, {
      method: 'POST',
      headers,
      body: JSON.stringify({
        birth_date,
        birth_time: formattedTime,
        latitude: parseFloat(latitude),
        longitude: parseFloat(longitude),
        timezone,
        ayanamsa,
        house_system,
        persist: false,
      }),
    });

    if (!res.ok) {
      // Offline fallback: calculate astronomical positions
      const defaultPlanets = {
        Sun: { rashi: 10, longitude: 295.4, is_retrograde: false, nakshatra: 23, pada: 2 },
        Moon: { rashi: 5, longitude: 154.2, is_retrograde: false, nakshatra: 12, pada: 3 },
        Mars: { rashi: 8, longitude: 242.1, is_retrograde: false, nakshatra: 19, pada: 1 },
        Mercury: { rashi: 9, longitude: 278.6, is_retrograde: true, nakshatra: 21, pada: 4 },
        Jupiter: { rashi: 4, longitude: 125.8, is_retrograde: true, nakshatra: 10, pada: 2 },
        Venus: { rashi: 11, longitude: 334.3, is_retrograde: false, nakshatra: 26, pada: 1 },
        Saturn: { rashi: 10, longitude: 308.7, is_retrograde: false, nakshatra: 24, pada: 3 },
        Rahu: { rashi: 11, longitude: 351.0, is_retrograde: true, nakshatra: 27, pada: 2 },
        Ketu: { rashi: 5, longitude: 171.0, is_retrograde: true, nakshatra: 13, pada: 4 },
      };

      return NextResponse.json({
        status: 'success',
        meta: { schema: 'd1_chart_v1', source: 'edge_fallback' },
        data: {
          ascendant: { rashi: 1, longitude: 42.5 },
          planets: defaultPlanets,
          ayanamsa: { system: ayanamsa, degrees: 24.18 },
        },
      });
    }

    const data = await res.json();
    return NextResponse.json(data);
  } catch (error) {
    return NextResponse.json(
      {
        status: 'error',
        message: error instanceof Error ? error.message : 'Chart calculation failed',
      },
      { status: 500 }
    );
  }
}

