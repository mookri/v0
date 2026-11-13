'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';

export type PartnerEntityKind = 'pandit' | 'temple' | 'vendor';

export interface EntitySwitcherProps {
  tenantLabel?: string;
}

const ENTITIES: Array<{
  kind: PartnerEntityKind;
  label: string;
  href: string;
  matchPrefixes: string[];
}> = [
  { kind: 'pandit', label: 'Pandit Courtyard', href: '/pandit', matchPrefixes: ['/pandit', '/assignments', '/calendar', '/earnings'] },
  { kind: 'temple', label: 'Temple Management', href: '/temples', matchPrefixes: ['/temples'] },
  { kind: 'vendor', label: 'Samagri Vendor', href: '/vendors', matchPrefixes: ['/vendors'] },
];

export function detectActiveEntity(pathname: string): PartnerEntityKind {
  for (const item of ENTITIES) {
    if (item.matchPrefixes.some((p) => pathname === p || pathname.startsWith(`${p}/`))) {
      return item.kind;
    }
  }
  return 'pandit';
}

export function EntitySwitcher({ tenantLabel }: EntitySwitcherProps) {
  const pathname = usePathname() ?? '/';
  const active = detectActiveEntity(pathname);

  return (
    <header className="border-b border-[#EFE3D3] bg-[#2C2033] text-[#FFF7ED]">
      <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-3">
        <div className="flex items-center gap-3">
          <Link
            href="/"
            className="inline-flex min-h-[48px] items-center rounded-md px-2 text-base font-bold tracking-tight text-[#FFF7ED] focus:outline-none focus:ring-2 focus:ring-[#F16A24]"
          >
            Navdhya Partner
          </Link>
          {tenantLabel ? (
            <span
              data-testid="active-tenant-badge"
              className="rounded-full border border-[#F16A24]/50 bg-[#F16A24]/20 px-3 py-1 text-xs font-medium text-[#FFF7ED]"
            >
              Org: {tenantLabel}
            </span>
          ) : null}
        </div>

        <nav aria-label="Partner entity switcher" className="flex flex-wrap items-center gap-2">
          {ENTITIES.map((entity) => {
            const isActive = active === entity.kind;
            return (
              <Link
                key={entity.kind}
                href={entity.href}
                data-testid={`entity-switch-${entity.kind}`}
                aria-current={isActive ? 'page' : undefined}
                className={
                  isActive
                    ? 'inline-flex min-h-[48px] items-center rounded-md bg-[#F16A24] px-4 py-2 text-sm font-semibold text-[#FFF7ED] focus:outline-none focus:ring-2 focus:ring-[#FFF7ED]'
                    : 'inline-flex min-h-[48px] items-center rounded-md border border-[#EFE3D3]/30 bg-[#2C2033] px-4 py-2 text-sm font-medium text-[#FFF7ED] hover:bg-[#EFE3D3]/15 focus:outline-none focus:ring-2 focus:ring-[#F16A24]'
                }
              >
                {entity.label}
              </Link>
            );
          })}
          <Link
            href="/earnings"
            className="inline-flex min-h-[48px] items-center rounded-md border border-[#EFE3D3]/40 bg-[#FFF7ED] px-4 py-2 text-sm font-semibold text-[#2C2033] hover:bg-[#EFE3D3] focus:outline-none focus:ring-2 focus:ring-[#F16A24]"
          >
            Earnings
          </Link>
        </nav>
      </div>
    </header>
  );
}

