import { ReactNode } from 'react'
import Link from 'next/link'

interface QuickActionsProps {
  actions: Array<{
    label: string
    href?: string
    onClick?: () => void
    icon: ReactNode
    variant?: 'default' | 'primary'
  }>
}

export function QuickActions({ actions }: QuickActionsProps) {
  return (
    <div className="stat-card">
      <h3 className="text-lg font-semibold mb-4">Quick Actions</h3>
      <div className="space-y-2">
        {actions.map((action) => {
          const content = (
            <div className="flex items-center gap-3 p-3 rounded-xl border border-border hover:bg-muted transition-colors cursor-pointer">
              <div className="size-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary flex-shrink-0">
                {action.icon}
              </div>
              <span className="text-sm font-medium">{action.label}</span>
            </div>
          )

          if (action.href) {
            return <Link key={action.label} href={action.href}>{content}</Link>
          }
          return <button key={action.label} onClick={action.onClick} className="w-full text-left">{content}</button>
        })}
      </div>
    </div>
  )
}
