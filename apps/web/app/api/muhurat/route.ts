import { NextResponse } from 'next/server';

export async function GET(request: Request) {
  const { searchParams } = new URL(request.url);
  const ceremony = searchParams.get('ceremony') || 'griha_pravesh';
  const date = searchParams.get('date') || new Date().toISOString().split('T')[0];

  const candidateWindows = [
    {
      id: `${ceremony}-1`,
      title: 'Brahma Muhurat (Primary Window)',
      start: `${date}T04:24:00+05:30`,
      end: `${date}T05:12:00+05:30`,
      shubh_score: 94,
      nakshatra: 'Rohini (Pada 2)',
      tithi: 'Shukla Paksha Dashami',
      is_recommended: true,
      notes: 'Guru-Chandra mutual aspect; free from Rahu Kaal and Gulika Kaal.',
    },
    {
      id: `${ceremony}-2`,
      title: 'Abhijit Muhurat (Mid-day Window)',
      start: `${date}T11:42:00+05:30`,
      end: `${date}T12:30:00+05:30`,
      shubh_score: 88,
      nakshatra: 'Rohini (Pada 3)',
      tithi: 'Shukla Paksha Dashami',
      is_recommended: false,
      notes: 'Strong solar transit; all directions permissible.',
    },
  ];

  return NextResponse.json({
    status: 'success',
    ceremony,
    date,
    windows: candidateWindows,
  });
}

