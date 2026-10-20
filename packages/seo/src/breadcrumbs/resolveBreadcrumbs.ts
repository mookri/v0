export interface Breadcrumb {
  label: string;
  href: string;
}

export function resolveBreadcrumbs(trail: { label: string; href: string }[]): Breadcrumb[] {
  return trail;
}
