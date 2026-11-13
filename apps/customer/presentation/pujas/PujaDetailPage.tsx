'use client';

import { useState } from 'react';
import Link from 'next/link';
import { generateIdempotencyKey } from '@/application/idempotency';
import { pujaBookStartUrl } from '@/application/bookings/puja-book-start-url';

interface PujaDetailPageProps {
  puja: {
    id: string;
    title: string;
    kind: 'puja';
    sku: string;
    currency: string;
    price_minor: number;
    description?: string;
    base_duration_minutes?: number;
  };
  error?: string;
}

type PackageTier = 'STANDARD' | 'PREMIUM' | 'COMPLETE';

const TIER_CONFIG: Record<
  PackageTier,
  { name: string; multiplier: number; label: string; description: string }
> = {
  STANDARD: {
    name: 'Standard',
    multiplier: 1.0,
    label: 'Standard Vidhi',
    description: 'Essential Vedic rituals with authentic consecrated samagri kit.',
  },
  PREMIUM: {
    name: 'Premium',
    multiplier: 1.35,
    label: 'Extended Vidhi',
    description: 'Enhanced rituals, extended sacred recitations & supreme samagri.',
  },
  COMPLETE: {
    name: 'Complete',
    multiplier: 1.8,
    label: 'Maha Vidhi',
    description: 'Comprehensive ceremony with senior pandit recitations and full offerings.',
  },
};

