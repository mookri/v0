import React from 'react'
import Link from 'next/link'

export interface BreadcrumbItem {
  label: string
  href?: string
}

export interface CategoryHeroBannerProps {
  breadcrumbs: BreadcrumbItem[]
  title: string
  description: string
  imageUrl: string
}

export function CategoryHeroBanner({
  breadcrumbs,
  title,
  description,
  imageUrl,
}: CategoryHeroBannerProps) {
  return (
    <section className="mx-auto max-w-7xl px-4 sm:px-6 pt-6 pb-4 md:pt-8">
      {/* Breadcrumbs */}
      <nav className="text-sm font-medium text-gray-500 mb-4 flex items-center gap-2">
        {breadcrumbs.map((crumb, index) => {
          const isLast = index === breadcrumbs.length - 1

          return (
            <React.Fragment key={crumb.label}>
              {crumb.href && !isLast ? (
                <Link href={crumb.href} className="hover:text-[#10263f] transition-colors">
                  {crumb.label}
                </Link>
              ) : (
                <span className={isLast ? "text-[#10263f] font-bold" : ""}>
                  {crumb.label}
                </span>
              )}
              
              {!isLast && <span>/</span>}
            </React.Fragment>
          )
        })}
      </nav>

      {/* Hero Banner */}
      <div className="relative w-full h-[240px] rounded-2xl overflow-hidden">
        <img 
          src={imageUrl} 
          alt={title} 
          className="absolute inset-0 w-full h-full object-cover"
        />
        <div className="absolute inset-0 bg-gradient-to-r from-black/80 via-black/40 to-transparent"></div>
        
        <div className="absolute inset-0 p-5 md:p-8 flex flex-col justify-end">
          <h1 className="text-2xl md:text-[26px] font-bold text-white mb-2 md:mb-3 tracking-tight">{title}</h1>
          <p className="text-white/90 text-[13px] md:text-[14px] max-w-3xl leading-relaxed font-medium line-clamp-2 md:line-clamp-none">
            {description}
          </p>
        </div>
      </div>
    </section>
  )
}
