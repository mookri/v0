import { ReactNode } from 'react'

interface TimelineItemProps {
  icon: ReactNode
  title: string
  subtitle?: string
  time: string
  status?: 'success' | 'warning' | 'danger' | 'info'
}

const STATUS_COLORS = {
  success: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
  warning: 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
  danger: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
  info: 'bg-sky-100 text-sky-600 dark:bg-sky-900/30 dark:text-sky-400',
}

export function TimelineItem({ icon, title, subtitle, time, status = 'info' }: TimelineItemProps) {
  return (
    <div className="flex items-start gap-3 p-3 rounded-xl hover:bg-muted/50 transition-colors">
      <div className={`size-10 rounded-lg flex items-center justify-center flex-shrink-0 ${STATUS_COLORS[status]}`}>
        {icon}
      </div>
      <div className="flex-1 min-w-0">
        <p className="text-sm font-medium text-foreground truncate">{title}</p>
        {subtitle && <p className="text-xs text-muted-foreground mt-0.5">{subtitle}</p>}
      </div>
      <span className="text-xs text-muted-foreground whitespace-nowrap">{time}</span>
    </div>
  )
}
