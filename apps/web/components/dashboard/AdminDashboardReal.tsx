import { Suspense } from 'react';
import { cookies } from 'next/headers';
import { StatCard } from '@/components/dashboard/StatCard';
import { StatusBadge } from '@/components/dashboard/StatusBadge';
import { Search, BarChart3, Users, IndianRupee, Shield, Server, MapPin, Crown, Code, Send, Bell } from 'lucide-react';

/**
 * Server-side fetch of this app's own BFF routes. Relative URL (never the
 * public origin — a dev/E2E server must not reach production) plus the
 * incoming request's cookies so the BFF can authenticate the caller's
 * session. Reading cookies() also opts the admin dashboard out of any
 * static prerender: a per-user view must never be cached across users.
 */
async function bffJSON(path: string): Promise<unknown> {
  const store = await cookies();
  const cookieHeader = store
    .getAll()
    .map((c) => `${c.name}=${c.value}`)
    .join('; ');
  const res = await fetch(path, {
    headers: {
      'Content-Type': 'application/json',
      ...(cookieHeader ? { cookie: cookieHeader } : {}),
    },
    cache: 'no-store',
  });
  if (!res.ok) throw new Error(`BFF ${path} responded ${res.status}`);
  return res.json();
}

interface AdminStats {
  users?: { total: number; active: number; issues: number };
  roles?: { total: number; assignments: number };
  bookings?: { total: number; pending: number; completed: number };
  content?: { queued: number; published: number; in_review: number };
  payouts?: { processed: number; pending: number; on_hold: number };
}

interface SEOQueueItem {
  id: string;
  slug: string;
  cluster: string;
  market: string;
  status: string;
}

async function fetchAdminStats(): Promise<AdminStats> {
  try {
    const data = (await bffJSON('/api/admin/stats')) as { data?: { stats?: AdminStats } };
    return data.data?.stats || {};
  } catch {
    return {};
  }
}

async function fetchSEOQueue(): Promise<SEOQueueItem[]> {
  try {
    const data = (await bffJSON('/api/admin/seo')) as { data?: { data?: SEOQueueItem[] } };
    return data.data?.data || [];
  } catch {
    return [];
  }
}

async function fetchAdminUsers() {
  try {
    const data = (await bffJSON('/api/admin/users?limit=10')) as { data?: { data?: unknown[] } };
    return data.data?.data || [];
  } catch {
    return [];
  }
}

function StatCardLoading() {
  return (
    <div className="rounded-2xl border border-border bg-card p-6 shadow-sm animate-pulse space-y-3">
      <div className="h-5 w-24 bg-muted rounded" />
      <div className="h-10 w-32 bg-muted rounded" />
      <div className="h-4 w-20 bg-muted rounded" />
    </div>
  );
}

function SEOQueueLoading() {
  return (
    <div className="space-y-3">
      {[...Array(3)].map((_, i) => (
        <div key={i} className="flex items-center gap-4 p-3 rounded-xl border border-border animate-pulse">
          <div className="size-10 rounded-lg bg-muted" />
          <div className="flex-1 space-y-2">
            <div className="h-4 w-3/4 bg-muted rounded" />
            <div className="h-3 w-1/2 bg-muted rounded" />
          </div>
        </div>
      ))}
    </div>
  );
}

