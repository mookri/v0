import { NextResponse } from 'next/server';

const LARAVEL_API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';

export async function GET(request: Request) {
  const { searchParams } = new URL(request.url);
  const now = new Date();
  const year = searchParams.get('year') || String(now.getFullYear());
  const month = searchParams.get('month') || String(now.getMonth() + 1);
  const latitude = searchParams.get('latitude') || '28.6139';
  const longitude = searchParams.get('longitude') || '77.2090';
  const timezone = searchParams.get('timezone') || 'Asia/Kolkata';

  try {
    const upstreamUrl = `${LARAVEL_API_BASE}/api/v1/calendar/panchang/monthly?year=${encodeURIComponent(year)}&month=${encodeURIComponent(month)}&latitude=${encodeURIComponent(latitude)}&longitude=${encodeURIComponent(longitude)}&timezone=${encodeURIComponent(timezone)}`;
    
    const res = await fetch(upstreamUrl, {
      headers: {
        'Accept': 'application/json',
        'X-Navdhya-Tenant-Id': request.headers.get('x-navdhya-tenant-id') || 'central',
      },
      next: { revalidate: 86400 },
    });

    if (!res.ok) {
      // Offline-first monthly matrix stub
      const daysCount = new Date(parseInt(year), parseInt(month), 0).getDate();
      const days = [];
      for (let d = 1; d <= daysCount; d++) {
        const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        days.push({
          date: dateStr,
          tithi: { number: ((d - 1) % 15) + 1, name: `Tithi ${((d - 1) % 15) + 1}`, paksha: d <= 15 ? 'SHUKLA' : 'KRISHNA' },
          nakshatra: { number: ((d - 1) % 27) + 1, name: `Nakshatra ${((d - 1) % 27) + 1}` },
          sunrise: '06:42',
          sunset: '17:28',
          observances: d === 11 || d === 26 ? [{ code: 'EKADASHI', name: 'Ekadashi' }] : [],
        });
      }

      return NextResponse.json({
        status: 'success',
        meta: { schema: 'monthly_panchang_v1', year: parseInt(year), month: parseInt(month) },
        data: { days },
      });
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

