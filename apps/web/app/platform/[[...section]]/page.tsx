import { notFound } from 'next/navigation'
import { RoleWorkspacePage } from '@/components/product/RoleWorkspacePage'
import { ProductPageShell } from '@/components/product/ProductPageShell'
import { getWorkspaceConfig } from '@/lib/product/role-config'
import { getRouteMetadata } from '@/lib/routes/metadata'
import { SuperAdminDashboardReal } from '@/components/dashboard/AdminDashboardReal'
import { RolesPanel } from '@/components/console/platform/RolesPanel'
import { UsersPanel } from '@/components/console/platform/UsersPanel'
import { ApiPanel } from '@/components/console/platform/ApiPanel'
import { LogsPanel } from '@/components/console/platform/LogsPanel'
import { SettingsPanel } from '@/components/console/platform/SettingsPanel'
import { BillingPanel } from '@/components/console/platform/BillingPanel'
import { SecurityPanel } from '@/components/console/platform/SecurityPanel'
import { SystemPanel } from '@/components/console/platform/SystemPanel'

interface PageProps {
  params: Promise<{ section?: string[] }>
}

export default async function SuperAdminPlatformPage({ params }: PageProps) {
  const { section = [] } = await params
  const slug = section[0] ?? 'overview'
  const workspace = getWorkspaceConfig('SuperAdmin')
  if (!workspace.sections.some((item) => item.slug === slug)) notFound()
  const path = `/platform/${slug}`

  // Use real super admin dashboard for overview section
  if (slug === 'overview') {
    return (
      <ProductPageShell path={path}>
        <SuperAdminDashboardReal />
      </ProductPageShell>
    )
  }

  // W1a: real console sections backed by live endpoints. Everything else still
  // falls through to the RoleWorkspacePage placeholder until its backend lands
  // (W2) — see docs/PLATFORM-FEATURES-AND-CONSOLE-PLAN.md.
  if (slug === 'roles') {
    return (
      <ProductPageShell path={path}>
        <RolesPanel />
      </ProductPageShell>
    )
  }

  if (slug === 'users') {
    return (
      <ProductPageShell path={path}>
        <UsersPanel />
      </ProductPageShell>
    )
  }

  if (slug === 'api') {
    return (
      <ProductPageShell path={path}>
        <ApiPanel />
      </ProductPageShell>
    )
  }

  if (slug === 'logs') {
    return (
      <ProductPageShell path={path}>
        <LogsPanel />
      </ProductPageShell>
    )
  }

  if (slug === 'settings') {
    return (
      <ProductPageShell path={path}>
        <SettingsPanel />
      </ProductPageShell>
    )
  }

  if (slug === 'billing') {
    return (
      <ProductPageShell path={path}>
        <BillingPanel />
      </ProductPageShell>
    )
  }

  if (slug === 'security') {
    return (
      <ProductPageShell path={path}>
        <SecurityPanel />
      </ProductPageShell>
    )
  }

  if (slug === 'system') {
    return (
      <ProductPageShell path={path}>
        <SystemPanel />
      </ProductPageShell>
    )
  }

  return (
    <ProductPageShell path={path}>
      <RoleWorkspacePage role="SuperAdmin" sectionSlug={slug} breadcrumbs={getRouteMetadata(path).breadcrumbs} />
    </ProductPageShell>
  )
}
