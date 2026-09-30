import { ReactNode } from 'react'

interface StatCardProps {
  icon: ReactNode
  label: string
  value: string
  change?: string
  changeType?: 'positive' | 'negative' | 'neutral' | 'warning' | 'danger'
}

export function StatCard({ icon, label, value, change, changeType = 'neutral' }: StatCardProps) {
  const changeColors = {
    positive: 'text-emerald-500',
    negative: 'text-red-500',
    neutral: 'text-muted-foreground',
    warning: 'text-amber-500',
    danger: 'text-red-500',
  }

  return (
    <div className="stat-card">
      <div className="flex items-center justify-between">
        <p className="text-sm text-muted-foreground">{label}</p>
        <div className="size-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
          {icon}
        </div>
      </div>
      <p className="mt-3 text-3xl font-bold text-foreground">{value}</p>
      {change && (
        <p className={`mt-2 text-xs font-medium ${changeColors[changeType]}`}>
          {change}
        </p>
      )}
    </div>
  )
}