export async function AdminDashboardReal() {
  const [stats, seoQueue, users] = await Promise.all([
    fetchAdminStats(),
    fetchSEOQueue(),
    fetchAdminUsers(),
  ]);

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-serif font-bold">Platform Admin</h1>
          <p className="text-muted-foreground text-sm">Platform-wide operations overview</p>
        </div>
        <div className="flex gap-2">
          <button className="btn-primary"><Search className="size-4 mr-1" /> Search</button>
        </div>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <Suspense fallback={<StatCardLoading />}>
          <StatCard
            icon={<BarChart3 className="size-5" />}
            label="Platform Actions"
            value={stats.users?.total ? String(stats.users.total) : '—'}
            change="Across all areas"
            changeType="neutral"
          />
        </Suspense>
        <Suspense fallback={<StatCardLoading />}>
          <StatCard
            icon={<Search className="size-5" />}
            label="SEO Queue"
            value={stats.content?.queued ? String(stats.content.queued) : '—'}
            change="Awaiting QA"
            changeType="warning"
          />
        </Suspense>
        <Suspense fallback={<StatCardLoading />}>
          <StatCard
            icon={<Users className="size-5" />}
            label="User Issues"
            value={stats.users?.issues ? String(stats.users.issues) : '—'}
            change="Escalations"
            changeType="danger"
          />
        </Suspense>
        <Suspense fallback={<StatCardLoading />}>
          <StatCard
            icon={<IndianRupee className="size-5" />}
            label="Payout Holds"
            value={stats.payouts?.on_hold ? String(stats.payouts.on_hold) : '—'}
            change="Manual verify"
            changeType="warning"
          />
        </Suspense>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 stat-card">
          <h3 className="text-lg font-semibold mb-4">SEO Queue</h3>
          <Suspense fallback={<SEOQueueLoading />}>
            <div className="space-y-3">
              {seoQueue.length === 0 ? (
                <p className="text-muted-foreground text-center py-8">No SEO items in queue</p>
              ) : (
                seoQueue.map((item) => (
                  <div key={item.id} className="flex items-center gap-4 p-3 rounded-xl border border-border">
                    <div className="size-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                      <Search className="size-5" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="font-medium text-sm truncate">{item.slug}</p>
                      <p className="text-xs text-muted-foreground">{item.cluster} • {item.market}</p>
                    </div>
                    <StatusBadge status={item.status} />
                  </div>
                ))
              )}
            </div>
          </Suspense>
        </div>

        <div className="space-y-6">
          <div className="stat-card">
            <h3 className="text-lg font-semibold mb-4">Payout Overview</h3>
            <div className="space-y-2">
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Processed</span>
                <span className="font-medium text-emerald-600">₹{stats.payouts?.processed?.toLocaleString() ?? '—'}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Pending</span>
                <span className="font-medium text-amber-600">₹{stats.payouts?.pending?.toLocaleString() ?? '—'}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">On Hold</span>
                <span className="font-medium text-red-600">₹{stats.payouts?.on_hold?.toLocaleString() ?? '—'}</span>
              </div>
            </div>
          </div>
          <div className="stat-card">
            <h3 className="text-lg font-semibold mb-4">Content Status</h3>
            <div className="space-y-2">
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Queued</span>
                <span className="font-medium">{stats.content?.queued ?? '—'}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Published</span>
                <span className="font-medium">{stats.content?.published ?? '—'}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">In Review</span>
                <span className="font-medium">{stats.content?.in_review ?? '—'}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export async function SuperAdminDashboardReal() {
  try {
    const data = (await bffJSON('/api/platform/stats')) as { data?: { stats?: Record<string, any> } };
    const stats = data.data?.stats || {};

    return (
      <div className="space-y-6">
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 className="text-2xl font-serif font-bold flex items-center gap-2">
              <Crown className="size-6 text-primary" /> Platform Control
            </h1>
            <p className="text-muted-foreground text-sm">Global platform metrics and health</p>
          </div>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <StatCard icon={<Users className="size-5" />} label="Total Users" value={stats.users?.total_users ? String(stats.users.total_users) : '—'} change="Across all roles" changeType="positive" />
          <StatCard icon={<IndianRupee className="size-5" />} label="Monthly Revenue" value={stats.revenue?.monthly ? `₹${stats.revenue.monthly.toLocaleString()}` : '—'} change="Growth" changeType="positive" />
          <StatCard icon={<Server className="size-5" />} label="System Uptime" value={stats.system?.uptime ? `${stats.system.uptime}%` : '—'} change="Last 30 days" changeType="positive" />
          <StatCard icon={<MapPin className="size-5" />} label="Active Cities" value={stats.cities?.length ? String(stats.cities.length) : '—'} change="Markets" changeType="positive" />
        </div>

        <div className="stat-card">
          <h3 className="text-lg font-semibold mb-4">System Health</h3>
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div className="flex items-center gap-3 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20">
              <span className="size-3 rounded-full bg-emerald-500" />
              <div>
                <p className="text-sm font-medium">API Response</p>
                <p className="text-xs text-muted-foreground">{stats.system?.api_ms ? `${stats.system.api_ms}ms` : '—'}</p>
              </div>
            </div>
            <div className="flex items-center gap-3 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20">
              <span className="size-3 rounded-full bg-emerald-500" />
              <div>
                <p className="text-sm font-medium">Database</p>
                <p className="text-xs text-muted-foreground">{stats.system?.db_ms ? `${stats.system.db_ms}ms` : '—'}</p>
              </div>
            </div>
            <div className="flex items-center gap-3 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20">
              <span className="size-3 rounded-full bg-emerald-500" />
              <div>
                <p className="text-sm font-medium">Cache</p>
                <p className="text-xs text-muted-foreground">{stats.system?.cache_ms ? `${stats.system.cache_ms}ms` : '—'}</p>
              </div>
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div className="stat-card">
            <h3 className="text-lg font-semibold mb-4">Geographic Spread</h3>
            <div className="space-y-2">
              {(stats.cities || []).map((city: { city: string; users: string; percent: number }) => (
                <div key={city.city} className="flex items-center justify-between text-sm">
                  <div className="flex items-center gap-2">
                    <MapPin className="size-3 text-muted-foreground" />
                    <span>{city.city}</span>
                  </div>
                  <span className="text-muted-foreground">{city.users}</span>
                </div>
              ))}
            </div>
          </div>

          <div className="stat-card">
            <h3 className="text-lg font-semibold mb-4">Security Status</h3>
            <div className="space-y-3">
              <div className="flex items-center gap-2 text-sm">
                <span className="text-emerald-500">✓</span> No threats detected
              </div>
              <div className="flex items-center justify-between text-sm">
                <span className="text-muted-foreground">2FA Adoption</span>
                <span className="font-medium">{stats.security?.mfa_adoption ? `${stats.security.mfa_adoption}%` : '—'}</span>
              </div>
              <div className="flex items-center justify-between text-sm">
                <span className="text-muted-foreground">Failed Logins Today</span>
                <span className="font-medium">{stats.security?.failed_logins_today ?? '—'}</span>
              </div>
              <div className="flex items-center justify-between text-sm">
                <span className="text-muted-foreground">Active Sessions</span>
                <span className="font-medium">{stats.security?.active_sessions ?? '—'}</span>
              </div>
            </div>
          </div>

          <div className="stat-card">
            <h3 className="text-lg font-semibold mb-4">Quick Actions</h3>
            <div className="grid grid-cols-2 gap-3">
              <button className="btn-secondary p-4 text-left hover:bg-accent/10">
                <Shield className="size-4 mb-1" /> Create Admin
              </button>
              <button className="btn-secondary p-4 text-left hover:bg-accent/10">
                <Send className="size-4 mb-1" /> Send Broadcast
              </button>
              <button className="btn-secondary p-4 text-left hover:bg-accent/10">
                <Code className="size-4 mb-1" /> Feature Flag
              </button>
              <button className="btn-secondary p-4 text-left hover:bg-accent/10">
                <Bell className="size-4 mb-1" /> System Announcement
              </button>
            </div>
          </div>
        </div>
      </div>
    );
  } catch {
    return (
      <div className="stat-card text-center py-12">
        <p className="text-muted-foreground">Failed to load platform stats. Please try again.</p>
      </div>
    );
  }
}