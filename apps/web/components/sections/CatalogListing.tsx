'use client';

import React, { useEffect, useState } from 'react';
import Link from 'next/link';
import { Clock, Flame, ShieldCheck, Sparkles, ChevronRight, Package, ArrowRight } from 'lucide-react';

export interface CatalogListingItem {
  id: string;
  sku: string;
  slug?: string;
  name: string;
  description?: string;
  item_kind?: string;
  base_duration_minutes?: number;
  price_minor?: number;
  currency?: string;
  features?: string[];
  popular?: boolean;
}

interface CatalogListingProps {
  categorySlug?: string;
  title?: string;
  description?: string;
  fallbackItems?: CatalogListingItem[];
}

export function CatalogListing({
  categorySlug,
  title = 'Sacred Ritual Services',
  description = 'Authoritative Vedic ceremonies performed by verified pandits with shastric precision and complete samagri.',
  fallbackItems = [],
}: CatalogListingProps) {
  const [items, setItems] = useState<CatalogListingItem[]>(fallbackItems);
  const [loading, setLoading] = useState<boolean>(false);
  const [selectedTier, setSelectedTier] = useState<Record<string, string>>({});

  useEffect(() => {
    let isMounted = true;
    async function loadCatalog() {
      try {
        setLoading(true);
        const url = categorySlug
          ? `/api/v1/catalog/public/items?category=${encodeURIComponent(categorySlug)}`
          : '/api/v1/catalog/public/items';
        const res = await fetch(url);
        if (res.ok) {
          const data = await res.json();
          if (isMounted && Array.isArray(data.data) && data.data.length > 0) {
            setItems(data.data);
          }
        }
      } catch {
        // Fallback items retained on fetch failure
      } finally {
        if (isMounted) setLoading(false);
      }
    }

    loadCatalog();
    return () => {
      isMounted = false;
    };
  }, [categorySlug]);

  const formatPrice = (minor?: number, currency = 'INR', tierMultiplier = 1) => {
    if (!minor || minor <= 0) return 'Custom Quote';
    const amount = (minor * tierMultiplier) / 100;
    return new Intl.NumberFormat('en-IN', {
      style: 'currency',
      currency,
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(amount);
  };

  const getTierMultiplier = (tier?: string) => {
    if (tier === 'PREMIUM') return 1.35;
    if (tier === 'COMPLETE') return 1.8;
    return 1.0;
  };

  return (
    <section className="py-16 px-4 sm:px-6 lg:px-8 bg-[#FFF7ED]">
      <div className="max-w-7xl mx-auto">
        {/* Section Header */}
        <div className="text-center max-w-3xl mx-auto mb-14">
          <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#EFE3D3] border border-[#B74717]/20 text-[#B74717] text-xs font-semibold uppercase tracking-wider mb-4">
            <Flame className="w-3.5 h-3.5 text-[#F16A24]" />
            Authoritative Vedic Catalog
          </div>
          <h2 className="text-3xl sm:text-4xl font-serif font-bold text-[#2C2033] tracking-tight mb-4">
            {title}
          </h2>
          <p className="text-base sm:text-lg text-[#2A2421]/80 leading-relaxed">
            {description}
          </p>
        </div>

        {/* Catalog Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          {items.map((item) => {
            const activeTier = selectedTier[item.id] || 'STANDARD';
            const multiplier = getTierMultiplier(activeTier);
            const duration = item.base_duration_minutes || 90;
            const priceDisplay = formatPrice(item.price_minor || 250000, item.currency || 'INR', multiplier);

            return (
              <div
                key={item.id}
                className="bg-white rounded-2xl border border-[#EFE3D3] shadow-sm hover:shadow-md transition-all duration-300 flex flex-col overflow-hidden group"
              >
                {/* Card Header & Badge */}
                <div className="p-6 pb-4 border-b border-[#EFE3D3]/60 bg-[#FFF7ED]/50">
                  <div className="flex items-center justify-between gap-2 mb-2">
                    <span className="text-xs font-mono font-medium text-[#2C2033]/60 uppercase tracking-wider">
                      {item.sku}
                    </span>
                    <div className="flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-[#EFE3D3] text-[#2C2033] text-xs font-medium">
                      <Clock className="w-3 h-3 text-[#F16A24]" />
                      <span>{duration}m</span>
                    </div>
                  </div>
                  <h3 className="text-xl font-serif font-bold text-[#2C2033] group-hover:text-[#F16A24] transition-colors">
                    {item.name}
                  </h3>
                </div>

                {/* Card Body */}
                <div className="p-6 flex-1 flex flex-col justify-between space-y-6">
                  {item.description && (
                    <p className="text-sm text-[#2A2421]/80 line-clamp-3 leading-relaxed">
                      {item.description}
                    </p>
                  )}

                  {/* 2-Axis Package Tier Picker */}
                  <div className="space-y-2">
                    <label className="text-xs font-semibold text-[#2C2033] uppercase tracking-wider block">
                      Package Tier
                    </label>
                    <div className="grid grid-cols-3 gap-1.5 p-1 bg-[#EFE3D3]/60 rounded-lg border border-[#EFE3D3]">
                      {(['STANDARD', 'PREMIUM', 'COMPLETE'] as const).map((tier) => (
                        <button
                          key={tier}
                          type="button"
                          onClick={() => setSelectedTier((prev) => ({ ...prev, [item.id]: tier }))}
                          className={`py-1.5 text-xs font-semibold rounded-md transition-all ${
                            activeTier === tier
                              ? 'bg-[#2C2033] text-[#FFF7ED] shadow-sm'
                              : 'text-[#2A2421]/70 hover:text-[#2C2033]'
                          }`}
                        >
                          {tier === 'STANDARD' ? 'Standard' : tier === 'PREMIUM' ? 'Premium' : 'Complete'}
                        </button>
                      ))}
                    </div>
                  </div>

                  {/* Ritual Features / Inclusions */}
                  <div className="space-y-2 text-xs text-[#2A2421]">
                    <div className="flex items-center gap-2">
                      <ShieldCheck className="w-4 h-4 text-[#F16A24] shrink-0" />
                      <span>Verified Vedic Pandits & Shastric Vidhi</span>
                    </div>
                    <div className="flex items-center gap-2">
                      <Package className="w-4 h-4 text-[#B74717] shrink-0" />
                      <span>Pure Samagri kit dispatched to venue</span>
                    </div>
                  </div>

                  {/* Pricing and Action */}
                  <div className="pt-4 border-t border-[#EFE3D3] flex items-center justify-between gap-4">
                    <div>
                      <span className="text-xs text-[#2A2421]/60 block">Price ({activeTier.toLowerCase()})</span>
                      <span className="text-2xl font-bold font-serif text-[#2C2033]">
                        {priceDisplay}
                      </span>
                    </div>

                    <Link
                      href={`/pujas/${item.slug || item.id}?tier=${activeTier}`}
                      className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#F16A24] hover:bg-[#B74717] text-[#FFF7ED] text-sm font-semibold transition-colors shadow-sm"
                    >
                      <span>Book Puja</span>
                      <ChevronRight className="w-4 h-4" />
                    </Link>
                  </div>
                </div>
              </div>
            );
          })}
        </div>

        {items.length === 0 && !loading && (
          <div className="text-center py-12 bg-white rounded-2xl border border-[#EFE3D3] p-8">
            <Sparkles className="w-8 h-8 text-[#F16A24] mx-auto mb-3" />
            <p className="text-base text-[#2C2033] font-medium">No active ceremonies found for this category.</p>
            <p className="text-sm text-[#2A2421]/60 mt-1">Please explore our complete ritual directory or contact support.</p>
          </div>
        )}
      </div>
    </section>
  );
}