function formatPrice(minor: number, currency: string, multiplier: number = 1): string {
  const major = (minor * multiplier) / 100;
  return new Intl.NumberFormat('en-IN', {
    style: 'currency',
    currency,
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(major);
}

export function PujaDetailPageView({ puja, error }: PujaDetailPageProps) {
  const [selectedTier, setSelectedTier] = useState<PackageTier>('STANDARD');

  if (error) {
    return (
      <main className="p-6 max-w-2xl mx-auto bg-[#FFF7ED] min-h-screen">
        <h1 className="text-2xl font-serif font-bold mb-4 text-[#B74717]" role="alert">
          Puja unavailable
        </h1>
        <p className="text-[#2A2421]">{error}</p>
        <Link href="/discover" className="mt-4 inline-block text-[#F16A24] font-medium hover:underline">
          ← Back to discover
        </Link>
      </main>
    );
  }

  const tierInfo = TIER_CONFIG[selectedTier];
  const activePrice = formatPrice(puja.price_minor || 250000, puja.currency, tierInfo.multiplier);
  const duration = puja.base_duration_minutes || 90;

  return (
    <main className="p-6 max-w-3xl mx-auto bg-[#FFF7ED] min-h-screen">
      <Link href="/discover" className="mb-6 inline-flex items-center gap-1.5 text-sm font-medium text-[#2C2033]/70 hover:text-[#2C2033] transition-colors">
        <svg className="w-4 h-4 text-[#F16A24]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
        </svg>
        Back to sacred discovery
      </Link>

      <article className="bg-white border border-[#EFE3D3] rounded-2xl overflow-hidden shadow-sm">
        {/* Header */}
        <header className="p-6 sm:p-8 border-b border-[#EFE3D3] bg-[#FFF7ED]/40">
          <div className="flex flex-wrap items-center justify-between gap-2 mb-2">
            <span className="text-xs font-mono font-medium text-[#B74717] bg-[#EFE3D3] px-2.5 py-0.5 rounded-full">
              SKU: {puja.sku}
            </span>
            <div className="flex items-center gap-1.5 text-xs font-medium text-[#2C2033] bg-[#EFE3D3] px-3 py-1 rounded-full">
              <svg className="w-3.5 h-3.5 text-[#F16A24]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" strokeWidth="2" />
                <polyline points="12 6 12 12 16 14" strokeWidth="2" />
              </svg>
              <span>Authoritative Duration: {duration} mins</span>
            </div>
          </div>
          <h1 className="text-2xl sm:text-3xl font-serif font-bold text-[#2C2033]">{puja.title}</h1>
        </header>

        {/* 2-Axis Package Tier Ladder Picker */}
        <section className="p-6 sm:p-8 border-b border-[#EFE3D3] bg-[#FFF7ED]/20">
          <h2 className="text-xs font-bold uppercase tracking-wider text-[#2C2033] mb-3">
            Select Ceremony Tier
          </h2>
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
            {(['STANDARD', 'PREMIUM', 'COMPLETE'] as const).map((tier) => {
              const info = TIER_CONFIG[tier];
              const isSelected = selectedTier === tier;
              const tierPrice = formatPrice(puja.price_minor || 250000, puja.currency, info.multiplier);

              return (
                <button
                  key={tier}
                  type="button"
                  onClick={() => setSelectedTier(tier)}
                  className={`p-4 rounded-xl border text-left transition-all flex flex-col justify-between ${
                    isSelected
                      ? 'border-[#F16A24] bg-[#FFF7ED] ring-2 ring-[#F16A24]/30 shadow-sm'
                      : 'border-[#EFE3D3] bg-white hover:border-[#B74717]/40'
                  }`}
                >
                  <div>
                    <div className="flex items-center justify-between mb-1">
                      <span className="font-serif font-bold text-sm text-[#2C2033]">{info.name}</span>
                      {isSelected && (
                        <span className="w-2 h-2 rounded-full bg-[#F16A24]" />
                      )}
                    </div>
                    <p className="text-xs text-[#2A2421]/70 leading-snug mb-3">{info.description}</p>
                  </div>
                  <div className="text-base font-bold font-serif text-[#2C2033]">
                    {tierPrice}
                  </div>
                </button>
              );
            })}
          </div>
        </section>

        {/* About ritual & Samagri Kit disclosure */}
        <section className="p-6 sm:p-8 border-b border-[#EFE3D3] space-y-6">
          {puja.description && (
            <div>
              <h2 className="text-base font-serif font-bold text-[#2C2033] mb-2">About this ritual</h2>
              <p className="text-sm text-[#2A2421]/90 leading-relaxed whitespace-pre-wrap">{puja.description}</p>
            </div>
          )}

          {/* Samagri Inclusions */}
          <div className="p-4 rounded-xl bg-[#FFF7ED] border border-[#EFE3D3] flex items-start gap-3">
            <svg className="w-5 h-5 text-[#F16A24] mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
            </svg>
            <div className="text-xs text-[#2A2421]">
              <span className="font-bold text-[#2C2033] block mb-0.5">Authoritative Samagri Kit Included</span>
              100% pure shastric ingredients, sacred dhoop, ghee, flowers, and consecrated offerings prepared specifically for this ceremony and dispatched to your venue.
            </div>
          </div>
        </section>

        {/* Footer with Price and Sacred CTA */}
        <footer className="p-6 sm:p-8 bg-[#FFF7ED]/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div className="flex flex-col">
            <span className="text-xs text-[#2A2421]/60">Total for {tierInfo.name} Tier</span>
            <span className="text-3xl font-serif font-bold text-[#2C2033]">{activePrice}</span>
          </div>

          <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <Link
              href={`/book/search?puja_id=${encodeURIComponent(puja.id)}&tier=${selectedTier}`}
              className="px-5 py-3 border border-[#2C2033] text-[#2C2033] rounded-xl text-center text-sm font-semibold hover:bg-[#EFE3D3] transition-colors"
            >
              Check Slots
            </Link>

            <button
              type="button"
              onClick={() => {
                window.location.href = pujaBookStartUrl(
                  puja.id,
                  generateIdempotencyKey(),
                  new Date().toISOString(),
                );
              }}
              className="px-6 py-3 bg-[#F16A24] hover:bg-[#B74717] text-[#FFF7ED] rounded-xl text-center text-sm font-semibold transition-colors shadow-sm"
            >
              Book Ceremony Now
            </button>
          </div>
        </footer>
      </article>
    </main>
  );
}
