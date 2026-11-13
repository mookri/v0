'use client';

import React, { useEffect, useState } from 'react';

interface MetricDefinition {
  key: string;
  label: string;
  value: number;
  unit: string;
  numerator: string;
  denominator: string;
  formula: string;
  as_of: string;
}

interface StatsResponse {
  data?: {
    metrics?: MetricDefinition[];
    tenant?: { code: string; name: string; state: string };
  };
  error?: string;
  message?: string;
}

export function DashboardHome() {
  const [metrics, setMetrics] = useState<MetricDefinition[] | null>(null);
  const [tenantName, setTenantName] = useState<string | null>(null);
  const [statusError, setStatusError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let active = true;
    fetch('/api/ops/stats', { cache: 'no-store' })
      .then(async (res) => {
        const body = (await res.json().catch(() => ({}))) as StatsResponse;
        if (!active) return;
        if (!res.ok) {
          setStatusError(body.message || `Telemetry unavailable (${res.status})`);
          setLoading(false);
          return;
        }
        setMetrics(body.data?.metrics ?? []);
        setTenantName(body.data?.tenant?.name ?? null);
        setLoading(false);
      })
      .catch(() => {
        if (!active) return;
        setStatusError('Unable to reach /api/ops/stats backend.');
        setLoading(false);
      });
    return () => {
      active = false;
    };
  }, []);

  return (
    <div className="space-y-8 text-[#2A2421]">
      <header className="border-b border-[#EFE3D3] pb-5">
        <p className="text-xs font-semibold uppercase tracking-widest text-[#B74717]">
          Sacred Ember Control Plane {tenantName ? `· ${tenantName}` : ''}
        </p>
        <h1 className="mt-1 text-2xl font-bold text-[#2C2033]">Platform Operations Overview</h1>
        <p className="text-sm text-[#2A2421] mt-1">
          Every KPI binds to an explicit numerator, denominator, and formula definition. Unavailable backends report honestly rather than showing estimates.
        </p>
      </header>

      {loading ? (
        <div className="rounded-xl border border-[#EFE3D3] bg-white p-6 text-sm text-[#2A2421]">
          Loading authoritative operational metrics...
        </div>
      ) : statusError ? (
        <div
          role="status"
          className="rounded-xl border border-[#B74717] bg-[#EFE3D3] p-6 text-sm text-[#2C2033]"
        >
          <p className="font-semibold text-[#B74717]">Operational KPI Feed Unavailable</p>
          <p className="mt-1 text-[#2A2421]">{statusError}</p>
        </div>
      ) : (
        <section className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" aria-label="Key metrics">
          {(metrics ?? []).map((metric) => (
            <article
              key={metric.key}
              className="rounded-xl border border-[#EFE3D3] bg-white p-6 shadow-sm"
            >
              <p className="text-xs font-semibold uppercase tracking-wide text-[#B74717]">{metric.label}</p>
              <div className="mt-2 flex items-baseline gap-2">
                <p className="text-3xl font-bold text-[#2C2033]">{metric.value}</p>
                <span className="text-xs font-medium text-[#2A2421]">{metric.unit}</span>
              </div>
              <dl className="mt-4 space-y-1 border-t border-[#EFE3D3] pt-3 text-xs text-[#2A2421]">
                <div>
                  <dt className="inline font-semibold text-[#2C2033]">Formula: </dt>
                  <dd className="inline">{metric.formula}</dd>
                </div>
                <div>
                  <dt className="inline font-semibold text-[#2C2033]">Numerator: </dt>
                  <dd className="inline">{metric.numerator}</dd>
                </div>
                <div>
                  <dt className="inline font-semibold text-[#2C2033]">Denominator: </dt>
                  <dd className="inline">{metric.denominator}</dd>
                </div>
              </dl>
            </article>
          ))}
        </section>
      )}
    </div>
  );
}

export default DashboardHome;
