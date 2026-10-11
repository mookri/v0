import { notFound } from 'next/navigation'
import { RoleWorkspacePage } from '@/components/product/RoleWorkspacePage'
import { ProductPageShell } from '@/components/product/ProductPageShell'
import { getWorkspaceConfig } from '@/lib/product/role-config'
import { getRouteMetadata } from '@/lib/routes/metadata'
import { SuperAdminDashboardReal } from '@/components/dashboard/AdminDashboardReal'

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

  return (
    <ProductPageShell path={path}>
      <RoleWorkspacePage role="SuperAdmin" sectionSlug={slug} breadcrumbs={getRouteMetadata(path).breadcrumbs} />
    </ProductPageShell>
  )
}
