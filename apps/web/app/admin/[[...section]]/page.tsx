import { notFound } from 'next/navigation'
import { RoleWorkspacePage } from '@/components/product/RoleWorkspacePage'
import { ProductPageShell } from '@/components/product/ProductPageShell'
import { getWorkspaceConfig } from '@/lib/product/role-config'
import { getRouteMetadata } from '@/lib/routes/metadata'
import { AdminDashboardReal } from '@/components/dashboard/AdminDashboardReal'
import { UsersPanel } from '@/components/console/platform/UsersPanel'
import { PanditsPanel } from '@/components/console/admin/PanditsPanel'
import { VendorsPanel } from '@/components/console/admin/VendorsPanel'
import { ManagersPanel } from '@/components/console/admin/ManagersPanel'
import { SupportPanel } from '@/components/console/admin/SupportPanel'
import { BookingsPanel } from '@/components/console/admin/BookingsPanel'
import { ContentPanel } from '@/components/console/admin/ContentPanel'
import { SeoPanel } from '@/components/console/admin/SeoPanel'
import { PayoutsPanel } from '@/components/console/admin/PayoutsPanel'
import { CatalogPanel } from '@/components/console/admin/CatalogPanel'
import { SettingsPanel } from '@/components/console/platform/SettingsPanel'

interface PageProps {
  params: Promise<{ section?: string[] }>
}

export default async function AdminWorkspacePage({ params }: PageProps) {
  const { section = [] } = await params
  const slug = section[0] ?? 'overview'
  const workspace = getWorkspaceConfig('Admin')
  if (!workspace.sections.some((item) => item.slug === slug)) notFound()
  const path = `/admin/${slug}`

  // Use real admin dashboard for overview section
  if (slug === 'overview') {
    return (
      <ProductPageShell path={path}>
        <AdminDashboardReal />
      </ProductPageShell>
    )
  }

  // W1b: real console sections backed by live endpoints. Everything else still
  // falls through to the RoleWorkspacePage placeholder until its backend lands
  // (W2) — see docs/PLATFORM-FEATURES-AND-CONSOLE-PLAN.md.
  if (slug === 'users') {
    return (
      <ProductPageShell path={path}>
        <UsersPanel />
      </ProductPageShell>
    )
  }

  if (slug === 'pandits') {
    return (
      <ProductPageShell path={path}>
        <PanditsPanel />
      </ProductPageShell>
    )
  }

  if (slug === 'vendors') {
    return (
      <ProductPageShell path={path}>
        <VendorsPanel />
      </ProductPageShell>
    )
  }

  if (slug === 'managers') {
    return (
      <ProductPageShell path={path}>
        <ManagersPanel />
      </ProductPageShell>
    )
  }

  if (slug === 'support') {
    return (
      <ProductPageShell path={path}>
        <SupportPanel />
      </ProductPageShell>
    )
  }

  if (slug === 'bookings') {
    return (
      <ProductPageShell path={path}>
        <BookingsPanel />
      </ProductPageShell>
    )
  }

  if (slug === 'content') {
    return (
      <ProductPageShell path={path}>
        <ContentPanel />
      </ProductPageShell>
    )
  }

  if (slug === 'catalog') {
    return (
      <ProductPageShell path={path}>
        <CatalogPanel />
      </ProductPageShell>
    )
  }

  if (slug === 'seo') {
    return (
      <ProductPageShell path={path}>
        <SeoPanel />
      </ProductPageShell>
    )
  }

  if (slug === 'payouts') {
    return (
      <ProductPageShell path={path}>
        <PayoutsPanel />
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

  return (
    <ProductPageShell path={path}>
      <RoleWorkspacePage role="Admin" sectionSlug={slug} breadcrumbs={getRouteMetadata(path).breadcrumbs} />
    </ProductPageShell>
  )
}
